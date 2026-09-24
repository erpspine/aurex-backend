<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_images_and_videos_can_be_created_preserved_and_replaced(): void
    {
        $this->checkMedia('/api/equipment', 'equipment', [
            'name' => 'Test machine', 'category' => 'Machines',
            'linked_exercises' => 0, 'status' => 'Active', 'maintenance_priority' => 'Low',
            'show_in_mobile_app' => '1', 'access_type' => 'Members Only', 'publish_status' => 'Published',
        ], 'operation_video_file', 'operation_video_url');
    }

    public function test_exercise_images_and_videos_can_be_created_preserved_and_replaced(): void
    {
        $this->checkMedia('/api/exercises', 'exercise', [
            'name' => 'Test exercise', 'category' => 'Equipment Based', 'body_part' => 'Chest',
            'equipment' => 'Machine', 'workout_level' => 'Beginner', 'status' => 'Active',
            'show_in_mobile_app' => '1', 'access_type' => 'Members Only', 'publish_status' => 'Published',
        ], 'video_file', 'video_url');
    }

    private function checkMedia(string $endpoint, string $key, array $fields, string $videoField, string $videoUrl): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateApiToken::class);
        Storage::fake('public');
        $files = fn () => [
            'image_file' => UploadedFile::fake()->image('photo.jpg'),
            $videoField => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4'),
        ];
        $created = $this->post($endpoint, $fields + $files(), ['Accept' => 'application/json'])
            ->assertCreated()->json($key);
        $url = $endpoint.'/'.$created['id'];
        foreach (['image_url', $videoUrl] as $field) {
            Storage::disk('public')->assertExists($this->storagePath($created[$field]));
        }
        $this->post($url, $fields + ['_method' => 'PUT'], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath($key.'.image_url', $created['image_url'])
            ->assertJsonPath($key.'.'.$videoUrl, $created[$videoUrl]);
        $updated = $this->post($url, $fields + ['_method' => 'PUT'] + $files(), ['Accept' => 'application/json'])
            ->assertOk()->json($key);
        foreach (['image_url', $videoUrl] as $field) {
            $this->assertNotSame($created[$field], $updated[$field]);
            Storage::disk('public')->assertMissing($this->storagePath($created[$field]));
            Storage::disk('public')->assertExists($this->storagePath($updated[$field]));
        }
        $this->post($url, $fields + ['_method' => 'PUT', 'image_file' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image_file');
        $this->post($url, $fields + ['_method' => 'PUT', $videoField => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors($videoField);
        $this->deleteJson($url)->assertOk();
        foreach (['image_url', $videoUrl] as $field) {
            Storage::disk('public')->assertMissing($this->storagePath($updated[$field]));
        }
    }

    private function storagePath(string $url): string
    {
        return explode('/storage/', $url, 2)[1];
    }
}
