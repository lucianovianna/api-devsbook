<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrameworkCompatibilityTest extends TestCase
{
    public function test_api_preflight_preserves_cors_without_authentication()
    {
        $this->options('/api/user', [], [
            'Origin' => 'https://client.example',
            'Access-Control-Request-Method' => 'PUT',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeader('Access-Control-Allow-Methods', 'PUT');
    }

    public function test_api_still_limits_requests_to_sixty_per_minute()
    {
        // Use a separate IP so this scenario does not share the other tests' bucket.
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.60']);

        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/ping')->assertOk();
        }

        $this->getJson('/api/ping')->assertStatus(429)->assertHeader('Retry-After');
    }
}
