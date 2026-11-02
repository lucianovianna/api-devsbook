<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\ApiTestCase;

class FeedTest extends ApiTestCase
{
    public function test_empty_feed_preserves_the_response_envelope_and_default_page()
    {
        $this->apiJson('GET', '/api/feed', [], $this->tokenFor(User::factory()->create()))
            ->assertOk()->assertExactJson([[
                'posts' => [], 'total' => 0, 'pageCount' => 0, 'currentPage' => 0,
            ]]);
    }

    public function test_feed_selects_own_and_followed_posts_in_descending_order_with_pagination()
    {
        $viewer = User::factory()->create();
        $followed = User::factory()->create();
        $outsider = User::factory()->create();
        DB::table('userrelations')->insert(['user_from' => $viewer->id, 'user_to' => $followed->id]);
        $oldest = $this->createPost($viewer, ['created_at' => '2026-01-01 12:00:00']);
        $middle = $this->createPost($followed, ['created_at' => '2026-01-02 12:00:00']);
        $newest = $this->createPost($viewer, ['created_at' => '2026-01-03 12:00:00']);
        $this->createPost($outsider, ['created_at' => '2026-01-04 12:00:00']);
        $token = $this->tokenFor($viewer);

        $this->apiJson('GET', '/api/feed', [], $token)->assertOk()
            ->assertJsonPath('0.total', 3)->assertJsonPath('0.pageCount', 2)
            ->assertJsonPath('0.currentPage', 0)->assertJsonCount(2, '0.posts')
            ->assertJsonPath('0.posts.0.id', $newest->id)->assertJsonPath('0.posts.0.mine', true)
            ->assertJsonPath('0.posts.1.id', $middle->id)->assertJsonPath('0.posts.1.mine', false);

        $this->apiJson('GET', '/api/feed?page=1', [], $token)->assertOk()
            ->assertJsonPath('0.currentPage', 1)->assertJsonPath('0.total', 3)
            ->assertJsonCount(1, '0.posts')->assertJsonPath('0.posts.0.id', $oldest->id);

        $this->apiJson('GET', '/api/feed?page=2', [], $token)->assertOk()
            ->assertJsonPath('0.currentPage', 2)->assertJsonCount(0, '0.posts');
    }

    public function test_feed_includes_authors_likes_and_comment_authors()
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $post = $this->createPost($viewer);
        DB::table('postslikes')->insert([
            ['id_post' => $post->id, 'id_user' => $viewer->id, 'created_at' => '2026-01-15 12:00:00'],
            ['id_post' => $post->id, 'id_user' => $other->id, 'created_at' => '2026-01-15 12:00:00'],
        ]);
        $commentId = DB::table('postcomments')->insertGetId([
            'id_post' => $post->id, 'id_user' => $other->id,
            'body' => 'A comment', 'created_at' => '2026-01-15 13:00:00',
        ]);

        $response = $this->apiJson('GET', '/api/feed', [], $this->tokenFor($viewer))
            ->assertOk()->assertJsonPath('0.posts.0.body', $post->body)
            ->assertJsonPath('0.posts.0.type', 'text')
            ->assertJsonPath('0.posts.0.created_at', '2026-01-15 12:00:00')
            ->assertJsonPath('0.posts.0.user.id', $viewer->id)
            ->assertJsonPath('0.posts.0.user.avatar', url('media/avatars/default.jpg'))
            ->assertJsonPath('0.posts.0.likeCount', 2)->assertJsonPath('0.posts.0.liked', true)
            ->assertJsonCount(1, '0.posts.0.comments')
            ->assertJsonPath('0.posts.0.comments.0.id', $commentId)
            ->assertJsonPath('0.posts.0.comments.0.body', 'A comment')
            ->assertJsonPath('0.posts.0.comments.0.user.id', $other->id);

        $this->assertArrayNotHasKey('password', $response->json('0.posts.0.user'));
        $this->assertArrayNotHasKey('token', $response->json('0.posts.0.user'));
        $this->assertArrayNotHasKey('password', $response->json('0.posts.0.comments.0.user'));
        $this->assertArrayNotHasKey('token', $response->json('0.posts.0.comments.0.user'));
    }

    public function test_unliked_post_has_zero_likes_and_no_comments()
    {
        $viewer = User::factory()->create();
        $this->createPost($viewer);

        $this->apiJson('GET', '/api/feed', [], $this->tokenFor($viewer))->assertOk()
            ->assertJsonPath('0.posts.0.likeCount', 0)->assertJsonPath('0.posts.0.liked', false)
            ->assertJsonCount(0, '0.posts.0.comments');
    }

    public function test_user_feed_filters_by_the_requested_user()
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $mine = $this->createPost($viewer);
        $theirs = $this->createPost($target);
        $token = $this->tokenFor($viewer);

        $this->apiJson('GET', '/api/user/feed', [], $token)->assertOk()
            ->assertJsonPath('0.total', 1)->assertJsonCount(1, '0.posts')
            ->assertJsonPath('0.posts.0.id', $mine->id);
        $this->apiJson('GET', '/api/user/'.$target->id.'/feed', [], $token)->assertOk()
            ->assertJsonPath('0.total', 1)->assertJsonCount(1, '0.posts')
            ->assertJsonPath('0.posts.0.id', $theirs->id);
    }

    public function test_creating_a_text_post_persists_the_authenticated_author()
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->apiJson('POST', '/api/feed', [
            'type' => 'text', 'body' => 'New post', 'id_user' => $other->id,
        ], $this->tokenFor($viewer))->assertOk()
            ->assertExactJson(['error' => false, 'messages' => ['Post enviado com sucesso']]);
        $this->assertDatabaseHas('posts', ['id_user' => $viewer->id, 'body' => 'New post', 'type' => 'text']);
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_invalid_text_post_is_not_persisted()
    {
        $this->apiJson('POST', '/api/feed', ['type' => 'text'], $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertJsonPath('error', true)
            ->assertJsonStructure(['messages' => ['body']]);
        $this->assertDatabaseCount('posts', 0);
    }
}
