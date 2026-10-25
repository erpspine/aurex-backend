<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppService
{
    /**
     * Send an approved AUTHENTICATION template; the code fills both the body and the copy-code button.
     *
     * @return array<string, mixed>
     */
    public function sendVerificationCode(string $to, string $code): array
    {
        return $this->sendTemplate($to, (string) config('services.whatsapp.verification_template'), [
            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
            ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $code]]],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     * @return array<string, mixed>
     */
    public function sendTemplate(string $to, string $template, array $components = []): array
    {
        $token = (string) config('services.whatsapp.access_token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');

        if ($token === '' || $phoneNumberId === '') {
            throw new RuntimeException('WhatsApp credentials are not configured.');
        }

        $response = Http::baseUrl((string) config('services.whatsapp.base_url'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.whatsapp.timeout', 15))
            ->post($phoneNumberId.'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizePhone($to),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => (string) config('services.whatsapp.language', 'en')],
                    'components' => $components,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'WhatsApp request failed (%d): %s',
                $response->status(),
                $response->json('error.message') ?? $response->body()
            ));
        }

        return $response->json() ?? [];
    }

    private function normalizePhone(string $phone): string
    {
        // Cloud API expects digits only, in international format (local 07xx numbers are Tanzanian).
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return str_starts_with($digits, '0') ? '255'.substr($digits, 1) : $digits;
    }
}
