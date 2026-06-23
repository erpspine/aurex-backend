<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class WorkoutController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'workouts' => Workout::query()->latest()->get(),
        ]);
    }

    public function show(Workout $workout): JsonResponse
    {
        return response()->json([
            'workout' => $workout,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $workout = Workout::create($this->validatedData($request));

        return response()->json([
            'message' => 'Workout created successfully.',
            'workout' => $workout,
        ], 201);
    }

    public function update(Request $request, Workout $workout): JsonResponse
    {
        $workout->update($this->validatedData($request, $workout));

        return response()->json([
            'message' => 'Workout updated successfully.',
            'workout' => $workout->fresh(),
        ]);
    }

    public function destroy(Workout $workout): JsonResponse
    {
        $this->deleteStoredFile($workout->cover_image_url);
        $workout->delete();

        return response()->json([
            'message' => 'Workout deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['required', Rule::in(['Weight Loss', 'Muscle Gain', 'Strength', 'Endurance', 'General Fitness'])],
            'workout_level' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced', 'Elite'])],
            'workout_type' => ['required', Rule::in(['Full Body', 'Upper Body', 'Lower Body', 'Push Day', 'Pull Day', 'Leg Day', 'Cardio', 'HIIT'])],
            'duration' => ['nullable', 'string', 'max:100'],
            'calories_burn' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'exercises' => ['nullable', 'array'],
            'exercises.*.exercise_id' => ['nullable', 'string', 'max:255'],
            'exercises.*.name' => ['required_with:exercises', 'string', 'max:255'],
            'exercises.*.body_part' => ['nullable', 'string', 'max:100'],
            'exercises.*.sets' => ['nullable', 'string', 'max:100'],
            'exercises.*.reps' => ['nullable', 'string', 'max:100'],
            'exercises.*.rest' => ['nullable', 'string', 'max:100'],
            'warm_up' => ['nullable', 'string'],
            'trainer_notes' => ['nullable', 'string'],
            'cool_down' => ['nullable', 'string'],
            'cover_image_file' => ['nullable', 'image', 'max:5120'],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
            'show_in_mobile_app' => ['required', 'boolean'],
            'access_type' => ['required', Rule::in(['Free', 'Premium', 'Members Only'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Workout $workout = null): array
    {
        $data = $request->validate($this->rules());
        unset($data['cover_image_file']);

        if ($request->hasFile('cover_image_file')) {
            $this->deleteStoredFile($workout?->cover_image_url);

            $data['cover_image_url'] = Storage::disk('public')->url(
                $request->file('cover_image_file')->store('workouts/covers', 'public')
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
