<?php

namespace Morrislaptop\LaravelRouteMenu;

use Morrislaptop\LaravelRouteMenu\Commands\LaravelRouteMenuCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRouteMenuServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-route-menu')
            ->hasCommand(LaravelRouteMenuCommand::class);
    }
}
