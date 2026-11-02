<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_api_ping_is_public()
    {
        $this->getJson('/api/ping')->assertOk()->assertExactJson(['pong' => true]);
    }
}
