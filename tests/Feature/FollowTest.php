<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class FollowTest extends ApiTestCase
{
    public function test_follow_toggles_without_removing_other_followers()
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $other = User::factory()->create();
        DB::table('userrelations')->insert(['user_from' => $other->id, 'user_to' => $target->id]);
        $token = $this->tokenFor($viewer);
        $endpoint = '/api/user/'.$target->id.'/follow';

        $this->apiJson('POST', $endpoint, [], $token)->assertOk()
            ->assertExactJson([['isFollowing' => true, 'followers' => 2]]);
        $this->assertDatabaseHas('userrelations', ['user_from' => $viewer->id, 'user_to' => $target->id]);
        $this->assertDatabaseCount('userrelations', 2);

        $this->apiJson('POST', $endpoint, [], $token)->assertOk()
            ->assertExactJson([['isFollowing' => false, 'followers' => 1]]);
        $this->assertDatabaseMissing('userrelations', ['user_from' => $viewer->id, 'user_to' => $target->id]);
        $this->assertDatabaseHas('userrelations', ['user_from' => $other->id, 'user_to' => $target->id]);
        $this->assertDatabaseCount('userrelations', 1);
    }

    public function test_user_cannot_follow_themselves()
    {
        $user = User::factory()->create();

        $this->apiJson('POST', '/api/user/'.$user->id.'/follow', [], $this->tokenFor($user))
            ->assertStatus(400)->assertExactJson(['error' => 'Você não pode seguir a si mesmo']);
        $this->assertDatabaseCount('userrelations', 0);
    }

    public function test_missing_user_cannot_be_followed()
    {
        $this->apiJson('POST', '/api/user/999999/follow', [], $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertExactJson(['error' => 'Usuário inexistente']);
        $this->assertDatabaseCount('userrelations', 0);
    }
}
