<?php

namespace Database\Seeders;

use App\Models\WorkoutLevel;
use Illuminate\Database\Seeder;

class WorkoutLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Beginner',
                'difficulty_rank' => 1,
                'recommended_duration' => '25-40 min',
                'intensity' => 'Low',
                'recommended_sets' => '2-3 sets',
                'recommended_reps' => '10-15 reps',
                'description' => 'Entry level training for new members learning safe movement and gym confidence.',
                'calories_range' => '150-300 kcal',
                'rest_time' => '60-90 sec',
                'training_frequency' => '2-3 days per week',
                'suitable_for' => 'New Members',
                'trainer_instructions' => 'Prioritize technique, breathing and controlled tempo before adding load.',
                'safety_notes' => 'Avoid max effort lifts and stop if form breaks down.',
                'linked_workouts' => 2,
                'linked_exercises' => 6,
                'access_type' => 'Free',
            ],
            [
                'name' => 'Intermediate',
                'difficulty_rank' => 2,
                'recommended_duration' => '40-55 min',
                'intensity' => 'Medium',
                'recommended_sets' => '3-4 sets',
                'recommended_reps' => '8-12 reps',
                'description' => 'Structured training for regular members improving strength, endurance and body composition.',
                'calories_range' => '300-500 kcal',
                'rest_time' => '60-90 sec',
                'training_frequency' => '3-4 days per week',
                'suitable_for' => 'Regular Members',
                'trainer_instructions' => 'Progress gradually using heavier loads, added volume or shorter rest periods.',
                'safety_notes' => 'Warm up properly before compound movements and avoid rushing reps.',
                'linked_workouts' => 3,
                'linked_exercises' => 8,
                'access_type' => 'Free',
            ],
            [
                'name' => 'Advanced',
                'difficulty_rank' => 3,
                'recommended_duration' => '50-70 min',
                'intensity' => 'High',
                'recommended_sets' => '4-5 sets',
                'recommended_reps' => '6-12 reps',
                'description' => 'Higher intensity training for experienced members targeting performance and progression.',
                'calories_range' => '450-700 kcal',
                'rest_time' => '90-120 sec',
                'training_frequency' => '4-5 days per week',
                'suitable_for' => 'Experienced Members',
                'trainer_instructions' => 'Use planned progression and monitor fatigue across weekly sessions.',
                'safety_notes' => 'Use spotters or safety catches for heavy sets and maintain recovery days.',
                'linked_workouts' => 1,
                'linked_exercises' => 7,
                'access_type' => 'Premium',
            ],
            [
                'name' => 'Elite',
                'difficulty_rank' => 4,
                'recommended_duration' => '60-90 min',
                'intensity' => 'Very High',
                'recommended_sets' => '5+ sets',
                'recommended_reps' => '3-10 reps',
                'description' => 'Performance-focused training for athletes and highly conditioned members.',
                'calories_range' => '600-900 kcal',
                'rest_time' => '120-180 sec',
                'training_frequency' => '5-6 days per week',
                'suitable_for' => 'Athletes',
                'trainer_instructions' => 'Program around performance goals, recovery metrics and technical standards.',
                'safety_notes' => 'Do not perform elite sessions without proper readiness, recovery and supervision.',
                'linked_workouts' => 0,
                'linked_exercises' => 4,
                'access_type' => 'Premium',
            ],
        ];

        foreach ($levels as $level) {
            WorkoutLevel::updateOrCreate(
                ['name' => $level['name']],
                [
                    'status' => 'Active',
                    'publish_status' => 'Published',
                    'show_in_mobile_app' => true,
                    ...$level,
                ],
            );
        }
    }
}
