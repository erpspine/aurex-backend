<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkoutLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class WorkoutLevelController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'workout_levels' => WorkoutLevel::query()
                ->orderBy('difficulty_rank')
                ->latest()
                ->get(),
        ]);
    }

    public function show(WorkoutLevel $workoutLevel): JsonResponse
    {
        return response()->json([
            'workout_level' => $workoutLevel,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $workoutLevel = WorkoutLevel::create($this->validatedData($request));

        return response()->json([
            'message' => 'Workout level created successfully.',
            'workout_level' => $workoutLevel,
        ], 201);
    }

    public function update(Request $request, WorkoutLevel $workoutLevel): JsonResponse
    {
        $workoutLevel->update($this->validatedData($request, $workoutLevel));

        return response()->json([
            'message' => 'Workout level updated successfully.',
            'workout_level' => $workoutLevel->fresh(),
        ]);
    }

    public function destroy(WorkoutLevel $workoutLevel): JsonResponse
    {
        $this->deleteStoredFile($workoutLevel->cover_image_url);
        $workoutLevel->delete();

        return response()->json([
            'message' => 'Workout level deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'difficulty_rank' => ['required', 'integer', 'min:1', 'max:4'],
            'recommended_duration' => ['nullable', 'string', 'max:100'],
            'intensity' => ['required', Rule::in(['Low', 'Medium', 'High', 'Very High'])],
            'recommended_sets' => ['nullable', 'string', 'max:100'],
            'recommended_reps' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'calories_range' => ['nullable', 'string', 'max:100'],
            'rest_time' => ['nullable', 'string', 'max:100'],
            'training_frequency' => ['nullable', 'string', 'max:100'],
            'suitable_for' => ['nullable', Rule::in(['New Members', 'Regular Members', 'Experienced Members', 'Athletes'])],
            'trainer_instructions' => ['nullable', 'string'],
            'safety_notes' => ['nullable', 'string'],
            'linked_workouts' => ['required', 'integer', 'min:0'],
            'linked_exercises' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['Active', 'Draft', 'Hidden'])],
            'cover_image_file' => ['nullable', 'image', 'max:5120'],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
            'show_in_mobile_app' => ['required', 'boolean'],
            'access_type' => ['required', Rule::in(['Free', 'Premium'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?WorkoutLevel $workoutLevel = null): array
    {
        $data = $request->validate($this->rules());
        unset($data['cover_image_file']);

        if ($request->hasFile('cover_image_file')) {
            $this->deleteStoredFile($workoutLevel?->cover_image_url);

            $data['cover_image_url'] = Storage::disk('public')->url(
                $request->file('cover_image_file')->store('workout-levels/covers', 'public')
            );
        }

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
