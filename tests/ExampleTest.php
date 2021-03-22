<?php

namespace Morrislaptop\LaravelRouteMenu\Tests;

class ExampleTest extends TestCase
{
    /** @test */
    public function true_generates_lists()
    {
        // Arrange.

        // Act.
        $response = $this->artisan('route:menu');

        // Assert.
        $response->assertExitCode(0);
    }
}
