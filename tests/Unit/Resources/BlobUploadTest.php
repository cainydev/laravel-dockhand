<?php

use Cainy\Dockhand\Resources\BlobUpload;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

it('constructs correctly', function () {
    $upload = new BlobUpload('my/repo', 'uuid-123', '/v2/my/repo/blobs/uploads/uuid-123', 0);
    expect($upload->repository)->toBe('my/repo')
        ->and($upload->uuid)->toBe('uuid-123')
        ->and($upload->location)->toBe('/v2/my/repo/blobs/uploads/uuid-123')
        ->and($upload->offset)->toBe(0);
});

it('creates from response', function () {
    $psr7 = new Psr7Response(202, [
        'Docker-Upload-UUID' => 'test-uuid',
        'Location' => '/v2/repo/blobs/uploads/test-uuid',
        'Range' => '0-1024',
    ]);
    $response = new Response($psr7);

    $upload = BlobUpload::fromResponse('my/repo', $response);
    expect($upload->repository)->toBe('my/repo')
        ->and($upload->uuid)->toBe('test-uuid')
        ->and($upload->location)->toBe('/v2/repo/blobs/uploads/test-uuid')
        ->and($upload->offset)->toBe(1024);
});

it('defaults offset to 0 when no range header', function () {
    $psr7 = new Psr7Response(202, [
        'Docker-Upload-UUID' => 'uuid',
        'Location' => '/upload',
    ]);
    $response = new Response($psr7);

    $upload = BlobUpload::fromResponse('repo', $response);
    expect($upload->offset)->toBe(0);
});

it('converts to array', function () {
    $upload = new BlobUpload('my/repo', 'uuid-123', '/location', 512);
    expect($upload->toArray())->toBe([
        'repository' => 'my/repo',
        'uuid' => 'uuid-123',
        'location' => '/location',
        'offset' => 512,
    ]);
});

it('implements JsonSerializable', function () {
    $upload = new BlobUpload('repo', 'uuid', '/loc', 0);
    expect($upload->jsonSerialize())->toBe($upload->toArray());
});
