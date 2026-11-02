<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class UserReadTest extends ApiTestCase
{
    public function test_own_profile_preserves_fields_urls_and_hidden_credentials()
    {
        $user = User::factory()->create([
            'name' => 'Profile User', 'city' => 'Recife', 'work' => 'Developer',
            'avatar' => 'avatar.jpg', 'cover' => 'cover.jpg', 'token' => 'private-token',
        ]);

        $response = $this->apiJson('GET', '/api/user', [], $this->tokenFor($user))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.data.id', $user->id)
            ->assertJsonPath('0.data.name', 'Profile User')
            ->assertJsonPath('0.data.email', $user->email)
            ->assertJsonPath('0.data.birthdate', '1995-01-15')
            ->assertJsonPath('0.data.city', 'Recife')->assertJsonPath('0.data.work', 'Developer')
            ->assertJsonPath('0.data.avatar', url('media/avatars/avatar.jpg'))
            ->assertJsonPath('0.data.cover', url('media/covers/cover.jpg'))
            ->assertJsonPath('0.data.me', true)->assertJsonPath('0.data.isFollowing', false)
            ->assertJsonPath('0.data.followers', 0)->assertJsonPath('0.data.following', 0)
            ->assertJsonPath('0.data.photoCount', 0);

        $this->assertIsInt($response->json('0.data.age'));
        $this->assertArrayNotHasKey('password', $response->json('0.data'));
        $this->assertArrayNotHasKey('token', $response->json('0.data'));
    }

    public function test_third_party_profile_includes_relationship_and_photo_counts()
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $other = User::factory()->create();
        DB::table('userrelations')->insert([
            ['user_from' => $viewer->id, 'user_to' => $target->id],
            ['user_from' => $other->id, 'user_to' => $target->id],
            ['user_from' => $target->id, 'user_to' => $other->id],
        ]);
        $this->createPost($target, ['type' => 'photo', 'body' => 'photo.jpg']);
        $this->createPost($target);
        $this->createPost($other, ['type' => 'photo']);

        $response = $this->apiJson('GET', '/api/user/'.$target->id, [], $this->tokenFor($viewer))
            ->assertOk()->assertJsonPath('0.data.id', $target->id)
            ->assertJsonPath('0.data.me', false)->assertJsonPath('0.data.isFollowing', true)
            ->assertJsonPath('0.data.followers', 2)->assertJsonPath('0.data.following', 1)
            ->assertJsonPath('0.data.photoCount', 1);

        $this->assertArrayNotHasKey('password', $response->json('0.data'));
        $this->assertArrayNotHasKey('token', $response->json('0.data'));
    }

    public function test_missing_profile_returns_the_legacy_error()
    {
        $this->apiJson('GET', '/api/user/999999', [], $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertExactJson(['error' => 'Usuário inexistente.']);
    }
}
