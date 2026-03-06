<?php

use Cainy\Dockhand\Events\EventAction;

it('has four cases with correct values', function () {
    expect(EventAction::cases())->toHaveCount(4)
        ->and(EventAction::PULL->value)->toBe('pull')
        ->and(EventAction::PUSH->value)->toBe('push')
        ->and(EventAction::MOUNT->value)->toBe('mount')
        ->and(EventAction::DELETE->value)->toBe('delete');
});
