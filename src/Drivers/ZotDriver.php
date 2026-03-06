<?php

namespace Cainy\Dockhand\Drivers;

use Cainy\Dockhand\Contracts\Authenticator;
use Cainy\Dockhand\Contracts\ZotCapabilities;
use Cainy\Dockhand\Exceptions\ExtensionNotEnabledException;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

class ZotDriver extends AbstractRegistryDriver implements ZotCapabilities
{
    protected int $extensionCacheTtl;

    public function __construct(string $baseUrl, Authenticator $auth, LoggerInterface $logger, int $extensionCacheTtl = 300)
    {
        parent::__construct($baseUrl, $auth, $logger);
        $this->extensionCacheTtl = $extensionCacheTtl;
    }

    public function supportsTagDeletion(): bool
    {
        return true;
    }

    public function discoverExtensions(): array
    {
        $cacheKey = 'dockhand:zot:extensions:' . md5($this->baseUrl);

        return Cache::remember($cacheKey, $this->extensionCacheTtl, function () {
            try {
                $response = $this->authenticatedRequest('none')
                    ->get('/_zot/ext/discover');

                if (!$response->successful()) {
                    return [];
                }

                /** @var array<int, mixed> $extensions */
                $extensions = $response->json('extensions', []);

                return $extensions;
            } catch (\Exception $e) {
                $this->logger()->warning('[ZotDriver] Failed to discover extensions', [
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    public function clearExtensionCache(): void
    {
        $cacheKey = 'dockhand:zot:extensions:' . md5($this->baseUrl);
        Cache::forget($cacheKey);
    }

    public function search(string $query, array $variables = []): array
    {
        $this->requireExtension('search');

        $response = $this->authenticatedRequest('none')
            ->post('/_zot/ext/search', [
                'query' => $query,
                'variables' => $variables,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Zot search request failed. Status: " . $response->status() . " Body: " . $response->body());
        }

        /** @var array<string, mixed> $result */
        $result = $response->json();

        return $result;
    }

    public function searchCVE(string $repository, string $reference): array
    {
        $query = <<<'GRAPHQL'
        query ($repo: String!, $reference: String!) {
            CVEListForImage(image: $repo, tag: $reference) {
                Tag
                CVEList {
                    Id
                    Title
                    Description
                    Severity
                    PackageList {
                        Name
                        InstalledVersion
                        FixedVersion
                    }
                }
            }
        }
        GRAPHQL;

        $result = $this->search($query, [
            'repo' => $repository,
            'reference' => $reference,
        ]);

        /** @var array<string, mixed> $data */
        $data = $result['data'] ?? [];
        /** @var array<string, mixed> $cveList */
        $cveList = $data['CVEListForImage'] ?? [];

        return $cveList;
    }

    public function starRepository(string $repository): bool
    {
        return $this->toggleUserPref('toggleStar', $repository);
    }

    public function unstarRepository(string $repository): bool
    {
        return $this->toggleUserPref('toggleStar', $repository);
    }

    public function bookmarkRepository(string $repository): bool
    {
        return $this->toggleUserPref('toggleBookmark', $repository);
    }

    public function unbookmarkRepository(string $repository): bool
    {
        return $this->toggleUserPref('toggleBookmark', $repository);
    }

    public function getReferrers(string $repository, string $digest, ?string $artifactType = null): array
    {
        $url = "/{$repository}/referrers/{$digest}";

        if ($artifactType !== null) {
            $url .= '?' . http_build_query(['artifactType' => $artifactType]);
        }

        try {
            $response = $this->authenticatedRequest('read', $repository)
                ->get($url);
        } catch (\Exception $e) {
            return [];
        }

        if (!$response->successful()) {
            return [];
        }

        /** @var array<int, mixed> $manifests */
        $manifests = $response->json('manifests', []);

        return $manifests;
    }

    protected function toggleUserPref(string $action, string $repository): bool
    {
        $this->requireExtension('userprefs');

        $response = $this->authenticatedRequest('none')
            ->put("/_zot/ext/userprefs?" . http_build_query([
                'action' => $action,
                'repo' => $repository,
            ]));

        return $response->successful();
    }

    /**
     * @throws ExtensionNotEnabledException
     */
    protected function requireExtension(string $extension): void
    {
        $extensions = $this->discoverExtensions();

        $found = false;
        foreach ($extensions as $ext) {
            /** @var string $name */
            $name = is_array($ext) ? ($ext['name'] ?? '') : $ext;
            if (str_contains(strtolower($name), strtolower($extension))) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new ExtensionNotEnabledException($extension);
        }
    }
}
