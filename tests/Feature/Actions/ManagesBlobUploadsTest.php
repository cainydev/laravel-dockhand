<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Exceptions\BlobUploadInvalidException;
use Cainy\Dockhand\Exceptions\BlobUploadUnknownException;
use Cainy\Dockhand\Exceptions\DigestInvalidException;
use Cainy\Dockhand\Exceptions\RangeInvalidException;
use Cainy\Dockhand\Resources\BlobUpload;
use Cainy\Dockhand\Resources\PushResult;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

it('initiates a blob upload', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/' => Http::response('', 202, [
            'Docker-Upload-UUID' => 'uuid-123',
            'Location' => '/v2/repo/blobs/uploads/uuid-123',
            'Range' => '0-0',
        ]),
    ]);

    $result = $this->driver->initiateBlobUpload('repo');
    expect($result)->toBeInstanceOf(BlobUpload::class)
        ->and($result->uuid)->toBe('uuid-123')
        ->and($result->repository)->toBe('repo');
});

it('throws on failed initiate', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/' => Http::response('error', 500),
    ]);

    $this->driver->initiateBlobUpload('repo');
})->throws(Exception::class, 'Failed to initiate blob upload');

it('mounts a blob successfully (201)', function () {
    Http::fake([
        'localhost:5000/v2/target/blobs/uploads/*' => Http::response('', 201, [
            'Location' => '/v2/target/blobs/sha256:abc',
            'Docker-Content-Digest' => 'sha256:abc',
        ]),
    ]);

    $result = $this->driver->mountBlob('target', 'sha256:abc', 'source');
    expect($result)->toBeInstanceOf(PushResult::class)
        ->and($result->digest)->toBe('sha256:abc');
});

it('mounts a blob with fallback to upload (202)', function () {
    Http::fake([
        'localhost:5000/v2/target/blobs/uploads/*' => Http::response('', 202, [
            'Docker-Upload-UUID' => 'uuid-456',
            'Location' => '/v2/target/blobs/uploads/uuid-456',
            'Range' => '0-0',
        ]),
    ]);

    $result = $this->driver->mountBlob('target', 'sha256:abc', 'source');
    expect($result)->toBeInstanceOf(BlobUpload::class);
});

it('throws on failed mount', function () {
    Http::fake([
        'localhost:5000/v2/target/blobs/uploads/*' => Http::response('error', 500),
    ]);

    $this->driver->mountBlob('target', 'sha256:abc', 'source');
})->throws(Exception::class, 'Failed to mount blob');

it('uploads a blob chunk', function () {
    Http::fake([
        '*' => Http::response('', 202, [
            'Docker-Upload-UUID' => 'uuid-123',
            'Location' => '/v2/repo/blobs/uploads/uuid-123',
            'Range' => '0-1023',
        ]),
    ]);

    $upload = new BlobUpload('repo', 'uuid-123', '/v2/repo/blobs/uploads/uuid-123', 0);
    $result = $this->driver->uploadBlobChunk($upload, str_repeat('x', 1024));

    expect($result)->toBeInstanceOf(BlobUpload::class)
        ->and($result->offset)->toBe(1023);
});

it('throws RangeInvalidException on 416', function () {
    Http::fake([
        '*' => Http::response('', 416),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->uploadBlobChunk($upload, 'data');
})->throws(RangeInvalidException::class);

it('throws BlobUploadUnknownException on 404 for chunk', function () {
    Http::fake([
        '*' => Http::response('', 404),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->uploadBlobChunk($upload, 'data');
})->throws(BlobUploadUnknownException::class);

it('completes a blob upload', function () {
    Http::fake([
        '*' => Http::response('', 201, [
            'Location' => '/v2/repo/blobs/sha256:final',
            'Docker-Content-Digest' => 'sha256:final',
        ]),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 1024);
    $result = $this->driver->completeBlobUpload($upload, 'sha256:final', 'last-chunk');

    expect($result)->toBeInstanceOf(PushResult::class)
        ->and($result->digest)->toBe('sha256:final');
});

it('completes a blob upload without data', function () {
    Http::fake([
        '*' => Http::response('', 201, [
            'Location' => '/v2/repo/blobs/sha256:final',
            'Docker-Content-Digest' => 'sha256:final',
        ]),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 1024);
    $result = $this->driver->completeBlobUpload($upload, 'sha256:final');

    expect($result)->toBeInstanceOf(PushResult::class);
});

it('throws DigestInvalidException on 400 DIGEST_INVALID', function () {
    Http::fake([
        '*' => Http::response([
            'errors' => [['code' => 'DIGEST_INVALID', 'message' => 'bad digest']],
        ], 400),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->completeBlobUpload($upload, 'sha256:bad');
})->throws(DigestInvalidException::class);

it('throws BlobUploadInvalidException on 400 other', function () {
    Http::fake([
        '*' => Http::response([
            'errors' => [['code' => 'OTHER', 'message' => 'other']],
        ], 400),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->completeBlobUpload($upload, 'sha256:x');
})->throws(BlobUploadInvalidException::class);

it('gets blob upload status', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/uuid-123' => Http::response('', 204, [
            'Docker-Upload-UUID' => 'uuid-123',
            'Location' => '/v2/repo/blobs/uploads/uuid-123',
            'Range' => '0-2048',
        ]),
    ]);

    $result = $this->driver->getBlobUploadStatus('repo', 'uuid-123');
    expect($result)->toBeInstanceOf(BlobUpload::class)
        ->and($result->offset)->toBe(2048);
});

it('throws BlobUploadUnknownException on 404 for status', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/missing' => Http::response('', 404),
    ]);

    $this->driver->getBlobUploadStatus('repo', 'missing');
})->throws(BlobUploadUnknownException::class);

it('cancels a blob upload', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/uuid-123' => Http::response('', 204),
    ]);

    expect($this->driver->cancelBlobUpload('repo', 'uuid-123'))->toBeTrue();
});

