<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BodyPart;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExerciseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'exercises' => Exercise::query()->with('bodyPart')->latest()->get(),
        ]);
    }

    public function show(Exercise $exercise): JsonResponse
    {
        return response()->json([
            'exercise' => $exercise->load('bodyPart'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $exercise = Exercise::create($this->validatedData($request));

        return response()->json([
            'message' => 'Exercise created successfully.',
            'exercise' => $exercise,
        ], 201);
    }

    public function update(Request $request, Exercise $exercise): JsonResponse
    {
        $exercise->update($this->validatedData($request, $exercise));

        return response()->json([
            'message' => 'Exercise updated successfully.',
            'exercise' => $exercise->fresh(),
        ]);
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $this->deleteStoredFile($exercise->image_url);
        $this->deleteStoredFile($exercise->video_url);

        $exercise->delete();

        return response()->json([
            'message' => 'Exercise deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['Equipment Based', 'Body Part Exercise', 'Workout'])],
            'body_part_id' => ['nullable', 'uuid', Rule::exists('body_parts', 'id')],
            'body_part' => ['required_without:body_part_id', 'nullable', 'string', 'max:255'],
            'equipment' => ['required', Rule::in(['Machine', 'Dumbbell', 'Barbell', 'Kettlebell', 'Resistance Bands', 'No Equipment'])],
            'workout_level' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced', 'Elite'])],
            'duration' => ['nullable', 'string', 'max:100'],
            'sets' => ['nullable', 'string', 'max:100'],
            'reps' => ['nullable', 'string', 'max:100'],
            'rest_time' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['Active', 'Draft', 'Hidden', 'Archived'])],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'array'],
            'instructions.*' => ['nullable', 'string', 'max:500'],
            'muscle_tags' => ['nullable', 'array'],
            'muscle_tags.*' => ['nullable', 'string', 'max:100'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,mkv', 'max:51200'],
            'show_in_mobile_app' => ['required', 'boolean'],
            'access_type' => ['required', Rule::in(['Free', 'Premium', 'Members Only'])],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Exercise $exercise = null): array
    {
        $data = $request->validate($this->rules());

        unset($data['image_file'], $data['video_file']);

        if (! empty($data['body_part_id'])) {
            $bodyPart = BodyPart::query()->find($data['body_part_id']);
            $data['body_part'] = $bodyPart?->name ?? $data['body_part'];
        }

        if ($request->hasFile('image_file')) {
            $this->deleteStoredFile($exercise?->image_url);

            $data['image_url'] = Storage::disk('public')->url(
                $request->file('image_file')->store('exercises/images', 'public')
            );
        }

        if ($request->hasFile('video_file')) {
            $this->deleteStoredFile($exercise?->video_url);

            $data['video_url'] = Storage::disk('public')->url(
                $request->file('video_file')->store('exercises/videos', 'public')
            );
        }

        $data['body_part'] = $data['body_part'] ?: 'Full Body';

        return $data;
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url || ! str_contains($url, '/storage/')) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $path), '/');

        if ($storagePath !== '') {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
