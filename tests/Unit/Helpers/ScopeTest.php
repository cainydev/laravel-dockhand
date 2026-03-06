<?php

use Cainy\Dockhand\Enums\ScopeResourceType;
use Cainy\Dockhand\Helpers\Scope;

it('creates a catalog scope', function () {
    $scope = (new Scope)->catalog();
    expect($scope->getResourceType())->toBe(ScopeResourceType::Registry)
        ->and($scope->getResourceName())->toBe('catalog')
        ->and($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeTrue()
        ->and($scope->getActions())->toBe(['*']);
});

it('creates a repository scope', function () {
    $scope = (new Scope)->repository('library/nginx');
    expect($scope->getResourceType())->toBe(ScopeResourceType::Repository)
        ->and($scope->getResourceName())->toBe('library/nginx')
        ->and($scope->hasPull())->toBeFalse()
        ->and($scope->hasPush())->toBeFalse();
});

it('creates a read repository scope', function () {
    $scope = (new Scope)->readRepository('library/nginx');
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeFalse();
});

it('creates a write repository scope', function () {
    $scope = (new Scope)->writeRepository('library/nginx');
    expect($scope->hasPush())->toBeTrue()
        ->and($scope->hasPull())->toBeFalse();
});

it('creates a delete repository scope', function () {
    $scope = (new Scope)->deleteRepository('library/nginx');
    expect($scope->hasDelete())->toBeTrue()
        ->and($scope->hasPull())->toBeFalse();
});

it('supports fluent allowPushAndPull', function () {
    $scope = (new Scope)->repository('test')->allowPushAndPull();
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeFalse();
});

it('supports allowAll', function () {
    $scope = (new Scope)->repository('test')->allowAll();
    expect($scope->getActions())->toBe(['*']);
});

it('supports allowNone', function () {
    $scope = (new Scope)->repository('test')->allowAll()->allowNone();
    expect($scope->getActions())->toBe([])
        ->and($scope->hasPull())->toBeFalse();
});

it('converts to string correctly', function () {
    $scope = (new Scope)->readRepository('library/nginx');
    expect($scope->toString())->toBe('repository:library/nginx:pull')
        ->and((string) $scope)->toBe('repository:library/nginx:pull');
});

it('converts to string with multiple actions', function () {
    $scope = (new Scope)->repository('test')->allowPull()->allowPush();
    expect($scope->toString())->toBe('repository:test:pull,push');
});

it('converts to string with all actions as wildcard', function () {
    $scope = (new Scope)->repository('test')->allowAll();
    expect($scope->toString())->toBe('repository:test:*');
});

it('parses from string', function () {
    $scope = Scope::fromString('repository:library/nginx:pull');
    expect($scope->getResourceType())->toBe(ScopeResourceType::Repository)
        ->and($scope->getResourceName())->toBe('library/nginx')
        ->and($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeFalse();
});

it('parses from string with multiple actions', function () {
    $scope = Scope::fromString('repository:test/repo:pull,push');
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeFalse();
});

it('parses wildcard action', function () {
    $scope = Scope::fromString('registry:catalog:*');
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeTrue();
});

it('throws on empty string', function () {
    Scope::fromString('');
})->throws(InvalidArgumentException::class);

it('throws on invalid format', function () {
    Scope::fromString('invalid');
})->throws(InvalidArgumentException::class);

it('throws on invalid resource type', function () {
    Scope::fromString('unknown:test:pull');
})->throws(InvalidArgumentException::class);

it('converts to array', function () {
    $scope = (new Scope)->readRepository('test');
    $array = $scope->toArray();
    expect($array)->toBe([
        'type' => 'repository',
        'name' => 'test',
        'actions' => ['pull'],
    ]);
});

it('converts to JSON', function () {
    $scope = (new Scope)->readRepository('test');
    expect($scope->toJson())->toBeJson();
});

it('implements JsonSerializable', function () {
    $scope = (new Scope)->readRepository('test');
    expect($scope->jsonSerialize())->toBe($scope->toArray());
});

it('supports setActions with property hook', function () {
    $scope = (new Scope)->repository('test');
    $scope->setActions(['pull', 'push']);
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeFalse();
});

it('supports setActions with wildcard', function () {
    $scope = (new Scope)->repository('test');
    $scope->setActions(['*']);
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeTrue();
});

it('parses delete action from string', function () {
    $scope = Scope::fromString('repository:test/repo:delete');
    expect($scope->hasDelete())->toBeTrue()
        ->and($scope->hasPull())->toBeFalse()
        ->and($scope->hasPush())->toBeFalse();
});

it('parses all actions from string', function () {
    $scope = Scope::fromString('repository:test:pull,push,delete');
    expect($scope->hasPull())->toBeTrue()
        ->and($scope->hasPush())->toBeTrue()
        ->and($scope->hasDelete())->toBeTrue();
});
