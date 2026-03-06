<?php

use Cainy\Dockhand\Resources\PaginatedResult;

it('constructs correctly', function () {
    $result = new PaginatedResult(collect(['a', 'b']), '/v2/_catalog?n=2&last=b');
    expect($result->items->toArray())->toBe(['a', 'b'])
        ->and($result->nextUrl)->toBe('/v2/_catalog?n=2&last=b');
});

it('reports hasMore when nextUrl is present', function () {
    $result = new PaginatedResult(collect(['a']), '/next');
    expect($result->hasMore())->toBeTrue();
});

it('reports no more when nextUrl is null', function () {
    $result = new PaginatedResult(collect(['a']), null);
    expect($result->hasMore())->toBeFalse();
});

it('converts to array', function () {
    $result = new PaginatedResult(collect(['a', 'b']), '/next');
    expect($result->toArray())->toBe([
        'items' => ['a', 'b'],
        'nextUrl' => '/next',
    ]);
});

it('implements JsonSerializable', function () {
    $result = new PaginatedResult(collect(), null);
    expect($result->jsonSerialize())->toBe($result->toArray());
});
