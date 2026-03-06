<?php

namespace Cainy\Dockhand\Tests;

use Cainy\Dockhand\DockhandServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            DockhandServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dockhand.default', 'default');
        $app['config']->set('dockhand.connections.default', [
            'driver' => 'distribution',
            'base_uri' => 'http://localhost:5000/v2/',
            'auth' => ['driver' => 'null'],
            'logging' => ['driver' => null],
        ]);
        $app['config']->set('dockhand.notifications.enabled', false);
    }
}
