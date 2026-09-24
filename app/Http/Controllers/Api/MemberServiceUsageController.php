<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberServiceUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MemberServiceUsageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
        ]);

        if (! empty($data['member_id'])) {
            $member = Member::query()->with('membershipPlan')->findOrFail($data['member_id']);
            $this->syncServiceUsagesFromPlan($member);

            return response()->json([
                'service_usages' => MemberServiceUsage::query()
                    ->with('member')
                    ->where('member_id', $member->id)
                    ->latest()
                    ->get(),
            ]);
        }

        return response()->json([
            'service_usages' => MemberServiceUsage::query()->with('member')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'service_name' => ['required', 'string', 'max:255'],
            'allowed_sessions' => ['nullable', 'integer', 'min:0'],
            'used_sessions' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $member = Member::query()->findOrFail($data['member_id']);
        $normalizedName = $this->normalizeServiceName($data['service_name']);
        $allowedSessions = (int) ($data['allowed_sessions'] ?? 0);
        $usedSessions = (int) ($data['used_sessions'] ?? 0);

        $serviceUsage = MemberServiceUsage::query()
            ->where('member_id', $member->id)
            ->where('service_name', $normalizedName)
            ->first();

        if ($serviceUsage) {
            $serviceUsage->update([
                'allowed_sessions' => $allowedSessions > 0 ? $allowedSessions : $serviceUsage->allowed_sessions,
                'used_sessions' => $usedSessions,
                'notes' => $data['notes'] ?? $serviceUsage->notes,
            ]);
        } else {
            $serviceUsage = MemberServiceUsage::create([
                'member_id' => $member->id,
                'service_name' => $normalizedName,
                'allowed_sessions' => $allowedSessions,
                'used_sessions' => $usedSessions,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Service usage saved successfully.',
            'service_usage' => $serviceUsage->fresh(),
        ], 201);
    }

    public function consume(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'service_name' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $member = Member::query()->with('membershipPlan')->findOrFail($data['member_id']);
        $this->syncServiceUsagesFromPlan($member);

        $normalizedName = $this->normalizeServiceName($data['service_name']);
        $quantity = (int) ($data['quantity'] ?? 1);

        $serviceUsage = MemberServiceUsage::query()
            ->where('member_id', $member->id)
            ->where('service_name', $normalizedName)
            ->first();

        if (! $serviceUsage) {
            return response()->json([
                'message' => 'This service has not been configured for this member yet.',
            ], 422);
        }

        $remaining = max($serviceUsage->allowed_sessions - ($serviceUsage->used_sessions + $quantity), 0);
        if ($serviceUsage->used_sessions + $quantity > $serviceUsage->allowed_sessions) {
            return response()->json([
                'message' => 'This service quota is already exhausted.',
                'remaining' => $remaining,
            ], 422);
        }

        $serviceUsage->update([
            'used_sessions' => $serviceUsage->used_sessions + $quantity,
        ]);

        return response()->json([
            'message' => 'Session usage recorded.',
            'service_usage' => $serviceUsage->fresh(),
        ], 201);
    }

    private function syncServiceUsagesFromPlan(Member $member): void
    {
        $planBenefits = $member->membershipPlan?->benefits;
        if (! is_array($planBenefits) || $planBenefits === []) {
            return;
        }

        $quotas = collect($planBenefits)
            ->map(fn (mixed $benefit): ?array => $this->parseServiceQuota((string) $benefit))
            ->filter()
            ->values();

        if ($quotas->isEmpty()) {
            return;
        }

        $existing = MemberServiceUsage::query()
            ->where('member_id', $member->id)
            ->get()
            ->keyBy(fn (MemberServiceUsage $usage): string => strtolower($usage->service_name));

        $quotas->each(function (array $quota) use ($member, $existing): void {
            $serviceName = $quota['service_name'];
            $allowedSessions = $quota['allowed_sessions'];
            $key = strtolower($serviceName);

            /** @var MemberServiceUsage|null $usage */
            $usage = $existing->get($key);

            if ($usage) {
                if ($usage->allowed_sessions !== $allowedSessions) {
                    $usage->update([
                        'allowed_sessions' => $allowedSessions,
                    ]);
                }

                return;
            }

            MemberServiceUsage::create([
                'member_id' => $member->id,
                'service_name' => $serviceName,
                'allowed_sessions' => $allowedSessions,
                'used_sessions' => 0,
            ]);
        });
    }

    /**
     * @return array{service_name: string, allowed_sessions: int}|null
     */
    private function parseServiceQuota(string $benefit): ?array
    {
        $cleaned = trim($benefit);

        if ($cleaned === '') {
            return null;
        }

        if (! preg_match('/^(.*?)(?:\s*[:\-]\s*|\s+)(\d+)\s*(?:session|sessions?)$/i', $cleaned, $matches)) {
            return null;
        }

        $serviceName = trim((string) ($matches[1] ?? ''));
        $allowedSessions = (int) ($matches[2] ?? 0);

        if ($serviceName === '' || $allowedSessions <= 0) {
            return null;
        }

        return [
            'service_name' => $this->normalizeServiceName($serviceName),
            'allowed_sessions' => $allowedSessions,
        ];
    }

    private function normalizeServiceName(string $serviceName): string
    {
        $trimmed = trim($serviceName);

        return $trimmed === '' ? 'Service' : ucwords(strtolower($trimmed));
    }
}
