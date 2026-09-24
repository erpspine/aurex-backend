<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\MembershipExpiryReminder;
use App\Services\BulkSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;

class SendMembershipExpiryReminders extends Command
{
    protected $signature = 'memberships:send-expiry-reminders {--dry-run : Build and log reminders without calling provider}';

    protected $description = 'Send membership expiry reminders 5 days and 1 day before expiry';

    public function __construct(private readonly BulkSmsService $bulkSmsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = now()->startOfDay();
        $targetDates = [
            5 => $today->copy()->addDays(5)->toDateString(),
            1 => $today->copy()->addDay()->toDateString(),
        ];

        $members = Member::query()
            ->with('membershipPlan:id,price_amount,currency')
            ->where('membership_status', 'Active')
            ->whereIn('expiry_date', array_values($targetDates))
            ->get();

        if ($members->isEmpty()) {
            $this->info('No members eligible for expiry reminder today.');

            return self::SUCCESS;
        }

        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($members as $member) {
            if (! $member->expiry_date) {
                $skippedCount++;
                continue;
            }

            $daysBeforeExpiry = max(0, (int) $today->diffInDays($member->expiry_date->copy()->startOfDay(), false));

            if (! in_array($daysBeforeExpiry, [5, 1], true)) {
                $skippedCount++;
                continue;
            }

            $phone = $this->normalizePhone((string) $member->phone);

            if ($phone === '') {
                $failedCount++;
                $this->warn("Skipped {$member->full_name}: missing phone number.");
                continue;
            }

            $reminderType = $daysBeforeExpiry === 5 ? 'five_days' : 'one_day';
            $amount = $this->resolveReminderAmount($member);
            $currency = $member->membershipPlan?->currency ?: 'TZS';

            $message = $this->buildMessage(
                memberName: (string) $member->full_name,
                expiryDate: $member->expiry_date,
                amount: $amount,
                currency: $currency,
            );

            if ($this->option('dry-run')) {
                $this->line("DRY RUN: {$member->full_name} ({$phone}) [D-{$daysBeforeExpiry}]");
                continue;
            }

            $log = MembershipExpiryReminder::query()->firstOrNew([
                'member_id' => $member->id,
                'reminder_type' => $reminderType,
                'expiry_date' => $member->expiry_date->toDateString(),
            ]);

            if ($log->exists && $log->status === 'sent') {
                $skippedCount++;
                continue;
            }

            $log->fill([
                'phone' => $phone,
                'days_before_expiry' => $daysBeforeExpiry,
                'membership_amount' => $amount,
                'message' => $message,
                'status' => 'pending',
                'error_message' => null,
            ]);
            $log->save();

            try {
                $response = $this->bulkSmsService->sendTextSingle(
                    to: $phone,
                    text: $message,
                    senderId: (string) config('services.bulk_sms.sender_id', 'AUREX'),
                );

                $providerMessage = $response['messages'][0] ?? [];
                $providerStatus = $providerMessage['status']['name'] ?? null;

                $log->update([
                    'status' => 'sent',
                    'provider_message_id' => isset($providerMessage['messageId']) ? (string) $providerMessage['messageId'] : null,
                    'provider_send_reference' => isset($providerMessage['sendReference']) ? (string) $providerMessage['sendReference'] : null,
                    'provider_status_name' => is_string($providerStatus) ? $providerStatus : null,
                    'provider_response' => $response,
                    'sent_at' => now(),
                ]);

                $sentCount++;
                $this->info("Sent reminder to {$member->full_name} ({$phone}) [D-{$daysBeforeExpiry}]");
            } catch (RuntimeException $exception) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ]);

                $failedCount++;
                $this->error("Failed reminder for {$member->full_name} ({$phone}): {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->line("Summary: sent={$sentCount}, failed={$failedCount}, skipped={$skippedCount}");

        return self::SUCCESS;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', trim($phone)) ?? '';

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '255'.substr($phone, 1);
        }

        return $phone;
    }

    private function buildMessage(string $memberName, Carbon $expiryDate, int $amount, string $currency): string
    {
        $formattedAmount = number_format($amount).' '.$currency;

        $expiryText = $expiryDate->format('d-M-Y');
        $reference = trim($memberName) !== '' ? $memberName : 'Member';

        return "Dear Aurex Member {$memberName}, your membership expires on {$expiryText}.\n\n"
            ."To renew and continue enjoying uninterrupted access to Aurex Performance Arena, please make payment via:\n\n"
            ."Lipa No.: 449385543\n"
            ."Amount: {$formattedAmount}\n"
                ."Reference: {$reference}\n\n"
            ."After payment, kindly send your payment confirmation via WhatsApp to 0704 998 620.\n\n"
            ."Thank you for being part of Aurex Performance Arena.\n"
            ."Strength • Discipline • Performance";
    }

    private function resolveReminderAmount(Member $member): int
    {
        $normalizedPlanAmount = $member->membershipPlan?->price_amount !== null
            ? (int) $member->membershipPlan->price_amount
            : null;

        if ($normalizedPlanAmount !== null && $normalizedPlanAmount > 0) {
            return $normalizedPlanAmount;
        }

        $latestMembershipPaymentAmount = $member->payments()
            ->where('payment_status', 'Paid')
            ->whereIn('payment_for', ['Membership', 'Membership Renewal', 'Membership Plan'])
            ->whereNotNull('amount')
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->value('amount');

        if ($latestMembershipPaymentAmount !== null && (int) $latestMembershipPaymentAmount > 0) {
            return (int) $latestMembershipPaymentAmount;
        }

        $normalizedMemberPaid = $member->amount_paid !== null ? (int) $member->amount_paid : null;

        if ($normalizedMemberPaid !== null && $normalizedMemberPaid > 0) {
            return $normalizedMemberPaid;
        }

        return max((int) ($normalizedPlanAmount ?? 0), 0);
    }
}