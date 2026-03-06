<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Exceptions\PaginationNumberInvalidException;
use Cainy\Dockhand\Resources\PaginatedResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

it('gets repositories as collection', function () {
    Http::fake([
        'localhost:5000/v2/_catalog' => Http::response([
            'repositories' => ['repo1', 'repo2', 'repo3'],
        ]),
    ]);

    $result = $this->driver->getRepositories();
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->toArray())->toBe(['repo1', 'repo2', 'repo3']);
});

it('gets repositories with pagination', function () {
    Http::fake([
        'localhost:5000/v2/_catalog*' => Http::response([
            'repositories' => ['repo1', 'repo2'],
        ], 200, [
            'Link' => '</v2/_catalog?n=2&last=repo2>; rel="next"',
        ]),
    ]);

    $result = $this->driver->getRepositories(limit: 2);
    expect($result)->toBeInstanceOf(PaginatedResult::class)
        ->and($result->items->toArray())->toBe(['repo1', 'repo2'])
        ->and($result->hasMore())->toBeTrue()
        ->and($result->nextUrl)->toBe('/v2/_catalog?n=2&last=repo2');
});

it('handles no Link header in paginated request', function () {
    Http::fake([
        'localhost:5000/v2/_catalog*' => Http::response([
            'repositories' => ['repo1'],
        ]),
    ]);

    $result = $this->driver->getRepositories(limit: 10);
    expect($result)->toBeInstanceOf(PaginatedResult::class)
        ->and($result->hasMore())->toBeFalse();
});

it('passes last parameter for pagination', function () {
    Http::fake([
        'localhost:5000/v2/_catalog*' => Http::response([
            'repositories' => ['repo3'],
        ]),
    ]);

    $this->driver->getRepositories(limit: 2, last: 'repo2');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'last=repo2') && str_contains($request->url(), 'n=2');
    });
});

it('throws on invalid pagination limit', function () {
    $this->driver->getRepositories(limit: 0);
})->throws(PaginationNumberInvalidException::class);

it('gets tags of repository', function () {
    Http::fake([
        'localhost:5000/v2/library/nginx/tags/list' => Http::response([
            'tags' => ['latest', 'v1.0', 'v2.0'],
        ]),
    ]);

    $result = $this->driver->getTagsOfRepository('library/nginx');
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->toArray())->toBe(['latest', 'v1.0', 'v2.0']);
});

it('gets tags with pagination', function () {
    Http::fake([
        'localhost:5000/v2/repo/tags/list*' => Http::response([
            'tags' => ['v1.0'],
        ], 200, [
            'Link' => '</v2/repo/tags/list?n=1&last=v1.0>; rel="next"',
        ]),
    ]);

    $result = $this->driver->getTagsOfRepository('repo', limit: 1);
    expect($result)->toBeInstanceOf(PaginatedResult::class)
        ->and($result->hasMore())->toBeTrue();
});

it('throws on invalid tag pagination limit', function () {
    $this->driver->getTagsOfRepository('repo', limit: -1);
})->throws(PaginationNumberInvalidException::class);

it('handles non-matching Link header format', function () {
    Http::fake([
        'localhost:5000/v2/_catalog*' => Http::response([
            'repositories' => ['repo1'],
        ], 200, [
            'Link' => '<something>; rel="prev"',
        ]),
    ]);

    $result = $this->driver->getRepositories(limit: 10);
    expect($result)->toBeInstanceOf(PaginatedResult::class)
        ->and($result->hasMore())->toBeFalse();
});

it('passes last parameter for tag pagination', function () {
    Http::fake([
        'localhost:5000/v2/repo/tags/list*' => Http::response([
            'tags' => ['v2.0'],
        ]),
    ]);

    $this->driver->getTagsOfRepository('repo', limit: 1, last: 'v1.0');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'last=v1.0') && str_contains($request->url(), 'n=1');
    });
});
