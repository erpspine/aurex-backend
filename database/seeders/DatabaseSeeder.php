<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->call(LocalAdminSeeder::class);
        }

        $this->call([
            BodyPartSeeder::class,
            MembershipPlanSeeder::class,
            EquipmentSeeder::class,
            ExerciseSeeder::class,
            ExerciseBodyPartMappingSeeder::class,
            WorkoutLevelSeeder::class,
            WorkoutSeeder::class,
            GymClassSeeder::class,
            TrainerSeeder::class,
            AppBannerSeeder::class,
        ]);
    }
}
