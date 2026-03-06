<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Illuminate\Support\Facades\Http;

it('returns request unchanged', function () {
    $auth = new NullAuthenticator;
    $request = Http::baseUrl('http://localhost');

    $result = $auth->authenticate($request, 'read', 'repo');
    expect($result)->toBe($request);
});

it('flush is a no-op', function () {
    $auth = new NullAuthenticator;
    $auth->flush();
    expect(true)->toBeTrue(); // no exception
});
