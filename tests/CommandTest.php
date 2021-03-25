<?php

namespace Morrislaptop\LaravelRouteMenu\Tests;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class CommandTest extends TestCase
{
    /** @test */
    public function it_outputs_for_a_closure()
    {
        // Arrange.
        Route::get('/some-url', fn (int $answer) => 'Hello World')
            ->name('hello-world')
            ->middleware('web')
            ->domain('google.com');

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput(' 💻 ' . __FILE__)
            ->expectsOutput('GET /some-url')
            ->expectsOutput('🏷️  Name:     hello-world')
            ->expectsOutput('🌏 Domain:   google.com')
            ->expectsOutput('🎬 Action:   Closure')
            ->expectsOutput('🤹 Params:   int $answer')
            ->expectsOutput('🧅 Middles:  web')
            ->expectsOutput('☕️ Code:     ' . __FILE__ . ':' . 16)
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_for_a_controller()
    {
        // Arrange.
        Route::get('/some-url', [TestController::class, 'store']);

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput(' 💻 Morrislaptop\LaravelRouteMenu\Tests')
            ->expectsOutput('🎬 Action:   Morrislaptop\LaravelRouteMenu\Tests\TestController@store')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_for_an_invokable_controller()
    {
        // Arrange.
        Route::get('/some-url', TestInvokableController::class);

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput(' 💻 Morrislaptop\LaravelRouteMenu\Tests')
            ->expectsOutput('🎬 Action:   Morrislaptop\LaravelRouteMenu\Tests\TestInvokableController')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_for_livewire_controller()
    {
        // Arrange.
        Route::get('/some-url', LivewireController::class);

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput(' 💻 Morrislaptop\LaravelRouteMenu\Tests')
            ->expectsOutput('🎬 Action:   Morrislaptop\LaravelRouteMenu\Tests\LivewireController')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_for_a_redirect()
    {
        // Arrange.
        Route::redirect('/old-url', '/new-url');

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput('REDIRECT /old-url')
            ->expectsOutput('👉 /new-url 302')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_for_a_permanent_redirect()
    {
        // Arrange.
        Route::permanentRedirect('/old-url', '/new-url');

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput('REDIRECT /old-url')
            ->expectsOutput('👉 /new-url 301')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_for_a_view()
    {
        // Arrange.
        Route::view('/welcome', 'welcome');

        // Act.
        $this->artisan('route:menu')
            ->expectsOutput('VIEW /welcome')
            ->expectsOutput('🎨 resources/views/welcome.php')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_filters_out_based_on_namespace_or_file()
    {
        // Arrange.
        Route::get('/some-url', LivewireController::class);

        // Act.
        $this->artisan('route:menu --file=gobligook')
            ->expectsOutput('Your application doesn\'t have any routes matching the given criteria.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_error_when_no_routes()
    {
        // Act.
        $this->artisan('route:menu')
            ->expectsOutput('Your application doesn\'t have any routes.')
            ->assertExitCode(0);
    }
}

class TestController extends Controller
{
    public function store(Request $request)
    {
        //
    }
}

class TestInvokableController extends Controller
{
    public function __invoke()
    {
        //
    }
}

class LivewireController extends Component
{
    public function mount()
    {
        //
    }
}
