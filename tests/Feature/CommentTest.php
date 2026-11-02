<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class CommentTest extends ApiTestCase
{
    public function test_comment_is_persisted_as_the_authenticated_user_and_appears_in_the_feed()
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $this->createPost($author);

        $this->apiJson('POST', '/api/post/'.$post->id.'/comment', [
            'txt' => 'A new comment', 'id_user' => $author->id,
        ], $this->tokenFor($commenter))->assertOk()
            ->assertExactJson([['Comentário enviado com sucesso']]);
        $this->assertDatabaseHas('postcomments', [
            'id_post' => $post->id, 'id_user' => $commenter->id, 'body' => 'A new comment',
        ]);
        $this->assertDatabaseCount('postcomments', 1);
        $this->apiJson('GET', '/api/feed', [], $this->tokenFor($author))->assertOk()
            ->assertJsonPath('0.posts.0.comments.0.body', 'A new comment')
            ->assertJsonPath('0.posts.0.comments.0.user.id', $commenter->id);
    }

    /** @dataProvider invalidComments */
    public function test_invalid_comment_is_not_persisted($data)
    {
        $user = User::factory()->create();
        $post = $this->createPost($user);
        $this->apiJson('POST', '/api/post/'.$post->id.'/comment', $data, $this->tokenFor($user))
            ->assertStatus(400)->assertJsonStructure(['messages' => ['txt']]);
        $this->assertDatabaseCount('postcomments', 0);
    }

    public function invalidComments(): array
    {
        return ['missing' => [[]], 'blank' => [['txt' => '   ']], 'not string' => [['txt' => ['invalid']]]];
    }

    public function test_missing_post_cannot_receive_a_comment()
    {
        $this->apiJson('POST', '/api/post/999999/comment', ['txt' => 'A comment'],
            $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertExactJson(['error' => 'Post inexistente']);
        $this->assertDatabaseCount('postcomments', 0);
    }
}
