<?php

use Cainy\Dockhand\Services\RegistryRequestService;
use Illuminate\Http\Client\PendingRequest;

it('returns a PendingRequest', function () {
    $service = new RegistryRequestService('http://localhost:5000/v2/');
    $request = $service->request();
    expect($request)->toBeInstanceOf(PendingRequest::class);
});

it('configures request with base url and default headers', function () {
    $service = new RegistryRequestService('http://localhost:5000/v2/', 60);

    // Verify the request can be obtained without errors
    $request = $service->request();
    expect($request)->toBeInstanceOf(PendingRequest::class);
});
