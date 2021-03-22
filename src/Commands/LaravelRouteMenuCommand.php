<?php

namespace Morrislaptop\LaravelRouteMenu\Commands;

use Illuminate\Console\Command;

class LaravelRouteMenuCommand extends Command
{
    public $signature = 'laravel-route-menu';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
