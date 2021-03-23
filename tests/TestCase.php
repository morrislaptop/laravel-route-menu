<?php

namespace Morrislaptop\LaravelRouteMenu\Tests;

use Morrislaptop\LaravelRouteMenu\LaravelRouteMenuServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelRouteMenuServiceProvider::class,
        ];
    }
}
