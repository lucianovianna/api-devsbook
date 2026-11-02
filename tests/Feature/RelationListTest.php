<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class RelationListTest extends ApiTestCase
{
    public function test_follower_list_exposes_the_incoming_users_public_fields()
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $follower = User::factory()->create();
        DB::table('userrelations')->insert(['user_from' => $follower->id, 'user_to' => $target->id]);

        $this->apiJson('GET', '/api/user/'.$target->id.'/followers', [], $this->tokenFor($viewer))
            ->assertOk()->assertExactJson([[
                'followers' => [[
                    'id' => $follower->id, 'name' => $follower->name,
                    'avatar' => url('media/avatars/default.jpg'),
                ]],
                'following' => [],
            ]]);
    }

    public function test_user_without_relations_returns_two_empty_lists()
    {
        $viewer = User::factory()->create();

        $this->apiJson('GET', '/api/user/'.$viewer->id.'/followers', [], $this->tokenFor($viewer))
            ->assertOk()->assertExactJson([['followers' => [], 'following' => []]]);
    }

    public function test_missing_user_cannot_have_relations_listed()
    {
        $this->apiJson('GET', '/api/user/999999/followers', [], $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertExactJson(['error' => 'Usuário inexistente']);
    }
}
