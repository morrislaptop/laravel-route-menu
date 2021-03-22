<?php

namespace Morrislaptop\LaravelRouteMenu\Commands;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Foundation\Console\RouteListCommand;
use ReflectionFunction;

class LaravelRouteMenuCommand extends RouteListCommand
{
    public $name = 'route:menu';

    public $description = 'Your `route:list`, sir.';

    public function handle()
    {
        $this->router->flushMiddlewareGroups();

        if (empty($this->router->getRoutes())) {
            return $this->error("Your application doesn't have any routes.");
        }

        if (empty($routes = $this->getRoutes())) {
            return $this->error("Your application doesn't have any routes matching the given criteria.");
        }

        $groups = $this->groupRoutes($routes);

        $this->displayGroups($groups);
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return Route[]
     */
    protected function getRoutes()
    {
        return collect($this->router->getRoutes())
            ->filter(fn (Route $route) => $this->filterRawRoute($route));
    }

    /**
     * @param Route[] $routes
     */
    protected function groupRoutes(Collection $routes)
    {
        return [
            [
                'name' => 'App',
                'routes' => $routes,
            ]
        ];
    }

    protected function displayGroups(array $groups)
    {
        foreach ($groups as $group)
        {
            $this->line($group['name']);

            $this->line('-----');

            $group['routes']->map(
                fn (Route $route, $i) => $this->displayRoute($route, $i)
            );
        }
    }

    protected function displayRoute(Route $route, int $i)
    {
        ray($route);

        $methods = collect($route->methods())->reject( fn ($method) => $method === 'HEAD');
        $name = $route->getName() ? " ({$route->getName()})" : '';

        $this->line(
            $i
            . ' - '
            . implode('|', $methods->all())
            . ' '
            . $route->uri()
            . $name
        );

        if ($route->domain()) {
            $this->line("Domain: " . $route->domain());
        }

        if ($route->getActionName()) {
            $this->line("Action: " . $route->getActionName());
        }

        $this->line("Middleware: " . $this->getMiddleware($route));

        $closure = $route->getAction('uses');
        $reflection = new ReflectionFunction($closure);
        $this->line("Code: " . $reflection->getFileName() . ':' . $reflection->getStartLine());

        $this->line('');
        $this->line('');
    }

    protected function getMiddleware($route)
    {
        return collect($this->router->gatherRouteMiddleware($route))->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode(", ");
    }

    /**
     * Get the route information for a given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getRouteInformation(Route $route)
    {
        ray($route);
        return [
            'domain' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri'    => $route->uri(),
            'name'   => $route->getName(),
            'action' => ltrim($route->getActionName(), '\\'),
            'middleware' => $this->getMiddleware($route),
        ];
    }

    /**
     * Filter the route by URI and / or name.
     *
     * @param  array  $route
     * @return array|null
     */
    protected function filterRawRoute(Route $route)
    {
        if (($this->option('name') && ! Str::contains($route->getName(), $this->option('name'))) ||
             $this->option('path') && ! Str::contains($route->uri(), $this->option('path')) ||
             $this->option('method') && ! Str::contains(implode('|', $route->methods()), strtoupper($this->option('method')))) {
            return;
        }

        return $route;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['method', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by method'],
            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by path'],
        ];
    }
}
