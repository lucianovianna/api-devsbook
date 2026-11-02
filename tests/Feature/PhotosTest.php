<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class PhotosTest extends ApiTestCase
{
    /** @dataProvider photoRoutes */
    public function test_photos_filter_by_author_and_type_and_preserve_urls_order_and_pagination($ownPhotos)
    {
        $viewer = User::factory()->create();
        $target = $ownPhotos ? $viewer : User::factory()->create();
        $outsider = User::factory()->create();
        $uri = $ownPhotos ? '/api/user/photos' : '/api/user/'.$target->id.'/photos';
        $oldest = $this->createPost($target, [
            'type' => 'photo', 'body' => 'old.jpg', 'created_at' => '2026-01-01 12:00:00',
        ]);
        $middle = $this->createPost($target, [
            'type' => 'photo', 'body' => 'middle.jpg', 'created_at' => '2026-01-02 12:00:00',
        ]);
        $newest = $this->createPost($target, [
            'type' => 'photo', 'body' => 'new.jpg', 'created_at' => '2026-01-03 12:00:00',
        ]);
        $this->createPost($target, ['created_at' => '2026-01-04 12:00:00']);
        $this->createPost($outsider, ['type' => 'photo', 'created_at' => '2026-01-05 12:00:00']);
        $token = $this->tokenFor($viewer);

        $this->apiJson('GET', $uri, [], $token)->assertOk()
            ->assertJsonPath('0.total', 3)->assertJsonPath('0.pageCount', 2)
            ->assertJsonPath('0.currentPage', 0)->assertJsonCount(2, '0.posts')
            ->assertJsonPath('0.posts.0.id', $newest->id)
            ->assertJsonPath('0.posts.0.body', url('media/uploads/new.jpg'))
            ->assertJsonPath('0.posts.0.user.id', $target->id)
            ->assertJsonPath('0.posts.0.mine', $ownPhotos)
            ->assertJsonPath('0.posts.1.id', $middle->id);
        $this->apiJson('GET', $uri.'?page=1', [], $token)->assertOk()
            ->assertJsonPath('0.currentPage', 1)->assertJsonCount(1, '0.posts')
            ->assertJsonPath('0.posts.0.id', $oldest->id);
        $this->apiJson('GET', $uri.'?page=2', [], $token)->assertOk()
            ->assertJsonCount(0, '0.posts')->assertJsonPath('0.total', 3);
    }

    /** @dataProvider photoRoutes */
    public function test_user_without_photos_has_an_empty_list($ownPhotos)
    {
        $viewer = User::factory()->create();
        $target = $ownPhotos ? $viewer : User::factory()->create();
        $uri = $ownPhotos ? '/api/user/photos' : '/api/user/'.$target->id.'/photos';
        $this->createPost($target);

        $this->apiJson('GET', $uri, [], $this->tokenFor($viewer))->assertOk()
            ->assertExactJson([['posts' => [], 'total' => 0, 'pageCount' => 0, 'currentPage' => 0]]);
    }

    public function photoRoutes(): array
    {
        return ['own' => [true], 'third party' => [false]];
    }
}
