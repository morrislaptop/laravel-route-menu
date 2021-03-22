<?php

namespace Morrislaptop\LaravelRouteMenu;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Morrislaptop\LaravelRouteMenu\Commands\LaravelRouteMenuCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_route_menu_table')
            ->hasCommand(LaravelRouteMenuCommand::class);
    }
}
