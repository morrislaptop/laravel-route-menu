<?php

namespace Morrislaptop\LaravelRouteMenu\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Route;
use Illuminate\Routing\ViewController;
use Illuminate\Support\Str;
use Livewire\Component;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Console\Input\InputOption;

class LaravelRouteMenuCommand extends RouteListCommand
{
    public $name = 'route:menu';

    public $description = 'Your `route:list`, sir.';

    public function handle()
    {
        $this->router->flushMiddlewareGroups();

        if (empty($this->router->getRoutes()->getRoutes())) {
            $this->error("Your application doesn't have any routes.");

            return;
        }

        if (empty($routes = $this->getRoutes())) {
            $this->error("Your application doesn't have any routes matching the given criteria.");

            return;
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
            ->filter(fn (Route $route) => $this->filterRawRoute($route))
            ->all();
    }

    /**
     * @param Route[] $routes
     * @return array<string, Route[]>
     */
    protected function groupRoutes(array $routes)
    {
        return collect($routes)->groupBy(
            fn (Route $route) => $this->getNamespaceOrFile($route)
        )->all();
    }

    protected function getNamespaceOrFile(Route $route): string
    {
        $reflection = $this->resolveReflection($route);

        if ($reflection instanceof ReflectionMethod) {
            return $reflection->getDeclaringClass()->getNamespaceName();
        }

        if ($reflection instanceof ReflectionClass) {
            return $reflection->getNamespaceName();
        }

        return str_replace(base_path() . '/', '', $reflection->getFileName());
    }

    /**
     * @param array<string, Route[]> $groups
     */
    protected function displayGroups(array $groups): void
    {
        foreach ($groups as $namespace => $routes) {
            $emoji = $this->getEmoji($namespace);

            $this->line("<bg=black;options=bold> $emoji $namespace</>");

            $this->line('-----');

            foreach ($routes as $route) {
                $this->displayRoute($route);
            }
        }
    }

    protected function getEmoji(string $namespace): string
    {
        $map = collect([
            'Fortify' => '🏰',
            'Jetstream' => '🛫',
            'Livewire' => '👀',
            'Spark' => '⚡',
            'Vapor' => '☁️',
            'Dusk' => '🌙',
            'Horizon' => '🌅',
            'Telescope' => '🔭',
            'Cashier' => '💵',
            'Padde' => '💵',
            'Sanctum' => '🔐',
            'Passport' => '🛂',
            'Nova' => '👨‍🚀️',
        ]);

        return $map->first(
            fn ($_e, $search) => Str::contains($namespace, $search),
            '💻'
        );
    }

    protected function displayRoute(Route $route): void
    {
        $reflection = $this->resolveReflection($route);
        $method = $this->resolveMethod($route, $reflection);
        $isSpecialMethod = in_array($method, ['REDIRECT', 'VIEW']);
        $padLength = 10;

        $this->line(
            "<fg=yellow>" . $method . "</>"
            . ' '
            . '/' . ltrim($route->uri(), '/')
        );

        $this->line('');

        ray($route);

        if ($method === 'REDIRECT') {
            $this->line('👉 ' . $route->defaults['destination'] . ' ' . $route->defaults['status']);
        }

        if ($method === 'VIEW') {
            $this->line('🎨 ' . 'resources/views/' . $route->defaults['view'] . '.php');
        }

        if ($route->getName()) {
            $this->line('🏷️  ' . str_pad("Name: ", $padLength)  . $route->getName());
        }

        if ($route->domain()) {
            $this->line('🌏 ' . str_pad("Domain: ", $padLength) . $route->domain());
        }

        if ($route->getActionName()) {
            $this->line('🎬 ' . str_pad("Action: ", $padLength) . $route->getActionName());
        }

        if (! $isSpecialMethod && $params = $route->signatureParameters()) {
            $this->line('🤹 ' . str_pad("Params: ", $padLength) . implode(', ', $this->paramString($params)));
        }

        if ($middleware = $this->getMiddleware($route)) {
            $this->line('🖕 ' . str_pad("Middles: ", $padLength) . $middleware);
        }

        if (! $isSpecialMethod) {
            $fileName = str_replace(base_path() . '/', '', $reflection->getFileName());
            $this->line('☕️ ' . str_pad("Code: ", $padLength) . "<fg=green>" . $fileName . '</>:<fg=green>' . $reflection->getStartLine() . '</>');
        }

        $this->line('');
        $this->line('');
    }

    protected function resolveMethod(Route $route, $reflection)
    {
        $class = $reflection instanceof ReflectionClass ? $reflection : null;
        $class = $reflection instanceof ReflectionMethod ? $reflection->getDeclaringClass() : null;

        if ($class && $class->getName() === RedirectController::class) {
            return 'REDIRECT';
        }

        if ($class && $class->getName() === ViewController::class) {
            return 'VIEW';
        }

        return collect($route->methods())
            ->reject(fn (string $method) => $method === 'HEAD')
            ->implode(', ');
    }

    /**
     * @param ReflectionParameter[] $params
     * @return string[]
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
        })->all();
    }

    protected function resolveReflection(Route $route)
    {
        if (! $route->getAction('controller')) {
            $closure = $route->getAction('uses');

            return new ReflectionFunction($closure);
        }

        [$class, $method] = Str::parseCallback($route->getAction('controller'), '__invoke');

        if (is_a($class, Component::class, true)) {
            return new ReflectionClass($class);
        }

        return new ReflectionMethod($class, $method);
    }

    protected function getMiddleware($route)
    {
        return collect($this->router->gatherRouteMiddleware($route))->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode(", ");
    }

    protected function filterRawRoute(Route $route): ?Route
    {
        if (($this->option('name') && ! Str::contains($route->getName(), $this->option('name'))) ||
             $this->option('path') && ! Str::contains($route->uri(), $this->option('path')) ||
             $this->option('file') && ! Str::contains(strtolower($this->getNamespaceOrFile($route)), strtolower($this->option('file'))) ||
             $this->option('method') && ! Str::contains(implode('|', $route->methods()), strtoupper($this->option('method')))) {
            return null;
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