it('returns false when cancelling non-existent upload', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/missing' => Http::response('', 404),
    ]);

    expect($this->driver->cancelBlobUpload('repo', 'missing'))->toBeFalse();
});

it('uploads a blob monolithically', function () {
    $callCount = 0;
    Http::fake(function ($request) use (&$callCount) {
        $callCount++;
        if ($callCount === 1) {
            // initiateBlobUpload - POST
            return Http::response('', 202, [
                'Docker-Upload-UUID' => 'uuid-mono',
                'Location' => '/v2/repo/blobs/uploads/uuid-mono',
                'Range' => '0-0',
            ]);
        }

        // completeBlobUpload - PUT
        return Http::response('', 201, [
            'Location' => '/v2/repo/blobs/sha256:mono',
            'Docker-Content-Digest' => 'sha256:mono',
        ]);
    });

    $result = $this->driver->uploadBlob('repo', 'blob-data', 'sha256:mono');
    expect($result)->toBeInstanceOf(PushResult::class)
        ->and($result->digest)->toBe('sha256:mono');
});

it('handles absolute Location urls from uploads', function () {
    $callCount = 0;
    Http::fake(function ($request) use (&$callCount) {
        $callCount++;
        if ($callCount === 1) {
            return Http::response('', 202, [
                'Docker-Upload-UUID' => 'uuid-abs',
                'Location' => 'http://localhost:5000/v2/repo/blobs/uploads/uuid-abs',
                'Range' => '0-0',
            ]);
        }

        return Http::response('', 201, [
            'Location' => '/v2/repo/blobs/sha256:abs',
            'Docker-Content-Digest' => 'sha256:abs',
        ]);
    });

    $result = $this->driver->uploadBlob('repo', 'data', 'sha256:abs');
    expect($result)->toBeInstanceOf(PushResult::class);
});

it('handles absolute Location urls from different host', function () {
    Http::fake(function ($request) {
        // The resolve will strip the base URL and use the location directly
        return Http::response('', 201, [
            'Location' => '/v2/repo/blobs/sha256:done',
            'Docker-Content-Digest' => 'sha256:done',
        ]);
    });

    $upload = new BlobUpload('repo', 'uuid', 'http://other-host:5000/v2/repo/blobs/uploads/uuid', 0);
    $result = $this->driver->completeBlobUpload($upload, 'sha256:done', 'data');
    expect($result)->toBeInstanceOf(PushResult::class);
});

it('throws on connection error for initiateBlobUpload', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->initiateBlobUpload('repo');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on connection error for mountBlob', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->mountBlob('target', 'sha256:abc', 'source');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on connection error for uploadBlobChunk', function () {
    Http::fake([
        '*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->uploadBlobChunk($upload, 'data');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on server error for uploadBlobChunk', function () {
    Http::fake([
        '*' => Http::response('error', 500),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->uploadBlobChunk($upload, 'data');
})->throws(Exception::class, 'Failed to upload blob chunk');

it('throws on connection error for completeBlobUpload', function () {
    Http::fake([
        '*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->completeBlobUpload($upload, 'sha256:x');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on non-201/400 completeBlobUpload failure', function () {
    Http::fake([
        '*' => Http::response('error', 500),
    ]);

    $upload = new BlobUpload('repo', 'uuid', '/v2/repo/blobs/uploads/uuid', 0);
    $this->driver->completeBlobUpload($upload, 'sha256:x');
})->throws(Exception::class, 'Failed to complete blob upload');

it('throws on connection error for getBlobUploadStatus', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->getBlobUploadStatus('repo', 'uuid-123');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on server error for getBlobUploadStatus', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/uuid-123' => Http::response('error', 500),
    ]);

    $this->driver->getBlobUploadStatus('repo', 'uuid-123');
})->throws(Exception::class, 'Failed to get blob upload status');

it('throws on connection error for cancelBlobUpload', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->cancelBlobUpload('repo', 'uuid-123');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on server error for cancelBlobUpload', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/uploads/uuid-123' => Http::response('error', 500),
    ]);

    $this->driver->cancelBlobUpload('repo', 'uuid-123');
})->throws(Exception::class, 'Failed to cancel blob upload');
