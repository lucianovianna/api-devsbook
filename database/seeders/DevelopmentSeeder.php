<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new LogicException('DevelopmentSeeder requires a local or testing environment.');
        }

        DB::transaction(function () {
            $users = [];
            foreach (['ana' => 'Ana Demo', 'bruno' => 'Bruno Demo', 'carla' => 'Carla Demo'] as $key => $name) {
                // Preserve existing users, including any profile/password edits.
                $users[$key] = User::firstOrCreate(['email' => $key.'@example.test'], [
                    'name' => $name,
                    'password' => Hash::make('devsbook-demo'),
                    'birthdate' => '1995-01-15',
                ]);
            }

            $posts = [];
            foreach ([
                ['ana', 'Primeiro post da Ana.', '2026-01-15 10:00:00'],
                ['bruno', 'Um post do Bruno para aparecer no feed.', '2026-01-15 11:00:00'],
                ['carla', 'Olá, Devsbook!', '2026-01-15 12:00:00'],
                ['ana', 'Mais um post para testar a paginação.', '2026-01-15 13:00:00'],
            ] as [$author, $body, $createdAt]) {
                $posts[] = Post::firstOrCreate([
                    'id_user' => $users[$author]->id, 'type' => 'text', 'body' => $body,
                ], ['created_at' => $createdAt]);
            }

            foreach ([['ana', 'bruno'], ['ana', 'carla'], ['bruno', 'ana']] as [$from, $to]) {
                UserRelation::firstOrCreate(['user_from' => $users[$from]->id, 'user_to' => $users[$to]->id]);
            }

            foreach ([['ana', 1], ['bruno', 0]] as [$user, $post]) {
                PostLike::firstOrCreate([
                    'id_user' => $users[$user]->id, 'id_post' => $posts[$post]->id,
                ], ['created_at' => '2026-01-15 14:00:00']);
            }

            foreach ([['ana', 1, 'Gostei do post!'], ['carla', 0, 'Bem-vinda, Ana!']] as [$user, $post, $body]) {
                PostComment::firstOrCreate([
                    'id_user' => $users[$user]->id, 'id_post' => $posts[$post]->id, 'body' => $body,
                ], ['created_at' => '2026-01-15 14:30:00']);
            }
        });
    }
}
