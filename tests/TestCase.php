<?php

namespace Morrislaptop\LaravelRouteMenu\Tests;

use Laravel\Fortify\FortifyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Morrislaptop\LaravelRouteMenu\LaravelRouteMenuServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelRouteMenuServiceProvider::class,
            FortifyServiceProvider::class,
        ];
    }
}
