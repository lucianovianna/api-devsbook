<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class AuthenticationTest extends ApiTestCase
{
    public function test_login_returns_a_token_that_authenticates_the_user()
    {
        $user = User::factory()->create();

        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('error', false)->assertJsonStructure(['token']);

        $this->assertIsString($response->json('token'));
        $this->apiJson('GET', '/api/user', [], $response->json('token'))
            ->assertOk()->assertJsonPath('0.data.id', $user->id);
    }

    public function test_wrong_password_is_rejected()
    {
        $user = User::factory()->create();

        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(400)->assertExactJson(['error' => 'Login e/ou senha incorretos']);

        $this->assertArrayNotHasKey('token', $response->json());
    }

    public function test_unknown_email_is_rejected()
    {
        $this->apiJson('POST', '/api/auth/login', [
            'email' => 'missing@example.test', 'password' => 'password',
        ])->assertStatus(400)->assertExactJson(['error' => 'Login e/ou senha incorretos']);
    }

    public function test_login_requires_valid_credentials()
    {
        $this->apiJson('POST', '/api/auth/login', ['email' => 'invalid', 'password' => 'a'])
            ->assertStatus(400)->assertJsonPath('error', true)
            ->assertJsonStructure(['message' => ['email', 'password']]);
    }

    /** @dataProvider protectedEndpoints */
    public function test_protected_endpoints_require_a_token($method, $uri)
    {
        $this->apiJson($method, $uri)->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function protectedEndpoints(): array
    {
        return [
            'profile' => ['GET', '/api/user'],
            'feed' => ['GET', '/api/feed'],
            'like' => ['POST', '/api/post/999999/like'],
            'follow' => ['POST', '/api/user/999999/follow'],
            'logout' => ['POST', '/api/auth/logout'],
            'refresh' => ['POST', '/api/auth/refresh'],
            'third party profile' => ['GET', '/api/user/999999'],
            'profile update' => ['PUT', '/api/user'],
            'avatar' => ['POST', '/api/user/avatar'],
            'cover' => ['POST', '/api/user/cover'],
            'post creation' => ['POST', '/api/feed'],
            'own feed' => ['GET', '/api/user/feed'],
            'third party feed' => ['GET', '/api/user/999999/feed'],
            'own photos' => ['GET', '/api/user/photos'],
            'third party photos' => ['GET', '/api/user/999999/photos'],
            'relations' => ['GET', '/api/user/999999/followers'],
            'comment' => ['POST', '/api/post/999999/comment'],
            'search' => ['GET', '/api/search?txt=User'],
        ];
    }

    public function test_invalid_token_is_rejected()
    {
        $this->apiJson('GET', '/api/user', [], 'not-a-jwt')->assertStatus(401);
    }

    public function test_expired_token_is_rejected()
    {
        $this->app['tymon.jwt']->factory()->setTTL(1);
        $token = $this->tokenFor(User::factory()->create());
        $this->travel(2)->minutes();

        try {
            $this->apiJson('GET', '/api/user', [], $token)->assertStatus(401);
        } finally {
            $this->travelBack();
        }
    }

    public function test_logout_revokes_the_token()
    {
        $token = $this->tokenFor(User::factory()->create());
        $this->apiJson('GET', '/api/user', [], $token)->assertOk();
        $this->apiJson('POST', '/api/auth/logout', [], $token)
            ->assertOk()->assertExactJson(['error' => false]);
        $this->apiJson('GET', '/api/user', [], $token)->assertStatus(401);
    }

    public function test_refresh_replaces_the_token_and_revokes_the_previous_one()
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $response = $this->apiJson('POST', '/api/auth/refresh', [], $token)
            ->assertOk()->assertJsonPath('error', false)->assertJsonStructure(['token']);

        $newToken = $response->json('token');
        $this->assertIsString($newToken);
        $this->assertNotSame($token, $newToken);
        $this->apiJson('GET', '/api/user', [], $token)->assertStatus(401);
        $this->apiJson('GET', '/api/user', [], $newToken)
            ->assertOk()->assertJsonPath('0.data.id', $user->id);
    }

    public function test_browser_request_redirects_to_the_legacy_unauthorized_endpoint()
    {
        $this->get('/api/user')->assertRedirect('/api/401');
        $this->get('/api/401')->assertStatus(401)
            ->assertExactJson(['error' => 'Não autorizado']);
    }
}
