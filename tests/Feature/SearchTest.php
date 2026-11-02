<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\ApiTestCase;

class SearchTest extends ApiTestCase
{
    public function test_search_matches_a_name_fragment_and_exposes_only_public_fields()
    {
        $viewer = User::factory()->create(['name' => 'Viewer User']);
        $match = User::factory()->create(['name' => 'Alice Example']);
        User::factory()->create(['name' => 'Other User']);

        $this->apiJson('GET', '/api/search?txt=lice', [], $this->tokenFor($viewer))
            ->assertOk()->assertExactJson([['users' => [[
                'id' => $match->id, 'name' => 'Alice Example',
                'avatar' => url('media/avatars/default.jpg'),
            ]]]]);
    }

    public function test_search_without_matches_returns_an_empty_list()
    {
        $this->apiJson('GET', '/api/search?txt=NoMatchingName', [],
            $this->tokenFor(User::factory()->create(['name' => 'Viewer User'])))
            ->assertOk()->assertExactJson([['users' => []]]);
    }

    /** @dataProvider invalidSearches */
    public function test_search_requires_a_nonblank_string($query)
    {
        $this->apiJson('GET', '/api/search'.$query, [], $this->tokenFor(User::factory()->create()))
            ->assertStatus(400)->assertJsonStructure(['messages' => ['txt']]);
    }

    public function invalidSearches(): array
    {
        return ['missing' => [''], 'blank' => ['?txt=%20%20'], 'not string' => ['?txt[]=invalid']];
    }
}
