<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class LikeTest extends ApiTestCase
{
    public function test_like_toggles_without_removing_other_users_likes()
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $post = $this->createPost($author);
        DB::table('postslikes')->insert([
            'id_post' => $post->id, 'id_user' => $author->id, 'created_at' => '2026-01-15 12:00:00',
        ]);
        $token = $this->tokenFor($viewer);
        $endpoint = '/api/post/'.$post->id.'/like';

        $this->apiJson('POST', $endpoint, [], $token)->assertOk()
            ->assertExactJson([['isLiked' => true, 'likeCout' => 2]]);
        $this->assertDatabaseHas('postslikes', ['id_post' => $post->id, 'id_user' => $viewer->id]);
        $this->assertDatabaseCount('postslikes', 2);

        $this->apiJson('POST', $endpoint, [], $token)->assertOk()
            ->assertExactJson([['isLiked' => false, 'likeCout' => 1]]);
        $this->assertDatabaseMissing('postslikes', ['id_post' => $post->id, 'id_user' => $viewer->id]);
        $this->assertDatabaseHas('postslikes', ['id_post' => $post->id, 'id_user' => $author->id]);
        $this->assertDatabaseCount('postslikes', 1);
    }

    public function test_separate_tokens_like_as_their_respective_users()
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $post = $this->createPost($first);
        $endpoint = '/api/post/'.$post->id.'/like';

        $this->apiJson('POST', $endpoint, [], $this->tokenFor($first))->assertOk()
            ->assertExactJson([['isLiked' => true, 'likeCout' => 1]]);
        $this->apiJson('POST', $endpoint, [], $this->tokenFor($second))->assertOk()
            ->assertExactJson([['isLiked' => true, 'likeCout' => 2]]);
        $this->assertDatabaseHas('postslikes', ['id_post' => $post->id, 'id_user' => $first->id]);
        $this->assertDatabaseHas('postslikes', ['id_post' => $post->id, 'id_user' => $second->id]);
        $this->assertDatabaseCount('postslikes', 2);
    }

    public function test_missing_post_cannot_be_liked()
    {
        $this->apiJson('POST', '/api/post/999999/like', [], $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertExactJson(['error' => 'Post inexistente']);
        $this->assertDatabaseCount('postslikes', 0);
    }
}
