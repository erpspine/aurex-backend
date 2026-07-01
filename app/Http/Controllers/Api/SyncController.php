<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\TurnstileEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SyncController extends Controller
{
    public function members(): JsonResponse
    {
        return response()->json([
            'members' => Member::query()
                ->select([
                    'id',
                    'full_name',
                    'access_code',
                    'membership_status',
                    'expiry_date',
                ])
                ->whereNotNull('access_code')
                ->orderBy('id')
                ->get(),
            'server_time' => now()->toISOString(),
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_event_id' => ['required', 'uuid'],
            'agent_id' => ['required', 'string', 'max:100'],
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'card_number' => ['nullable', 'string', 'max:20'],
            'occurred_at' => ['required', 'date'],
            'direction' => ['nullable', Rule::in(['In', 'Out'])],
            'entry_method' => ['required', 'in:Turnstile'],
            'gym_zone' => ['required', 'string', 'max:100'],
            'controller_serial' => ['nullable', 'string', 'max:255'],
            'door' => ['nullable', 'integer', 'min:0', 'max:255'],
            'reader' => ['nullable', 'integer', 'min:0', 'max:255'],
            'event_type' => ['nullable', 'integer', 'min:0'],
            'controller_allowed' => ['nullable', 'boolean'],
        ]);

        $existing = TurnstileEvent::query()
            ->with('attendance')
            ->where('source_event_id', $data['source_event_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Attendance event already synchronized.',
                'event' => $existing,
                'attendance' => $existing->attendance,
            ]);
        }

        $member = Member::query()->with('membershipPlan:id,name')->findOrFail($data['member_id']);
        $eventTime = Carbon::parse($data['occurred_at']);
        $direction = $data['direction'] ?? 'In';

        [$event, $attendance] = DB::transaction(function () use (
            $data,
            $member,
            $eventTime,
            $direction,
        ): array {
            if ($direction === 'Out') {
                $attendance = Attendance::query()
                    ->where('member_id', $member->id)
                    ->where('status', 'Inside Gym')
                    ->latest('check_in_at')
                    ->lockForUpdate()
                    ->first();

                if ($attendance) {
                    $attendance->update([
                        'check_out_at' => $eventTime,
                        'status' => 'Checked Out',
                    ]);
                }
            } else {
                $attendance = Attendance::create([
                    'source_event_id' => $data['source_event_id'],
                    'agent_id' => $data['agent_id'],
                    'member_id' => $member->id,
                    'member_name' => $member->full_name,
                    'member_phone' => $member->phone,
                    'plan_name' => $member->membershipPlan?->name ?? $member->membership_status,
                    'check_in_at' => $eventTime,
                    'entry_method' => $data['entry_method'],
                    'gym_zone' => $data['gym_zone'],
                    'status' => 'Inside Gym',
                ]);
            }

            $event = TurnstileEvent::create([
                'source_event_id' => $data['source_event_id'],
                'agent_id' => $data['agent_id'],
                'member_id' => $member->id,
                'attendance_id' => $attendance?->id,
                'card_number' => $data['card_number'] ?? $member->access_code ?? '',
                'event_time' => $eventTime,
                'direction' => $direction,
                'controller_serial' => $data['controller_serial'] ?? null,
                'door' => $data['door'] ?? null,
                'reader' => $data['reader'] ?? null,
                'event_type' => $data['event_type'] ?? null,
                'controller_allowed' => $data['controller_allowed'] ?? false,
            ]);

            return [$event, $attendance];
        });

        return response()->json([
            'message' => $direction === 'Out'
                ? ($attendance ? 'Member checked out from controller event.' : 'Exit event recorded; no open session was found.')
                : 'Member checked in from controller event.',
            'event' => $event,
            'attendance' => $attendance,
        ], 201);
    }
}
