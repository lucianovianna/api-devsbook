<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class KnownIssuesTest extends ApiTestCase
{
    public function test_profile_update_should_accept_the_users_unchanged_email()
    {
        $user = User::factory()->create();
        $response = $this->apiJson('PUT', '/api/user', [
            'email' => $user->email, 'name' => 'Updated User',
        ], $this->tokenFor($user));

        $response->assertOk()->assertJsonPath('error', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email, 'name' => 'Updated User']);
    }

    public function test_following_list_should_contain_the_destination_user()
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        DB::table('userrelations')->insert(['user_from' => $viewer->id, 'user_to' => $followed->id]);
        $response = $this->apiJson('GET', '/api/user/'.$viewer->id.'/followers', [], $this->tokenFor($viewer))
            ->assertOk()->assertJsonCount(1, '0.following')->assertJsonCount(0, '0.followers');

        $response->assertExactJson([[
            'followers' => [],
            'following' => [[
                'id' => $followed->id, 'name' => $followed->name,
                'avatar' => url('media/avatars/default.jpg'),
            ]],
        ]]);
    }
}
