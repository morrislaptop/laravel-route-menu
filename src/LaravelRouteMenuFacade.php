<?php

namespace Morrislaptop\LaravelRouteMenu;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Morrislaptop\LaravelRouteMenu\LaravelRouteMenu
 */
class LaravelRouteMenuFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-route-menu';
    }
}
