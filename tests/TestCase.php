<?php

namespace Morrislaptop\LaravelRouteMenu\Tests;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Morrislaptop\LaravelRouteMenu\LaravelRouteMenuServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            InertiaServiceProvider::class,
            LaravelRouteMenuServiceProvider::class,
        ];
    }
}
