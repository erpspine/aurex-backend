<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BulkSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SmsController extends Controller
{
    public function __construct(private readonly BulkSmsService $bulkSmsService)
    {
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required'],
            'to.*' => ['string', 'max:30'],
            'message' => ['required', 'string', 'max:4000'],
            'sender_id' => ['nullable', 'string', 'max:11'],
        ]);

        $recipients = is_array($validated['to'])
            ? $validated['to']
            : [$validated['to']];

        if ($recipients === []) {
            return response()->json([
                'message' => 'At least one recipient is required.',
            ], 422);
        }

        $results = [];

        try {
            foreach ($recipients as $recipient) {
                $results[] = [
                    'to' => $recipient,
                    'response' => $this->bulkSmsService->sendTextSingle(
                        to: (string) $recipient,
                        text: $validated['message'],
                        senderId: $validated['sender_id'] ?? null,
                    ),
                ];
            }
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'SMS request submitted.',
            'results' => $results,
        ]);
    }
}