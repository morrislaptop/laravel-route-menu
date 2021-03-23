<?php

namespace Morrislaptop\LaravelRouteMenu\Commands;

use Closure;
use Exception;
use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use ReflectionFunctionAbstract;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Foundation\Console\RouteListCommand;
use NunoMaduro\Collision\Highlighter;
use ReflectionNamedType;
use ReflectionParameter;

class LaravelRouteMenuCommand extends RouteListCommand
{
    public $name = 'route:menu';

    public $description = 'Your `route:list`, sir.';

    protected Highlighter $highlighter;

    public function handle(Highlighter $highlighter = null)
    {
        $this->highlighter = $highlighter;

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
        return $routes->groupBy(
            fn (Route $route) => $this->getNamespaceOrFile($route)
        );
    }

    protected function getNamespaceOrFile(Route $route)
    {
        $reflection = $this->resolveReflection($route);

        if ($reflection instanceof ReflectionMethod) {
            return $reflection->getDeclaringClass()->getNamespaceName();
        }

        return str_replace(base_path() . '/', '', $reflection->getFileName());
    }

    protected function displayGroups(Collection $groups)
    {
        foreach ($groups as $namespace => $routes)
        {
            $emoji = $this->getEmoji($namespace);

            $this->line("<bg=black;options=bold> $emoji $namespace </>");

            $this->line('-----');

            $routes->map(
                fn (Route $route, $i) => $this->displayRoute($route, $i)
            );
        }
    }

    protected function getEmoji($namespace)
    {
        switch ($namespace)
        {
            case Str::contains($namespace, 'Fortify'):
                return '🏰';

            case Str::contains($namespace, 'Jetstream'):
                return '🛫';

            case Str::contains($namespace, 'Livewire'):
                return '👀';

            case Str::contains($namespace, 'Spark'):
                return '⚡';

            case Str::contains($namespace, 'Vapor'):
                return '☁️';

            default:
                return '💻';
        }
    }

    protected function displayRoute(Route $route, int $i)
    {
        $methods = collect($route->methods())->reject( fn ($method) => $method === 'HEAD');
        $padLength = 10;

        $this->line(
            "<fg=yellow>" . implode('|', $methods->all()) . "</>"
            . ' '
            . '/' . ltrim($route->uri(), '/')
        );

        $this->line('');

        if ($route->getName()) {
            $this->line('🏷️  ' . str_pad("Name: ", $padLength)  . $route->getName());
        }

        if ($route->domain()) {
            $this->line('🌏 ' . str_pad("Domain: ", $padLength) . $route->domain());
        }

        if ($route->getActionName()) {
            $this->line('🎬 ' . str_pad("Action: ", $padLength) . $route->getActionName());
        }

        if ($params = $route->signatureParameters()) {
            $this->line('🤹 ' . str_pad("Params: ", $padLength) . $this->paramString($params)->implode(', '));
        }

        if ($middleware = $this->getMiddleware($route)) {
            $this->line('🖕 ' . str_pad("Middles: ", $padLength) . $middleware);
        }

        $reflection = $this->resolveReflection($route);
        $fileName = str_replace(base_path() . '/', '', $reflection->getFileName());
        $this->line('☕️ ' . str_pad("Code: ", $padLength) . "<fg=green>" . $fileName . '</>:<fg=green>' . $reflection->getStartLine() . '</>');

        $this->line('');
        $this->line('');
    }

    /**
     * @param $params ReflectionParameter[]
     */
    protected function paramString($params)
    {
        return collect($params)->map(function (ReflectionParameter $param) {
            $str = $param->allowsNull() ? '?' : '';
            $str .= "<fg=magenta>" . ($param->getType() instanceof ReflectionNamedType ? $param->getType()->getName() : 'mixed') . "</>";
            $str .= ' ';
            $str .= '$' . $param->getName();
            $str .= $param->isDefaultValueAvailable() ? ' = <fg=green>' . $param->getDefaultValue() . '</>' : '';

            return $str;
        });
    }

    protected function resolveReflection(Route $route): ReflectionFunctionAbstract
    {
        if ($controller = $route->getAction('controller')) {
            [$class, $method] = Str::parseCallback($controller, '__invoke');
            return new ReflectionMethod($class, $method);
        }
        else {
            $closure = $route->getAction('uses');
            return new ReflectionFunction($closure);
        }
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
             $this->option('file') && ! Str::contains(strtolower($this->getNamespaceOrFile($route)), strtolower($this->option('file'))) ||
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
            ['file', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by namespace or file'],
        ];
    }
}
