<?php

use Cainy\Dockhand\Enums\RegistryApiVersion;

it('has V1 and V2 cases', function () {
    expect(RegistryApiVersion::cases())->toHaveCount(2)
        ->and(RegistryApiVersion::V1->value)->toBe('registry/1.0')
        ->and(RegistryApiVersion::V2->value)->toBe('registry/2.0');
});
