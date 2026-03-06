<?php

use Cainy\Dockhand\DockhandManager;
use Cainy\Dockhand\Facades\Dockhand;

it('resolves to DockhandManager', function () {
    expect(Dockhand::getFacadeRoot())->toBeInstanceOf(DockhandManager::class);
});

it('forwards static calls to manager', function () {
    // connection() should return a driver
    $connection = Dockhand::connection();
    expect($connection)->toBeInstanceOf(\Cainy\Dockhand\Drivers\DistributionDriver::class);
});
