<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\ApiTestCase;

class UploadTest extends ApiTestCase
{
    private $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicDirectory = sys_get_temp_dir().'/devsbook-upload-tests-'.bin2hex(random_bytes(8));
        foreach (['avatars', 'covers', 'uploads'] as $directory) {
            File::makeDirectory($this->publicDirectory.'/media/'.$directory, 0755, true);
        }
        $this->app->instance('path.public', $this->publicDirectory);
        $this->beforeApplicationDestroyed(function () {
            File::deleteDirectory($this->publicDirectory);
        });
    }

    /** @dataProvider supportedImages */
    public function test_upload_processes_real_images_and_persists_the_result($kind, $extension)
    {
        $user = User::factory()->create()->refresh();
        $other = User::factory()->create()->refresh();
        $field = $kind === 'post' ? 'photo' : $kind;
        $uri = $kind === 'post' ? '/api/feed' : '/api/user/'.$kind;
        $data = [$field => UploadedFile::fake()->image('source.'.$extension, 1200, 600)];
        if ($kind === 'post') {
            $data['type'] = 'photo';
        }

        $response = $this->apiUpload($uri, $data, $this->tokenFor($user))
            ->assertOk()->assertJsonPath('error', false);

        if ($kind === 'post') {
            $post = Post::firstOrFail();
            $this->assertSame($user->id, $post->id_user);
            $this->assertSame('photo', $post->type);
            $this->assertDatabaseCount('posts', 1);
            $filename = $post->body;
            $directory = 'uploads';
            $width = 800;
            $height = 400;
            // Extensionless post filenames preserve the source encoding in Image 2.
            $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';
            $this->apiJson('GET', '/api/user/photos', [], $this->tokenFor($user))
                ->assertOk()->assertJsonPath('0.posts.0.body', url('media/uploads/'.$filename));
        } else {
            $filename = $user->fresh()->getAttribute($kind);
            $directory = $kind === 'avatar' ? 'avatars' : 'covers';
            $width = $kind === 'avatar' ? 200 : 850;
            $height = $kind === 'avatar' ? 200 : 310;
            $mime = 'image/jpeg';
            $this->assertNotSame($user->getAttribute($kind), $filename);
            $this->assertSame($other->getAttribute($kind), $other->fresh()->getAttribute($kind));
            $response->assertJsonPath('messages.url', url('media/'.$directory.'/'.$filename));
            $this->apiJson('GET', '/api/user', [], $this->tokenFor($user))
                ->assertOk()->assertJsonPath('0.data.'.$kind, url('media/'.$directory.'/'.$filename));
        }

        $path = $this->publicDirectory.'/media/'.$directory.'/'.$filename;
        $this->assertFileExists($path);
        $metadata = getimagesize($path);
        $this->assertSame($width, $metadata[0]);
        $this->assertSame($height, $metadata[1]);
        $this->assertSame($mime, $metadata['mime']);
        $this->assertCount(1, File::allFiles($this->publicDirectory));
    }

    public function supportedImages(): array
    {
        return [
            'post JPEG' => ['post', 'jpg'], 'post PNG' => ['post', 'png'],
            'avatar JPEG' => ['avatar', 'jpg'], 'avatar PNG' => ['avatar', 'png'],
            'cover JPEG' => ['cover', 'jpg'], 'cover PNG' => ['cover', 'png'],
        ];
    }

    /** @dataProvider rejectedUploads */
    public function test_missing_or_unsupported_file_leaves_database_and_media_unchanged($kind, $invalidFile)
    {
        $user = User::factory()->create()->refresh();
        $field = $kind === 'post' ? 'photo' : $kind;
        $uri = $kind === 'post' ? '/api/feed' : '/api/user/'.$kind;
        $data = $kind === 'post' ? ['type' => 'photo'] : [];
        if ($invalidFile) {
            $data[$field] = UploadedFile::fake()->createWithContent('document.txt', 'not an image');
        }

        $this->apiUpload($uri, $data, $this->tokenFor($user))->assertStatus(400)
            ->assertJsonPath('error', true)->assertJsonStructure(['messages' => [$field]]);
        $this->assertDatabaseCount('posts', 0);
        $this->assertSame($user->avatar, $user->fresh()->avatar);
        $this->assertSame($user->cover, $user->fresh()->cover);
        $this->assertCount(0, File::allFiles($this->publicDirectory));
    }

    public function rejectedUploads(): array
    {
        return [
            'post missing' => ['post', false], 'post invalid' => ['post', true],
            'avatar missing' => ['avatar', false], 'avatar invalid' => ['avatar', true],
            'cover missing' => ['cover', false], 'cover invalid' => ['cover', true],
        ];
    }
}
