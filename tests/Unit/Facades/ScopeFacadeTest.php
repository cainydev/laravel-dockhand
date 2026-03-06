<?php

use Cainy\Dockhand\Facades\Scope;
use Cainy\Dockhand\Helpers\Scope as ScopeResource;

it('creates a scope via facade', function () {
    $scope = Scope::repository('test/repo');
    expect($scope)->toBeInstanceOf(ScopeResource::class)
        ->and($scope->getResourceName())->toBe('test/repo');
});

it('creates a read scope via facade', function () {
    $scope = Scope::readRepository('test/repo');
    expect($scope->hasPull())->toBeTrue();
});
