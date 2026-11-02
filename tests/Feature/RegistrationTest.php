<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\User;
use Tests\ApiTestCase;

class RegistrationTest extends ApiTestCase
{
    private function validUserData(): array
    {
        return [
            'name' => 'Regression User', 'email' => 'registration@example.test',
            'password' => 'password', 'birthdate' => '1995-01-15',
        ];
    }

    public function test_registration_persists_the_user_and_returns_a_usable_token()
    {
        $data = $this->validUserData();
        $response = $this->apiJson('POST', '/api/user', $data)
            ->assertOk()->assertJsonPath('error', false)->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => $data['email'], 'name' => $data['name'], 'birthdate' => $data['birthdate'],
            'avatar' => 'default.jpg', 'cover' => 'cover.jpg',
        ]);
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertNotSame($data['password'], $user->password);
        $this->assertTrue(password_verify($data['password'], $user->password));
        $this->apiJson('GET', '/api/user', [], $response->json('token'))
            ->assertOk()->assertJsonPath('0.data.id', $user->id);
    }

    #[DataProvider('requiredFields')]
    public function test_registration_requires_each_mandatory_field($field)
    {
        $data = $this->validUserData();
        unset($data[$field]);

        $this->apiJson('POST', '/api/user', $data)->assertStatus(400)
            ->assertJsonPath('error', true)->assertJsonStructure(['message' => [$field]]);
        $this->assertDatabaseCount('users', 0);
    }

    public static function requiredFields(): array
    {
        return [['name'], ['email'], ['password'], ['birthdate']];
    }

    public function test_registration_validates_field_formats()
    {
        $this->apiJson('POST', '/api/user', [
            'name' => 'a', 'email' => 'invalid', 'password' => 'a', 'birthdate' => 'invalid',
        ])->assertStatus(400)->assertJsonPath('error', true)
            ->assertJsonStructure(['message' => ['name', 'email', 'password', 'birthdate']]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_a_duplicate_email()
    {
        $data = $this->validUserData();
        $existing = User::factory()->create(['email' => $data['email']]);

        $this->apiJson('POST', '/api/user', $data)->assertStatus(400)
            ->assertJsonPath('error', true)->assertJsonStructure(['message' => ['email']]);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($existing->name, $existing->fresh()->name);
    }
}
