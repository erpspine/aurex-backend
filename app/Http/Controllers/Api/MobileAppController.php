<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use App\Models\DietPlan;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Workout;
use App\Models\WorkoutLevel;
use Illuminate\Http\JsonResponse;

class MobileAppController extends Controller
{
    public function show(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'stats' => [
                'app_users' => Member::query()->count(),
                'active_devices' => Member::query()->where('membership_status', 'Active')->count(),
                'push_notifications' => 0,
                'workout_starts' => 0,
            ],
            'modules' => [
                [
                    'name' => 'Body Part Exercises',
                    'status' => 'Enabled',
                    'items' => Exercise::query()->where('category', 'Body Part Exercise')->count().' Exercises',
                    'icon' => 'Dumbbell',
                ],
                [
                    'name' => 'Equipment Exercises',
                    'status' => 'Enabled',
                    'items' => Exercise::query()->where('category', 'Equipment Based')->count().' Exercises',
                    'icon' => 'Activity',
                ],
                [
                    'name' => 'Workouts',
                    'status' => 'Enabled',
                    'items' => Workout::query()->count().' Programs',
                    'icon' => 'Dumbbell',
                ],
                [
                    'name' => 'Workout Levels',
                    'status' => 'Enabled',
                    'items' => WorkoutLevel::query()->count().' Levels',
                    'icon' => 'BarChart3',
                ],
                [
                    'name' => 'Diet Plans',
                    'status' => 'Enabled',
                    'items' => DietPlan::query()->count().' Plans',
                    'icon' => 'Utensils',
                ],
                [
                    'name' => 'Class Booking',
                    'status' => 'Enabled',
                    'items' => GymClass::query()->whereDate('class_date', $today)->count().' Classes Today',
                    'icon' => 'CalendarDays',
                ],
            ],
            'home_sections' => [
                'Hero Banner',
                'Today Workout',
                'Body Part Exercises',
                'Equipment Based Exercises',
                'Popular Workouts',
                'Diet Plan Suggestions',
                'Class Schedule',
                'Progress Summary',
            ],
            'settings' => [
                ['label' => 'Allow Registration', 'value' => 'Enabled'],
                ['label' => 'Class Booking', 'value' => 'Enabled'],
                ['label' => 'Push Notifications', 'value' => 'Enabled'],
                ['label' => 'Premium Content', 'value' => 'Enabled'],
            ],
            'banners' => AppBanner::query()
                ->where('show_in_mobile_app', true)
                ->orderBy('display_order')
                ->latest()
                ->get(),
            'equipment_count' => Equipment::query()->count(),
        ]);
    }
}
