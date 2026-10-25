<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberDietPreference;
use App\Models\MemberFoodEntry;
use App\Models\MemberWaterLog;
use App\Models\MemberWorkoutLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Activity the signed-in user records in the mobile app: completed workout
 * sessions, the food diary, water intake and diet preferences. Every query is
 * scoped to the authenticated user.
 */
class MemberActivityController extends Controller
{
    private const MEAL_TYPES = ['Breakfast', 'Lunch', 'Snack', 'Dinner'];

    public function workouts(Request $request): JsonResponse
    {
        $logs = MemberWorkoutLog::query()
            ->where('user_id', $request->user()->id)
            ->latest('completed_at')
            ->limit(1000)
            ->get();

        return response()->json([
            'workout_logs' => $logs->map->toApi()->values(),
        ]);
    }

    public function storeWorkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:64'],
            'exercise_id' => ['nullable', 'string', 'max:255'],
            'exercise_name' => ['required', 'string', 'max:255'],
            'body_part' => ['nullable', 'string', 'max:100'],
            'completed_at' => ['required', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'sets' => ['present', 'array', 'max:50'],
            'sets.*.reps' => ['required', 'integer', 'min:0', 'max:1000'],
            'sets.*.weight_kg' => ['required', 'numeric', 'min:0', 'max:2000'],
        ]);

        // Stored in the app timezone like every other timestamp in the API.
        $data['completed_at'] = Carbon::parse($data['completed_at'])
            ->setTimezone(config('app.timezone'));

        $log = $this->createOnce(MemberWorkoutLog::class, $request, $data);

        return response()->json(['workout_log' => $log->toApi()], 201);
    }

    public function food(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $entries = MemberFoodEntry::query()
            ->where('user_id', $request->user()->id)
            ->when($data['from'] ?? null, fn ($query, $from) => $query->whereDate('entry_date', '>=', $from))
            ->when($data['to'] ?? null, fn ($query, $to) => $query->whereDate('entry_date', '<=', $to))
            ->orderBy('entry_date')
            ->orderBy('entry_time')
            ->get();

        return response()->json([
            'food_entries' => $entries->map->toApi()->values(),
        ]);
    }

    public function storeFood(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:64'],
            'date' => ['required', 'date_format:Y-m-d'],
            'meal_type' => ['required', Rule::in(self::MEAL_TYPES)],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:255'],
            'servings' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'amount_grams' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'calories' => ['required', 'numeric', 'min:0', 'max:100000'],
            'protein' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'carbs' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'fat' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        $entry = $this->createOnce(MemberFoodEntry::class, $request, [
            'client_id' => $data['client_id'] ?? null,
            'entry_date' => $data['date'],
            'meal_type' => $data['meal_type'],
            'entry_time' => $data['time'],
            'name' => $data['name'],
            'servings' => $data['servings'],
            'amount_grams' => $data['amount_grams'] ?? 0,
            'calories' => $data['calories'],
            'protein' => $data['protein'] ?? 0,
            'carbs' => $data['carbs'] ?? 0,
            'fat' => $data['fat'] ?? 0,
        ]);

        return response()->json(['food_entry' => $entry->toApi()], 201);
    }

    public function destroyFood(Request $request, string $foodEntry): JsonResponse
    {
        MemberFoodEntry::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($foodEntry)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Food entry removed.']);
    }

    public function water(Request $request): JsonResponse
    {
        $logs = MemberWaterLog::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('log_date')
            ->get();

        return response()->json([
            'water_logs' => $logs->map(fn (MemberWaterLog $log): array => [
                'date' => $log->log_date->toDateString(),
                'cups' => $log->cups,
            ])->values(),
        ]);
    }

    public function updateWater(Request $request, string $date): JsonResponse
    {
        validator(['date' => $date], ['date' => ['date_format:Y-m-d']])->validate();
        $data = $request->validate([
            'cups' => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        // Matched by calendar day: some drivers store date columns with a time.
        $log = MemberWaterLog::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('log_date', $date)
            ->first()
            ?? new MemberWaterLog(['user_id' => $request->user()->id, 'log_date' => $date]);
        $log->cups = $data['cups'];
        $log->save();

        return response()->json([
            'water_log' => ['date' => $date, 'cups' => $log->cups],
        ]);
    }

    public function dietPreferences(Request $request): JsonResponse
    {
        $preference = MemberDietPreference::query()
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'diet_preferences' => $this->preferencePayload($preference),
        ]);
    }

    public function updateDietPreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'calorie_goal' => ['nullable', 'integer', 'min:800', 'max:10000'],
            'diet_plan_id' => ['nullable', 'uuid', Rule::exists('diet_plans', 'id')],
        ]);

        $preference = MemberDietPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'calorie_goal' => $data['calorie_goal'] ?? null,
                'diet_plan_id' => $data['diet_plan_id'] ?? null,
            ],
        );

        return response()->json([
            'diet_preferences' => $this->preferencePayload($preference),
        ]);
    }

    /**
     * Creates a record for the user, or returns the existing one when the app
     * retries an upload with the same client id.
     *
     * @template TModel of MemberWorkoutLog|MemberFoodEntry
     *
     * @param  class-string<TModel>  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private function createOnce(string $model, Request $request, array $attributes)
    {
        $userId = $request->user()->id;
        $clientId = $attributes['client_id'] ?? null;

        if ($clientId === null) {
            return $model::query()->create([...$attributes, 'user_id' => $userId]);
        }

        return $model::query()->firstOrCreate(
            ['user_id' => $userId, 'client_id' => $clientId],
            $attributes,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function preferencePayload(?MemberDietPreference $preference): array
    {
        return [
            'calorie_goal' => $preference?->calorie_goal,
            'diet_plan_id' => $preference?->diet_plan_id,
        ];
    }
}
