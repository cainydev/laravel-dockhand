<?php

use Cainy\Dockhand\Events\BlobDeletedEvent;
use Cainy\Dockhand\Events\BlobMountedEvent;
use Cainy\Dockhand\Events\BlobPulledEvent;
use Cainy\Dockhand\Events\BlobPushedEvent;
use Cainy\Dockhand\Events\EventAction;
use Cainy\Dockhand\Events\ManifestDeletedEvent;
use Cainy\Dockhand\Events\ManifestPulledEvent;
use Cainy\Dockhand\Events\ManifestPushedEvent;
use Cainy\Dockhand\Events\TagDeletedEvent;
use Cainy\Dockhand\Exceptions\ParseException;
use Cainy\Dockhand\Exceptions\UnauthorizedException;
use Cainy\Dockhand\Exceptions\UnknownException;
use Cainy\Dockhand\Exceptions\UnsupportedException;
use Cainy\Dockhand\Facades\TokenService;
use Cainy\Dockhand\Resources\MediaType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;

Route::post(config('dockhand.notifications.route'), function (Request $request) {
    if ($request->bearerToken() === null) {
        throw new UnauthorizedException('Bearer token required.');
    }

    if (!TokenService::validateToken($request->bearerToken(), function ($validator, $token) {
        $validator->assert($token, new HasClaimWithValue('access', 'notify'));
    })) {
        throw new UnauthorizedException('Bearer token invalid or lacks required "notify" access.');
    }

    if (!$request->has('events')
        || !is_array($request->get('events'))
        || empty($request->get('events'))) {
        throw new ParseException('No events provided or events is not an array.');
    }

    foreach ($request->input('events', []) as $eventData) {
        if (
            !isset($eventData['action'], $eventData['target'], $eventData['id'], $eventData['timestamp']) ||
            !is_array($eventData['target'])
        ) {
            Log::channel('stderr')->error('Malformed event received, missing essential fields: ', $eventData);
            continue;
        }

        Log::channel('stderr')->info('Processing event: ' . json_encode($eventData, JSON_PRETTY_PRINT));

        try {
            $action = EventAction::from($eventData['action']);
            $target = $eventData['target'];
            $mediaType = isset($target['mediaType']) ? MediaType::fromString($target['mediaType']) : null;

            switch ($action) {
                case EventAction::PUSH:
                    if (!$mediaType) {
                        Log::channel('stderr')->error("PUSH event without a target.mediaType. Event ID: {$eventData['id']}");
                        continue 2;
                    }

                    if ($mediaType->isImageManifest() || $mediaType->isImageIndex()) {
                        Log::channel('stderr')->info("Dispatching ManifestPushedEvent for ID: {$eventData['id']} (MediaType: {$mediaType->value})");
                        ManifestPushedEvent::dispatch($eventData);
                    } elseif ($mediaType->isImageLayer() || $mediaType->isImageConfig() || $mediaType->isCustom()) {
                        Log::channel('stderr')->info("Dispatching BlobPushedEvent for ID: {$eventData['id']} (MediaType: {$mediaType->value})");
                        BlobPushedEvent::dispatch($eventData);
                    } else {
                        Log::channel('stderr')->warning("Unhandled mediaType '{$mediaType->value}' for PUSH action. Event ID: {$eventData['id']}. Defaulting to BlobPushedEvent.");
                        BlobPushedEvent::dispatch($eventData);
                    }
                    break;

                case EventAction::PULL:
                    if (!$mediaType) {
                        Log::channel('stderr')->error("PULL event without a target.mediaType. Event ID: {$eventData['id']}");
                        continue 2;
                    }

                    if ($mediaType->isImageManifest() || $mediaType->isImageIndex()) {
                        Log::channel('stderr')->info("Dispatching ManifestPulledEvent for ID: {$eventData['id']} (MediaType: {$mediaType->value})");
                        ManifestPulledEvent::dispatch($eventData);
                    } elseif ($mediaType->isImageLayer() || $mediaType->isImageConfig() || $mediaType->isCustom() || $mediaType === MediaType::CONTAINER_CONFIG_V1) {
                        Log::channel('stderr')->info("Dispatching BlobPulledEvent for ID: {$eventData['id']} (MediaType: {$mediaType->value})");
                        BlobPulledEvent::dispatch($eventData);
                    } else {
                        Log::channel('stderr')->warning("Unhandled mediaType '{$mediaType->value}' for PULL action. Event ID: {$eventData['id']}. Defaulting to BlobPulledEvent.");
                        BlobPulledEvent::dispatch($eventData);
                    }
                    break;

                case EventAction::MOUNT:
                    if ($mediaType === null || $mediaType->isImageLayer() || $mediaType->isImageConfig() || $mediaType->isCustom() || $mediaType === MediaType::CONTAINER_CONFIG_V1) {
                        Log::channel('stderr')->info("Dispatching BlobMountedEvent for ID: {$eventData['id']}" . ($mediaType ? " (MediaType: {$mediaType->value})" : " (MediaType: null)"));
                        BlobMountedEvent::dispatch($eventData);
                    } elseif ($mediaType->isImageManifest() || $mediaType->isImageIndex()) {
                        Log::channel('stderr')->error("MOUNT event received for a manifest/index mediaType '{$mediaType->value}'. This is unexpected. Skipping. Event ID: {$eventData['id']}");
                        continue 2;
                    } else {
                        Log::channel('stderr')->warning("MOUNT event with unexpected specific mediaType '{$mediaType->value}'. Assuming blob-like. Event ID: {$eventData['id']}");
                        BlobMountedEvent::dispatch($eventData);
                    }
                    break;

                case EventAction::DELETE:
                    if (!empty($target['tag'])) {
                        Log::channel('stderr')->info("Dispatching TagDeletedEvent for tag: {$target['tag']}, Repo: {$target['repository']}. Event ID: {$eventData['id']}");
                        TagDeletedEvent::dispatch($eventData);
                    } elseif (!empty($target['digest'])) {
                        if ($mediaType && ($mediaType->isImageManifest() || $mediaType->isImageIndex())) {
                            Log::channel('stderr')->info("Dispatching ManifestDeletedEvent for digest: {$target['digest']}, Repo: {$target['repository']}. Event ID: {$eventData['id']} (MediaType: {$mediaType->value})");
                            ManifestDeletedEvent::dispatch($eventData);
                        } else {
                            $mtValue = $mediaType ? $mediaType->value : 'null';
                            Log::channel('stderr')->info("Dispatching BlobDeletedEvent for digest: {$target['digest']}, Repo: {$target['repository']}. Event ID: {$eventData['id']} (MediaType: {$mtValue})");
                            BlobDeletedEvent::dispatch($eventData);
                        }
                    } else {
                        Log::channel('stderr')->error("DELETE event without 'target.tag' or 'target.digest'. Event ID: {$eventData['id']}. Payload: " . json_encode($target));
                        continue 2;
                    }
                    break;

                default:
                    throw new UnsupportedException("Unknown event action '{$eventData['action']}' encountered in switch. Event ID: {$eventData['id']}");
            }
        } catch (\ValueError $e) {
            Log::channel('stderr')->error("Invalid event action string '{$eventData['action']}'. Error: {$e->getMessage()}. Event ID: {$eventData['id']}");
            throw new ParseException("Invalid event action string '{$eventData['action']}'. Event ID: {$eventData['id']}", 0, $e);
        } catch (UnauthorizedException|ParseException|UnsupportedException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel('stderr')->error("An unexpected error occurred while processing event ID {$eventData['id']}: {$e->getMessage()}\n" . $e->getTraceAsString());
            throw new UnknownException("An unexpected error occurred while processing event. Please check logs. Event ID: {$eventData['id']}", 0, $e);
        }
    }

    return response()->json(['message' => 'Notifications received and processed.'], 202);
});
