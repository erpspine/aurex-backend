<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->string('period', 'month')->toString();

        if (! in_array($period, ['week', 'month', 'year'], true)) {
            $period = 'month';
        }

        [$startDate, $endDate] = $this->dateRange($period);

        $payments = Payment::query()
            ->with(['member:id,full_name,phone,email'])
            ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->latest('payment_date')
            ->get();

        $paidPayments = $payments->where('payment_status', 'Paid');
        $pendingPayments = $payments->where('payment_status', 'Pending');

        $attendances = Attendance::query()
            ->whereBetween('check_in_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get();

        $newMembers = Member::query()
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->count();

        $classes = GymClass::query()
            ->whereBetween('class_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        return response()->json([
            'period' => [
                'key' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'stats' => [
                'revenue' => $paidPayments->sum('amount'),
                'transactions' => $payments->count(),
                'pending_payments' => $pendingPayments->sum('amount'),
                'active_members' => Member::query()->where('membership_status', 'Active')->count(),
                'new_members' => $newMembers,
                'check_ins' => $attendances->count(),
                'avg_daily_attendance' => $this->averageDailyAttendance($attendances, $startDate, $endDate),
                'class_bookings' => $classes->sum('booked_slots'),
            ],
            'revenue_series' => $this->series($paidPayments, 'payment_date', 'amount', $period, $startDate, $endDate),
            'attendance_series' => $this->series($attendances, 'check_in_at', null, $period, $startDate, $endDate),
            'revenue_by_category' => $this->groupTotals($paidPayments, 'payment_for', 'amount'),
            'payment_methods' => $this->groupTotals($paidPayments, 'payment_method', 'amount'),
            'member_status' => $this->groupCounts(Member::query()->get(), 'membership_status'),
            'top_services' => $this->groupTotals($paidPayments, 'item_name', 'amount')->take(6)->values(),
            'recent_payments' => $payments->take(8)->values(),
        ]);
    }

    private function dateRange(string $period): array
    {
        if ($period === 'week') {
            return [now()->startOfWeek(), now()->endOfWeek()];
        }

        if ($period === 'year') {
            return [now()->startOfYear(), now()->endOfYear()];
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    private function series(Collection $items, string $dateField, ?string $amountField, string $period, Carbon $startDate, Carbon $endDate): array
    {
        $buckets = collect();

        if ($period === 'year') {
            $cursor = $startDate->copy()->startOfMonth();

            while ($cursor <= $endDate) {
                $key = $cursor->format('Y-m');
                $buckets->put($key, [
                    'key' => $key,
                    'label' => $cursor->format('M'),
                    'value' => 0,
                ]);
                $cursor->addMonth();
            }

            foreach ($items as $item) {
                $date = Carbon::parse($item->{$dateField});
                $key = $date->format('Y-m');

                if ($buckets->has($key)) {
                    $current = $buckets->get($key);
                    $current['value'] += $amountField ? (int) $item->{$amountField} : 1;
                    $buckets->put($key, $current);
                }
            }

            return $buckets->values()->all();
        }

        $cursor = $startDate->copy();

        while ($cursor <= $endDate) {
            $key = $cursor->toDateString();
            $buckets->put($key, [
                'key' => $key,
                'label' => $cursor->format('d M'),
                'value' => 0,
            ]);
            $cursor->addDay();
        }

        foreach ($items as $item) {
            $date = Carbon::parse($item->{$dateField});
            $key = $date->toDateString();

            if ($buckets->has($key)) {
                $current = $buckets->get($key);
                $current['value'] += $amountField ? (int) $item->{$amountField} : 1;
                $buckets->put($key, $current);
            }
        }

        return $buckets->values()->all();
    }

    private function groupTotals(Collection $items, string $labelField, string $amountField): Collection
    {
        return $items
            ->groupBy(fn ($item) => $item->{$labelField} ?: 'Not set')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'value' => $group->sum($amountField),
            ])
            ->sortByDesc('value')
            ->values();
    }

    private function groupCounts(Collection $items, string $labelField): Collection
    {
        return $items
            ->groupBy(fn ($item) => $item->{$labelField} ?: 'Not set')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'value' => $group->count(),
            ])
            ->sortByDesc('value')
            ->values();
    }

    private function averageDailyAttendance(Collection $attendances, Carbon $startDate, Carbon $endDate): int
    {
        $days = max($startDate->diffInDays($endDate) + 1, 1);

        return (int) round($attendances->count() / $days);
    }
}
