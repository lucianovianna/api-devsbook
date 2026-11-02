<?php

namespace Tests;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use LogicException;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as private refreshIsolatedDatabase;
    }

    public function refreshDatabase()
    {
        // Check the actual connection before RefreshDatabase calls migrate:fresh.
        if (! app()->environment('testing') || config('database.default') !== 'pgsql') {
            throw new LogicException('API tests require the testing environment and PostgreSQL.');
        }

        $connection = $this->app['db']->connection();

        if ($connection->selectOne('SELECT current_database() AS name')->name !== 'devsbook_test') {
            throw new LogicException('API tests may only reset the devsbook_test database.');
        }

        $this->refreshIsolatedDatabase();
    }

    protected function tokenFor(User $user): string
    {
        return $this->app['tymon.jwt']->fromUser($user);
    }

    protected function apiJson(string $method, string $uri, array $data = [], ?string $token = null): TestResponse
    {
        $this->resetRequestState();
        $headers = $token === null ? [] : ['Authorization' => 'Bearer '.$token];

        return $this->json($method, $uri, $data, $headers);
    }

    protected function apiUpload(string $uri, array $data, ?string $token = null): TestResponse
    {
        $this->resetRequestState();
        $headers = ['Accept' => 'application/json'];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $this->post($uri, $data, $headers);
    }

    private function resetRequestState(): void
    {
        // Real HTTP requests boot fresh guards/controllers; feature tests reuse the app.
        $this->app['auth']->forgetGuards();
        $this->app['tymon.jwt']->unsetToken();

        foreach ($this->app['router']->getRoutes() as $route) {
            $route->controller = null;
        }
    }

    protected function createPost(User $user, array $attributes = []): Post
    {
        $post = new Post();
        $post->forceFill(array_merge([
            'id_user' => $user->id,
            'type' => 'text',
            'body' => 'A regression test post',
            'created_at' => '2026-01-15 12:00:00',
        ], $attributes));
        $post->save();

        return $post;
    }
}
