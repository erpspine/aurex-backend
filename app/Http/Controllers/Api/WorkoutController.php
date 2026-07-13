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
        $query = Workout::query()->latest();
        $user = request()->user();

        if ($this->isMemberUser($user)) {
            $member = $user?->member;

            $query
                ->where('publish_status', 'Published')
                ->where('show_in_mobile_app', true)
                ->when($member?->fitness_goal, fn ($workouts, string $goal) => $workouts->where('goal', $goal))
                ->when($member?->workout_level, fn ($workouts, string $level) => $workouts->where('workout_level', $level));
        }

        return response()->json([
            'workouts' => $query->get(),
        ]);
    }

    public function show(Workout $workout): JsonResponse
    {
        $user = request()->user();

        if ($this->isMemberUser($user)) {
            $member = $user?->member;

            abort_if(
                $workout->publish_status !== 'Published'
                    || ! $workout->show_in_mobile_app
                    || ($member?->fitness_goal && $workout->goal !== $member->fitness_goal)
                    || ($member?->workout_level && $workout->workout_level !== $member->workout_level),
                403,
                'This workout is not assigned to your profile.'
            );
        }

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
            'days_count' => ['required', 'integer', 'min:1', 'max:31'],
            'workout_days' => ['nullable', 'array'],
            'workout_days.*.day_number' => ['required_with:workout_days', 'integer', 'min:1', 'max:31'],
            'workout_days.*.title' => ['nullable', 'string', 'max:255'],
            'workout_days.*.notes' => ['nullable', 'string'],
            'workout_days.*.exercises' => ['nullable', 'array'],
            'workout_days.*.exercises.*.exercise_id' => ['nullable', 'string', 'max:255'],
            'workout_days.*.exercises.*.name' => ['required_with:workout_days.*.exercises', 'string', 'max:255'],
            'workout_days.*.exercises.*.body_part' => ['nullable', 'string', 'max:100'],
            'workout_days.*.exercises.*.sets' => ['nullable', 'string', 'max:100'],
            'workout_days.*.exercises.*.reps' => ['nullable', 'string', 'max:100'],
            'workout_days.*.exercises.*.rest' => ['nullable', 'string', 'max:100'],
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

        $data['days_count'] = (int) ($data['days_count'] ?? 1);
        $data['workout_days'] = $this->normalizeWorkoutDays(
            $data['workout_days'] ?? [],
            $data['days_count']
        );
        $data['exercises'] = $this->flattenWorkoutExercises($data['workout_days']);

        if ($request->hasFile('cover_image_file')) {
            $this->deleteStoredFile($workout?->cover_image_url);

            $data['cover_image_url'] = Storage::disk('public')->url(
                $request->file('cover_image_file')->store('workouts/covers', 'public')
            );
        }

        return $data;
    }

    /**
     * @param array<int, mixed> $days
     * @return array<int, array<string, mixed>>
     */
    private function normalizeWorkoutDays(array $days, int $daysCount): array
    {
        $normalized = [];

        for ($dayNumber = 1; $dayNumber <= $daysCount; $dayNumber++) {
            $source = collect($days)->first(
                fn ($day) => is_array($day) && (int) ($day['day_number'] ?? 0) === $dayNumber
            );

            $exercises = is_array($source) && is_array($source['exercises'] ?? null)
                ? $source['exercises']
                : [];

            $normalized[] = [
                'day_number' => $dayNumber,
                'title' => is_array($source) && filled($source['title'] ?? null)
                    ? (string) $source['title']
                    : "Day {$dayNumber}",
                'notes' => is_array($source) ? (string) ($source['notes'] ?? '') : '',
                'exercises' => array_values($exercises),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $days
     * @return array<int, array<string, mixed>>
     */
    private function flattenWorkoutExercises(array $days): array
    {
        $exercises = [];

        foreach ($days as $day) {
            foreach (($day['exercises'] ?? []) as $exercise) {
                if (! is_array($exercise)) {
                    continue;
                }

                $exercises[] = [
                    ...$exercise,
                    'day_number' => $day['day_number'] ?? null,
                    'day_title' => $day['title'] ?? null,
                ];
            }
        }

        return $exercises;
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

    private function isMemberUser(mixed $user): bool
    {
        return $user
            && (
                strtolower(trim((string) ($user->role ?? ''))) === 'member'
                || strtolower(trim((string) ($user->user_type ?? ''))) === 'member'
            );
    }
}
