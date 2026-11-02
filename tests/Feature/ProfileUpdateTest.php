<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class ProfileUpdateTest extends ApiTestCase
{
    public function test_profile_fields_are_updated_only_for_the_authenticated_user()
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Other User']);
        $data = [
            'name' => 'Updated User', 'email' => 'updated@example.test',
            'birthdate' => '1990-05-20', 'city' => 'Recife', 'work' => 'Developer',
        ];

        $this->apiJson('PUT', '/api/user', $data + ['id' => $other->id], $this->tokenFor($user))
            ->assertOk()->assertJsonPath('error', false)->assertJsonStructure(['messages']);
        $this->assertDatabaseHas('users', $data + ['id' => $user->id]);
        $this->assertSame('Other User', $other->fresh()->name);
        $this->assertNotSame($data['email'], $other->fresh()->email);
    }

    public function test_partial_update_preserves_omitted_fields_and_password()
    {
        $user = User::factory()->create(['city' => 'Recife', 'work' => 'Developer']);
        $original = $user->fresh()->getAttributes();

        $this->apiJson('PUT', '/api/user', ['name' => 'Updated User'], $this->tokenFor($user))
            ->assertOk()->assertJsonPath('error', false);

        $this->assertSame('Updated User', $user->fresh()->name);
        foreach (['email', 'password', 'birthdate', 'city', 'work'] as $field) {
            $this->assertSame($original[$field], $user->fresh()->getAttribute($field));
        }
    }

    public function test_email_owned_by_another_user_is_rejected_without_partial_updates()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->apiJson('PUT', '/api/user', [
            'email' => $other->email, 'name' => 'Should Not Persist',
        ], $this->tokenFor($user))->assertStatus(400)->assertJsonPath('error', true)
            ->assertJsonStructure(['message' => ['email']]);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->name, $user->fresh()->name);
    }

    /** @dataProvider invalidFields */
    public function test_invalid_profile_fields_are_not_persisted($field, $value)
    {
        $user = User::factory()->create();
        $original = $user->fresh()->getAttributes();

        $this->apiJson('PUT', '/api/user', [$field => $value], $this->tokenFor($user))
            ->assertStatus(400)->assertJsonPath('error', true)
            ->assertJsonStructure(['message' => [$field]]);
        $this->assertSame($original, $user->fresh()->getAttributes());
    }

    public function invalidFields(): array
    {
        return [
            'short name' => ['name', 'a'],
            'email format' => ['email', 'invalid'],
            'birthdate format' => ['birthdate', 'invalid'],
            'long city' => ['city', str_repeat('a', 101)],
            'long work' => ['work', str_repeat('a', 101)],
            'short password' => ['password', 'a'],
            'short confirmation' => ['password_confirm', 'a'],
        ];
    }

    public function test_password_change_accepts_the_new_password_and_rejects_the_old_one()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $oldHash = $user->password;

        $this->apiJson('PUT', '/api/user', [
            'password' => 'new-password', 'password_confirm' => 'new-password',
        ], $this->tokenFor($user))->assertOk()->assertJsonPath('error', false);
        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
        $this->assertNotSame($oldHash, $user->fresh()->password);
        $this->assertSame($other->password, $other->fresh()->password);

        $this->apiJson('POST', '/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(400);
        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => $user->email, 'password' => 'new-password',
        ])->assertOk()->assertJsonPath('error', false);
        $this->apiJson('GET', '/api/user', [], $response->json('token'))
            ->assertOk()->assertJsonPath('0.data.id', $user->id);
    }

    /** @dataProvider invalidConfirmations */
    public function test_password_confirmation_failure_does_not_persist_any_changes($confirmation)
    {
        $user = User::factory()->create();
        $data = ['password' => 'new-password', 'name' => 'Should Not Persist'];
        if ($confirmation !== null) {
            $data['password_confirm'] = $confirmation;
        }

        $this->apiJson('PUT', '/api/user', $data, $this->tokenFor($user))
            ->assertStatus(400)->assertExactJson(['error' => true, 'message' => 'As senhas não batem.']);
        $this->assertSame($user->password, $user->fresh()->password);
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function invalidConfirmations(): array
    {
        return ['missing' => [null], 'different' => ['different-password']];
    }
}
