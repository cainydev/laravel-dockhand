<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

it('does not support tag deletion', function () {
    expect($this->driver->supportsTagDeletion())->toBeFalse();
});

it('returns Docker-Content-Digest as content digest header', function () {
    expect($this->driver->contentDigestHeader())->toBe('Docker-Content-Digest');
});

it('returns referrers from registry', function () {
    Http::fake([
        'localhost:5000/v2/repo/referrers/sha256:abc*' => Http::response([
            'manifests' => [
                ['digest' => 'sha256:ref1', 'mediaType' => 'application/vnd.oci.image.manifest.v1+json'],
            ],
        ]),
    ]);

    $result = $this->driver->getReferrers('repo', 'sha256:abc');
    expect($result)->toHaveCount(1)
        ->and($result[0]['digest'])->toBe('sha256:ref1');
});

it('returns empty array when referrers request fails', function () {
    Http::fake([
        'localhost:5000/v2/repo/referrers/*' => Http::response('error', 404),
    ]);

    $result = $this->driver->getReferrers('repo', 'sha256:abc');
    expect($result)->toBe([]);
});

it('passes artifactType filter to referrers', function () {
    Http::fake([
        'localhost:5000/v2/repo/referrers/sha256:abc*' => Http::response(['manifests' => []]),
    ]);

    $this->driver->getReferrers('repo', 'sha256:abc', 'application/vnd.example+json');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'artifactType=');
    });
});

it('returns empty array on exception for referrers', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $result = $this->driver->getReferrers('repo', 'sha256:abc');
    expect($result)->toBe([]);
});
