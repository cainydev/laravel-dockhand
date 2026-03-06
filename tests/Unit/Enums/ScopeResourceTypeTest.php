<?php

use Cainy\Dockhand\Enums\ScopeResourceType;

it('has registry and repository cases', function () {
    expect(ScopeResourceType::cases())->toHaveCount(2)
        ->and(ScopeResourceType::Registry->value)->toBe('registry')
        ->and(ScopeResourceType::Repository->value)->toBe('repository');
});
