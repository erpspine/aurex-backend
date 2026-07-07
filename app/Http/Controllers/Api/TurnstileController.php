<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TurnstileCommand;
use App\Models\TurnstileEvent;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TurnstileController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => ['required', 'string', 'max:100'],
            'member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $command = TurnstileCommand::create([
            ...$data,
            'type' => 'open_gate',
            'requested_by' => $request->user()->id,
            'status' => 'Pending',
            'expires_at' => now()->addMinutes(2),
        ]);

        return response()->json([
            'message' => 'Remote gate command queued.',
            'command' => $command->load('member:id,full_name'),
        ], 201);
    }

    public function pending(Request $request): JsonResponse
    {
        $agentId = trim((string) $request->header('X-Aurex-Agent'));
        abort_if($agentId === '', 422, 'X-Aurex-Agent header is required.');

        TurnstileCommand::query()
            ->where('agent_id', $agentId)
            ->where('status', 'Pending')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'Expired',
                'result_message' => 'The agent did not collect the command before it expired.',
                'completed_at' => now(),
            ]);

        return response()->json([
            'commands' => TurnstileCommand::query()
                ->with('member:id,full_name')
                ->where('agent_id', $agentId)
                ->where('status', 'Pending')
                ->where('expires_at', '>', now())
                ->oldest()
                ->limit(20)
                ->get()
                ->map(function (TurnstileCommand $command): array {
                    $cardPayload = self::cardPayloadFromReason($command->reason);

                    return [
                        'id' => $command->id,
                        'type' => $command->type,
                        'member_id' => $command->member_id,
                        'member_name' => $cardPayload['member_name']
                            ?? $command->member?->full_name,
                        'card_number' => $cardPayload['card_number'] ?? null,
                        'expiry_date' => $cardPayload['expiry_date'] ?? null,
                        'expires_at' => $command->expires_at->toISOString(),
                    ];
                }),
        ]);
    }

    public function latestCard(): JsonResponse
    {
        $event = TurnstileEvent::query()
            ->whereNotNull('card_number')
            ->where('card_number', '!=', '')
            ->orderByDesc('event_time')
            ->orderByDesc('created_at')
            ->first();

        if (!$event) {
            return response()->json([
                'message' => 'No turnstile card scans found yet.',
                'card_number' => null,
                'event_time' => null,
                'controller_serial' => null,
                'agent_id' => $this->resolveAgentId(null),
            ]);
        }

        return response()->json([
            'card_number' => $event->card_number,
            'event_time' => $event->event_time?->toISOString(),
            'controller_serial' => $event->controller_serial,
            'agent_id' => $event->agent_id,
        ]);
    }

    public function pushCard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => ['nullable', 'string', 'max:100'],
            'card_number' => [
                'required',
                'string',
                'regex:/^[1-9]\d{0,9}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && (int) $value > 4294967295) {
                        $fail('The turnstile card number must fit an unsigned 32-bit number.');
                    }
                },
            ],
            'member_name' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $agentId = $this->resolveAgentId($data['agent_id'] ?? null);

        if (!$agentId) {
            return response()->json([
                'message' => 'Unable to determine target turnstile agent. Set TURNSTILE_AGENT_ID in backend .env, or VITE_TURNSTILE_AGENT_ID in frontend env, or sync at least one turnstile event first.',
            ], 422);
        }

        $command = TurnstileCommand::create([
            'agent_id' => $agentId,
            'type' => 'add_card',
            'requested_by' => $request->user()->id,
            'reason' => json_encode([
                'card_number' => (string) $data['card_number'],
                'member_name' => $data['member_name'] ?? null,
                'expiry_date' => isset($data['expiry_date'])
                    ? Carbon::parse($data['expiry_date'])->toDateString()
                    : null,
            ]),
            'status' => 'Pending',
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'message' => 'Card push command queued for turnstile controller.',
            'command' => $command,
        ], 201);
    }

    public function acknowledge(
        Request $request,
        TurnstileCommand $turnstileCommand,
    ): JsonResponse {
        $agentId = trim((string) $request->header('X-Aurex-Agent'));
        abort_unless(
            $agentId !== '' && hash_equals($turnstileCommand->agent_id, $agentId),
            404,
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(['Completed', 'Failed'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($turnstileCommand->status === 'Pending') {
            $turnstileCommand->update([
                'status' => $data['status'],
                'result_message' => $data['message'] ?? null,
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Command acknowledgement recorded.',
            'command' => $turnstileCommand->fresh(),
        ]);
    }

    private function resolveAgentId(?string $requestedAgentId): ?string
    {
        $agentId = trim((string) $requestedAgentId);
        if ($agentId !== '') {
            return $agentId;
        }

        $eventAgentId = TurnstileEvent::query()
            ->whereNotNull('agent_id')
            ->where('agent_id', '!=', '')
            ->orderByDesc('event_time')
            ->orderByDesc('created_at')
            ->value('agent_id');

        if ($eventAgentId) {
            return $eventAgentId;
        }

        $commandAgentId = TurnstileCommand::query()
            ->whereNotNull('agent_id')
            ->where('agent_id', '!=', '')
            ->latest('created_at')
            ->value('agent_id');

        if ($commandAgentId) {
            return $commandAgentId;
        }

        $configuredAgentId = trim((string) config('services.turnstile.default_agent_id', ''));

        return $configuredAgentId !== '' ? $configuredAgentId : null;
    }

    /**
     * @return array{card_number?: string|null, member_name?: string|null, expiry_date?: string|null}
     */
    private static function cardPayloadFromReason(?string $reason): array
    {
        if (!$reason) {
            return [];
        }

        $decoded = json_decode($reason, true);
        if (!is_array($decoded)) {
            return [];
        }

        return [
            'card_number' => isset($decoded['card_number']) ? (string) $decoded['card_number'] : null,
            'member_name' => isset($decoded['member_name']) ? (string) $decoded['member_name'] : null,
            'expiry_date' => isset($decoded['expiry_date']) ? (string) $decoded['expiry_date'] : null,
        ];
    }
}
