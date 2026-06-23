<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->string('period', 'today')->toString();
        $query = Attendance::query()->latest('check_in_at');

        if ($period === 'week') {
            $query->whereBetween('check_in_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereBetween('check_in_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } else {
            $query->whereDate('check_in_at', now()->toDateString());
        }

        $attendances = $query->get();
        $todayAttendances = Attendance::query()
            ->whereDate('check_in_at', now()->toDateString())
            ->get();

        return response()->json([
            'attendances' => $attendances,
            'stats' => [
                'today_check_ins' => $todayAttendances->count(),
                'inside_gym' => $todayAttendances->where('status', 'Inside Gym')->count(),
                'avg_session' => $this->averageSession($todayAttendances),
                'missed_today' => max(Member::query()->where('membership_status', 'Active')->count() - $todayAttendances->count(), 0),
                'peak_hour' => $this->peakHour($todayAttendances),
            ],
        ]);
    }

    public function members(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());

        $members = Member::query()
            ->with('membershipPlan')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'members' => $members,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'check_in_date' => ['required', 'date'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'entry_method' => ['required', Rule::in(['Manual Entry', 'QR Code', 'Mobile App', 'Front Desk'])],
            'gym_zone' => ['required', Rule::in(['Main Gym Floor', 'Strength Zone', 'Cardio Zone', 'Class Studio', 'Personal Training'])],
            'staff_notes' => ['nullable', 'string'],
        ]);

        $member = Member::query()->with('membershipPlan')->findOrFail($data['member_id']);
        $checkInAt = Carbon::parse($data['check_in_date'].' '.$data['check_in_time']);

        $attendance = Attendance::create([
            'member_id' => $member->id,
            'member_name' => $member->full_name,
            'member_phone' => $member->phone,
            'plan_name' => $member->membershipPlan?->name ?? $member->membership_status,
            'check_in_at' => $checkInAt,
            'entry_method' => $data['entry_method'],
            'gym_zone' => $data['gym_zone'],
            'staff_notes' => $data['staff_notes'] ?? null,
            'status' => 'Inside Gym',
        ]);

        return response()->json([
            'message' => 'Member checked in successfully.',
            'attendance' => $attendance,
        ], 201);
    }

    public function checkout(Attendance $attendance): JsonResponse
    {
        if ($attendance->status === 'Checked Out') {
            return response()->json([
                'message' => 'Member is already checked out.',
                'attendance' => $attendance,
            ]);
        }

        $attendance->update([
            'check_out_at' => now(),
            'status' => 'Checked Out',
        ]);

        return response()->json([
            'message' => 'Member checked out successfully.',
            'attendance' => $attendance->fresh(),
        ]);
    }

    private function averageSession($attendances): string
    {
        $durations = $attendances
            ->filter(fn (Attendance $attendance) => $attendance->check_out_at !== null)
            ->map(fn (Attendance $attendance) => $attendance->check_in_at->diffInMinutes($attendance->check_out_at));

        if ($durations->isEmpty()) {
            return '0m';
        }

        return $this->durationText((int) round($durations->avg()));
    }

    private function peakHour($attendances): array
    {
        $hour = $attendances
            ->groupBy(fn (Attendance $attendance) => $attendance->check_in_at->format('H'))
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        if ($hour === null) {
            return ['label' => 'Not set', 'count' => 0];
        }

        return [
            'label' => Carbon::createFromTime((int) $hour)->format('g A'),
            'count' => $attendances->filter(fn (Attendance $attendance) => $attendance->check_in_at->format('H') === $hour)->count(),
        ];
    }

    private function durationText(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    }
}
