<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TurnstileCommand;
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
                ->map(fn (TurnstileCommand $command) => [
                    'id' => $command->id,
                    'type' => $command->type,
                    'member_id' => $command->member_id,
                    'member_name' => $command->member?->full_name,
                    'expires_at' => $command->expires_at->toISOString(),
                ]),
        ]);
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
}
