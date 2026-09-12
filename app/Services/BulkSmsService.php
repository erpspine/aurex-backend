<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BulkSmsService
{
    /**
     * @return array<string, mixed>
     */
    public function sendTextSingle(string $to, string $text, ?string $senderId = null): array
    {
        $username = (string) config('services.bulk_sms.username');
        $password = (string) config('services.bulk_sms.password');

        if ($username === '' || $password === '') {
            throw new RuntimeException('Bulk SMS credentials are not configured.');
        }

        $path = config('services.bulk_sms.test_mode')
            ? '/api/sms/v1/test/text/single'
            : '/api/sms/v1/text/single';

        $response = Http::baseUrl((string) config('services.bulk_sms.base_url'))
            ->withBasicAuth($username, $password)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.bulk_sms.timeout', 15))
            ->post($path, [
                'from' => $senderId ?: (string) config('services.bulk_sms.sender_id', 'AUREX'),
                'to' => $this->normalizePhone($to),
                'text' => $text,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(Response $response): array
    {
        if ($response->successful()) {
            $json = $response->json();

            return is_array($json)
                ? $json
                : ['raw' => $response->body()];
        }

        $payload = $response->json();
        $errorText = is_array($payload)
            ? ($payload['message'] ?? $payload['error'] ?? json_encode($payload))
            : $response->body();

        throw new RuntimeException(
            sprintf('Bulk SMS request failed (%d): %s', $response->status(), $errorText)
        );
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return $phone;
        }

        return preg_replace('/\s+/', '', $phone) ?? $phone;
    }
}