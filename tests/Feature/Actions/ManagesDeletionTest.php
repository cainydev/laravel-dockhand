<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Drivers\ZotDriver;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
    $this->zotDriver = new ZotDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

it('deletes a manifest by digest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/sha256:abc*' => Http::response('', 202),
    ]);

    expect($this->driver->deleteManifest('repo', 'sha256:abc'))->toBeTrue();
});

it('returns false for 404 on deleteManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/sha256:missing*' => Http::response('', 404),
    ]);

    expect($this->driver->deleteManifest('repo', 'sha256:missing'))->toBeFalse();
});

it('throws on 405 for deleteManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/sha256:abc*' => Http::response('', 405),
    ]);

    $this->driver->deleteManifest('repo', 'sha256:abc');
})->throws(Exception::class, 'Delete is not allowed');

it('throws when deleting by tag on distribution driver', function () {
    $this->driver->deleteManifest('repo', 'latest');
})->throws(InvalidArgumentException::class, 'only supports deletion by digest');

it('allows deleting by tag on zot driver', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest*' => Http::response('', 202),
    ]);

    expect($this->zotDriver->deleteManifest('repo', 'latest'))->toBeTrue();
});

it('throws on connection error for deleteManifest', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->deleteManifest('repo', 'sha256:abc');
})->throws(Exception::class, 'Connection to registry failed');

it('deletes a blob by digest', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:blob*' => Http::response('', 202),
    ]);

    expect($this->driver->deleteBlob('repo', 'sha256:blob'))->toBeTrue();
});

it('returns false for 404 on deleteBlob', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:missing*' => Http::response('', 404),
    ]);

    expect($this->driver->deleteBlob('repo', 'sha256:missing'))->toBeFalse();
});

it('throws on server error for deleteBlob', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:err*' => Http::response('error', 500),
    ]);

    $this->driver->deleteBlob('repo', 'sha256:err');
})->throws(Exception::class, 'Failed to delete blob');

it('throws on server error for deleteManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/sha256:err*' => Http::response('error body', 500),
    ]);

    $this->driver->deleteManifest('repo', 'sha256:err');
})->throws(Exception::class, 'Failed to delete manifest');

it('includes body in deleteManifest error message', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/sha256:err*' => Http::response('detailed error', 500),
    ]);

    try {
        $this->driver->deleteManifest('repo', 'sha256:err');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Body: detailed error');
    }
});

it('includes body in deleteBlob error message', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:err*' => Http::response('detailed error', 500),
    ]);

    try {
        $this->driver->deleteBlob('repo', 'sha256:err');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Body: detailed error');
    }
});

it('throws on connection error for deleteBlob', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->deleteBlob('repo', 'sha256:abc');
})->throws(Exception::class, 'Connection to registry failed');
