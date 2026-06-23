<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Database\Seeder;

class WorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $workouts = [
            [
                'name' => 'Beginner Full Body Foundation',
                'goal' => 'General Fitness',
                'workout_level' => 'Beginner',
                'workout_type' => 'Full Body',
                'duration' => '45 min',
                'calories_burn' => '350 kcal',
                'description' => 'A balanced full body workout for new members building technique and confidence.',
                'exercise_names' => ['Goblet Squat', 'Push Up', 'Lat Pulldown', 'Plank Hold'],
                'warm_up' => '5 minutes treadmill walk, hip openers, shoulder circles and bodyweight squats.',
                'trainer_notes' => 'Keep loads light and focus on clean movement patterns.',
                'cool_down' => 'Slow walk for 3 minutes followed by hamstring, chest and lat stretches.',
                'access_type' => 'Free',
            ],
            [
                'name' => 'Upper Body Strength',
                'goal' => 'Strength',
                'workout_level' => 'Intermediate',
                'workout_type' => 'Upper Body',
                'duration' => '50 min',
                'calories_burn' => '420 kcal',
                'description' => 'Upper body strength session focused on pressing, pulling and core control.',
                'exercise_names' => ['Dumbbell Bench Press', 'Shoulder Press Machine', 'Lat Pulldown', 'Resistance Band Row'],
                'warm_up' => 'Band pull-aparts, light push ups and two easy ramp-up sets.',
                'trainer_notes' => 'Use controlled tempo and leave one to two reps in reserve.',
                'cool_down' => 'Stretch chest, shoulders, biceps and lats for 5 minutes.',
                'access_type' => 'Members Only',
            ],
            [
                'name' => 'Lower Body Power',
                'goal' => 'Muscle Gain',
                'workout_level' => 'Intermediate',
                'workout_type' => 'Lower Body',
                'duration' => '55 min',
                'calories_burn' => '500 kcal',
                'description' => 'Lower body muscle building session using compound leg movements.',
                'exercise_names' => ['Leg Press', 'Goblet Squat', 'Kettlebell Swing', 'Plank Hold'],
                'warm_up' => 'Light cycling, glute bridges, ankle mobility and bodyweight lunges.',
                'trainer_notes' => 'Drive through the full foot and keep knees tracking over toes.',
                'cool_down' => 'Quad, glute and calf stretches with slow breathing.',
                'access_type' => 'Members Only',
            ],
            [
                'name' => 'Fat Burn HIIT Circuit',
                'goal' => 'Weight Loss',
                'workout_level' => 'Advanced',
                'workout_type' => 'HIIT',
                'duration' => '30 min',
                'calories_burn' => '520 kcal',
                'description' => 'High intensity circuit designed to increase conditioning and calorie expenditure.',
                'exercise_names' => ['Treadmill Intervals', 'Kettlebell Swing', 'Push Up', 'Plank Hold'],
                'warm_up' => '5 minutes progressive cardio and dynamic mobility.',
                'trainer_notes' => 'Move fast but maintain form. Extend rest if technique breaks down.',
                'cool_down' => 'Walk until heart rate drops, then stretch hips, chest and shoulders.',
                'access_type' => 'Premium',
            ],
            [
                'name' => 'Pull Day Builder',
                'goal' => 'Muscle Gain',
                'workout_level' => 'Intermediate',
                'workout_type' => 'Pull Day',
                'duration' => '45 min',
                'calories_burn' => '380 kcal',
                'description' => 'Back and biceps focused workout for building pulling strength.',
                'exercise_names' => ['Lat Pulldown', 'Resistance Band Row', 'Kettlebell Swing', 'Plank Hold'],
                'warm_up' => 'Light rowing, scapular retractions and band rows.',
                'trainer_notes' => 'Pull elbows down and back. Avoid shrugging during rows.',
                'cool_down' => 'Lat, rear delt and forearm stretches.',
                'access_type' => 'Members Only',
            ],
            [
                'name' => 'Cardio Endurance Reset',
                'goal' => 'Endurance',
                'workout_level' => 'Beginner',
                'workout_type' => 'Cardio',
                'duration' => '35 min',
                'calories_burn' => '390 kcal',
                'description' => 'Steady cardio and core routine for improving aerobic capacity.',
                'exercise_names' => ['Treadmill Intervals', 'Resistance Band Row', 'Plank Hold'],
                'warm_up' => 'Easy walking for 5 minutes and light mobility.',
                'trainer_notes' => 'Keep breathing steady and stay below max effort.',
                'cool_down' => '5 minutes slow walk and full body stretching.',
                'access_type' => 'Free',
            ],
        ];

        foreach ($workouts as $workout) {
            $exerciseNames = $workout['exercise_names'];
            unset($workout['exercise_names']);

            Workout::updateOrCreate(
                ['name' => $workout['name']],
                [
                    'exercises' => $this->exercisePayload($exerciseNames),
                    'publish_status' => 'Published',
                    'show_in_mobile_app' => true,
                    ...$workout,
                ],
            );
        }
    }

    private function exercisePayload(array $exerciseNames): array
    {
        $exercises = Exercise::query()
            ->whereIn('name', $exerciseNames)
            ->get()
            ->keyBy('name');

        return collect($exerciseNames)
            ->map(function (string $name) use ($exercises) {
                $exercise = $exercises->get($name);

                return [
                    'exercise_id' => $exercise?->id ?? '',
                    'name' => $exercise?->name ?? $name,
                    'body_part' => $exercise?->body_part ?? '',
                    'sets' => $exercise?->sets ?? '3 sets',
                    'reps' => $exercise?->reps ?? '10 reps',
                    'rest' => $exercise?->rest_time ?? '60 sec',
                ];
            })
            ->values()
            ->all();
    }
}
