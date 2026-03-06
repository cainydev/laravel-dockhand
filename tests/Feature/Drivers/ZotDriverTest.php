<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\ZotDriver;
use Cainy\Dockhand\Exceptions\ExtensionNotEnabledException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new ZotDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger, 300);
    Cache::flush();
});

it('supports tag deletion', function () {
    expect($this->driver->supportsTagDeletion())->toBeTrue();
});

it('discovers extensions', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [
                ['name' => 'search', 'url' => '/v2/_zot/ext/search'],
                ['name' => 'userprefs', 'url' => '/v2/_zot/ext/userprefs'],
            ],
        ]),
    ]);

    $extensions = $this->driver->discoverExtensions();
    expect($extensions)->toHaveCount(2)
        ->and($extensions[0]['name'])->toBe('search');
});

it('caches extension discovery results', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'search']],
        ]),
    ]);

    $this->driver->discoverExtensions();
    $this->driver->discoverExtensions();

    // Should only make one HTTP call due to caching
    Http::assertSentCount(1);
});

it('clears extension cache', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'search']],
        ]),
    ]);

    $this->driver->discoverExtensions();
    $this->driver->clearExtensionCache();
    $this->driver->discoverExtensions();

    Http::assertSentCount(2);
});

it('returns empty on discovery failure', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response('error', 500),
    ]);

    expect($this->driver->discoverExtensions())->toBe([]);
});

it('searches via GraphQL', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'search']],
        ]),
        'localhost:5000/v2/_zot/ext/search' => Http::response([
            'data' => ['GlobalSearch' => ['repos' => []]],
        ]),
    ]);

    $result = $this->driver->search('{ GlobalSearch { repos { name } } }');
    expect($result)->toHaveKey('data');
});

it('searchCVE sends correct query', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'search']],
        ]),
        'localhost:5000/v2/_zot/ext/search' => Http::response([
            'data' => ['CVEListForImage' => ['Tag' => 'latest', 'CVEList' => []]],
        ]),
    ]);

    $result = $this->driver->searchCVE('library/nginx', 'latest');
    expect($result)->toHaveKey('Tag');
});

it('throws ExtensionNotEnabledException when extension missing', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [],
        ]),
    ]);

    $this->driver->search('query');
})->throws(ExtensionNotEnabledException::class, 'search');

it('stars a repository', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'userprefs']],
        ]),
        'localhost:5000/v2/_zot/ext/userprefs*' => Http::response('', 200),
    ]);

    expect($this->driver->starRepository('my/repo'))->toBeTrue();
});

it('unstars a repository', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'userprefs']],
        ]),
        'localhost:5000/v2/_zot/ext/userprefs*' => Http::response('', 200),
    ]);

    expect($this->driver->unstarRepository('my/repo'))->toBeTrue();
});

it('bookmarks a repository', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'userprefs']],
        ]),
        'localhost:5000/v2/_zot/ext/userprefs*' => Http::response('', 200),
    ]);

    expect($this->driver->bookmarkRepository('my/repo'))->toBeTrue();
});

it('unbookmarks a repository', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'userprefs']],
        ]),
        'localhost:5000/v2/_zot/ext/userprefs*' => Http::response('', 200),
    ]);

    expect($this->driver->unbookmarkRepository('my/repo'))->toBeTrue();
});

it('throws when userprefs extension not available', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'search']],
        ]),
    ]);

    $this->driver->starRepository('repo');
})->throws(ExtensionNotEnabledException::class, 'userprefs');

it('gets referrers', function () {
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

it('gets referrers with artifact type filter', function () {
    Http::fake([
        'localhost:5000/v2/repo/referrers/sha256:abc*' => Http::response([
            'manifests' => [],
        ]),
    ]);

    $result = $this->driver->getReferrers('repo', 'sha256:abc', 'application/vnd.example');
    expect($result)->toBe([]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'artifactType=');
    });
});

it('returns empty array on referrers connection error', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    expect($this->driver->getReferrers('repo', 'sha256:abc'))->toBe([]);
});

it('returns empty array on referrers failure', function () {
    Http::fake([
        'localhost:5000/v2/repo/referrers/*' => Http::response('error', 500),
    ]);

    expect($this->driver->getReferrers('repo', 'sha256:abc'))->toBe([]);
});

it('handles discovery exception gracefully', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => fn () => throw new \Exception('network error'),
    ]);

    expect($this->driver->discoverExtensions())->toBe([]);
});

it('throws on search failure', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => [['name' => 'search']],
        ]),
        'localhost:5000/v2/_zot/ext/search' => Http::response('error', 500),
    ]);

    $this->driver->search('query');
})->throws(Exception::class, 'search request failed');

it('handles string extensions in requireExtension', function () {
    Http::fake([
        'localhost:5000/v2/_zot/ext/discover' => Http::response([
            'extensions' => ['search', 'userprefs'],
        ]),
        'localhost:5000/v2/_zot/ext/search' => Http::response([
            'data' => ['result' => true],
        ]),
    ]);

    $result = $this->driver->search('query');
    expect($result)->toHaveKey('data');
});
