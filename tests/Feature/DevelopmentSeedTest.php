<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\ApiTestCase;

class DevelopmentSeedTest extends ApiTestCase
{
    public function test_demo_seed_rejects_a_production_environment()
    {
        $this->app->instance('env', 'production');
        $this->expectException(LogicException::class);

        (new DevelopmentSeeder())->run();
    }

    public function test_demo_seed_is_repeatable_and_preserves_existing_data()
    {
        $existing = User::factory()->create(['name' => 'Existing User']);
        $this->seed();
        $ana = User::where('email', 'ana@example.test')->firstOrFail();
        $originalHash = $ana->password;
        $ana->name = 'Edited Demo User';
        $ana->save();
        $this->seed();

        foreach (['users' => 4, 'posts' => 4, 'userrelations' => 3, 'postslikes' => 2, 'postcomments' => 2] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame('Existing User', $existing->fresh()->name);
        $this->assertSame('Edited Demo User', $ana->fresh()->name);
        $this->assertSame($originalHash, $ana->fresh()->password);
        $this->assertSame(0, DB::table('posts')->where('id_user', $existing->id)->count());

        $token = $this->apiJson('POST', '/api/auth/login', [
            'email' => 'ana@example.test', 'password' => 'devsbook-demo',
        ])->assertOk()->json('token');
        $this->apiJson('GET', '/api/feed', [], $token)
            ->assertOk()->assertJsonPath('0.total', 4)->assertJsonCount(2, '0.posts');
        $this->apiJson('GET', '/api/user/'.$ana->id.'/followers', [], $token)
            ->assertOk()->assertJsonCount(2, '0.following')->assertJsonCount(1, '0.followers');
    }
}
