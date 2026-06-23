<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DietPlan;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function show(): JsonResponse
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $monthlyRevenue = Payment::query()
            ->where('payment_status', 'Paid')
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $previousMonthRevenue = Payment::query()
            ->where('payment_status', 'Paid')
            ->whereBetween('payment_date', [
                now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ])
            ->sum('amount');

        $activeMembers = Member::query()->where('membership_status', 'Active')->count();
        $previousActiveMembers = Member::query()
            ->where('membership_status', 'Active')
            ->whereDate('created_at', '<', now()->startOfMonth()->toDateString())
            ->count();

        $workouts = Workout::query()->count();
        $newWorkouts = Workout::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $dietPlans = DietPlan::query()->count();
        $newDietPlans = DietPlan::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $todayCheckIns = Attendance::query()
            ->whereDate('check_in_at', $today)
            ->count();

        $activeWorkouts = Workout::query()
            ->where('publish_status', 'Published')
            ->count();

        $classesToday = GymClass::query()
            ->whereDate('class_date', $today)
            ->count();

        $caloriesLogged = DietPlan::query()
            ->where('publish_status', 'Published')
            ->pluck('daily_calories')
            ->sum(fn ($calories) => $this->numericValue($calories));

        return response()->json([
            'stats' => [
                'active_members' => [
                    'value' => $activeMembers,
                    'change' => $this->changeText($activeMembers, $previousActiveMembers),
                ],
                'monthly_revenue' => [
                    'value' => $monthlyRevenue,
                    'change' => $this->changeText($monthlyRevenue, $previousMonthRevenue, true),
                ],
                'workout_programs' => [
                    'value' => $workouts,
                    'change' => '+'.$newWorkouts.' new',
                ],
                'diet_plans' => [
                    'value' => $dietPlans,
                    'change' => '+'.$newDietPlans.' new',
                ],
            ],
            'today_summary' => [
                'check_ins_today' => $todayCheckIns,
                'calories_logged' => $caloriesLogged,
                'active_workouts' => $activeWorkouts,
                'classes_today' => $classesToday,
            ],
            'performance' => $this->weeklyPerformance(),
        ]);
    }

    private function weeklyPerformance(): array
    {
        $startDate = now()->startOfWeek();
        $items = [];

        for ($day = 0; $day < 7; $day++) {
            $date = $startDate->copy()->addDays($day);
            $dateString = $date->toDateString();

            $items[] = [
                'label' => $date->format('D'),
                'date' => $dateString,
                'check_ins' => Attendance::query()
                    ->whereDate('check_in_at', $dateString)
                    ->count(),
                'revenue' => Payment::query()
                    ->where('payment_status', 'Paid')
                    ->whereDate('payment_date', $dateString)
                    ->sum('amount'),
            ];
        }

        return $items;
    }

    private function changeText(int|float $current, int|float $previous, bool $isCurrency = false): string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $percent = (($current - $previous) / $previous) * 100;
        $prefix = $percent >= 0 ? '+' : '';
        $value = round($percent, 1);

        return $prefix.$value.'%'.($isCurrency ? ' this month' : '');
    }

    private function numericValue(?string $value): int
    {
        if ($value === null) {
            return 0;
        }

        $number = preg_replace('/[^0-9.]/', '', $value);

        return (int) round((float) $number);
    }
}
