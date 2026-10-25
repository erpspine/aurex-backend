<?php

namespace Tests\Feature;

use App\Models\DietPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberActivityTest extends TestCase
{
    use RefreshDatabase;

    private function memberHeaders(string $email = 'activity@example.test'): array
    {
        $this->postJson('/api/register', [
            'name' => 'Activity Member', 'email' => $email,
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertCreated();

        User::where('email', $email)->update(['email_verified_at' => now()]);
        $token = $this->postJson('/api/login', [
            'email' => $email, 'password' => 'Password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_workout_logs_are_saved_once_and_scoped_to_the_user(): void
    {
        $headers = $this->memberHeaders();
        $log = [
            'client_id' => 'device-1',
            'exercise_id' => 'ex-1',
            'exercise_name' => 'Back Squat',
            'body_part' => 'Legs',
            'completed_at' => '2026-10-22T05:30:00Z',
            'duration_seconds' => 900,
            'sets' => [['reps' => 8, 'weight_kg' => 60], ['reps' => 6, 'weight_kg' => 70.5]],
        ];

        $first = $this->postJson('/api/activity/workouts', $log, $headers)
            ->assertCreated()
            ->assertJsonPath('workout_log.exercise_name', 'Back Squat')
            ->assertJsonPath('workout_log.sets.1.weight_kg', 70.5);
        // A retried upload returns the same record instead of a duplicate.
        $this->postJson('/api/activity/workouts', $log, $headers)
            ->assertCreated()
            ->assertJsonPath('workout_log.id', $first->json('workout_log.id'));

        $this->getJson('/api/activity/workouts', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'workout_logs');

        $other = $this->memberHeaders('other@example.test');
        $this->getJson('/api/activity/workouts', $other)->assertOk()->assertJsonCount(0, 'workout_logs');
    }

    public function test_food_diary_create_list_and_delete(): void
    {
        $headers = $this->memberHeaders();
        $entry = $this->postJson('/api/activity/food', [
            'client_id' => 'food-1',
            'date' => '2026-10-22',
            'meal_type' => 'Breakfast',
            'time' => '07:30',
            'name' => 'Oats',
            'servings' => 1.5,
            'calories' => 450,
            'protein' => 20,
        ], $headers)->assertCreated()->assertJsonPath('food_entry.calories', 450)->json('food_entry');

        $this->getJson('/api/activity/food?from=2026-10-22&to=2026-10-22', $headers)
            ->assertOk()
            ->assertJsonPath('food_entries.0.name', 'Oats')
            ->assertJsonPath('food_entries.0.time', '07:30');

        $other = $this->memberHeaders('other@example.test');
        $this->deleteJson('/api/activity/food/'.$entry['id'], [], $other)->assertNotFound();

        $this->deleteJson('/api/activity/food/'.$entry['id'], [], $headers)->assertOk();
        $this->getJson('/api/activity/food', $headers)->assertJsonCount(0, 'food_entries');
    }

    public function test_food_entry_validation(): void
    {
        $this->postJson('/api/activity/food', [
            'date' => 'yesterday', 'meal_type' => 'Brunch', 'time' => '7am', 'name' => '', 'servings' => 0, 'calories' => -1,
        ], $this->memberHeaders())->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'meal_type', 'time', 'name', 'servings', 'calories']);
    }

    public function test_water_and_diet_preferences_upsert(): void
    {
        $headers = $this->memberHeaders();

        $this->putJson('/api/activity/water/2026-10-22', ['cups' => 3], $headers)->assertOk();
        $this->putJson('/api/activity/water/2026-10-22', ['cups' => 5], $headers)->assertOk();
        $this->getJson('/api/activity/water', $headers)
            ->assertJsonCount(1, 'water_logs')
            ->assertJsonPath('water_logs.0.cups', 5);

        $this->getJson('/api/activity/diet-preferences', $headers)
            ->assertJsonPath('diet_preferences.calorie_goal', null);

        $plan = DietPlan::create([
            'name' => 'Lean', 'goal' => 'Weight Loss', 'workout_level' => 'Beginner', 'diet_type' => 'Normal',
            'show_in_mobile_app' => true, 'access_type' => 'Free', 'publish_status' => 'Published',
        ]);
        $this->putJson('/api/activity/diet-preferences', [
            'calorie_goal' => 2200, 'diet_plan_id' => $plan->id,
        ], $headers)->assertOk()->assertJsonPath('diet_preferences.diet_plan_id', $plan->id);
        $this->getJson('/api/activity/diet-preferences', $headers)
            ->assertJsonPath('diet_preferences.calorie_goal', 2200);
    }
}
