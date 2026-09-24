<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MemberCredentialsMail;
use App\Models\Member;
use App\Models\MembershipExpiryReminder;
use App\Models\TurnstileCommand;
use App\Models\User;
use App\Services\BulkSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use RuntimeException;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = Member::query()
            ->with(['membershipPlan:id,name', 'user:id,email,status'])
            ->latest()
            ->get();

        return response()->json([
            'members' => $this->enrichMembersWithControllerPushStatus($members),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());
        $plainPassword = Str::random(12);

        $member = DB::transaction(function () use ($validated, $plainPassword): Member {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'user_type' => 'Member',
                'role' => 'Member',
                'status' => $this->userStatusFor($validated['membership_status']),
                'password' => Hash::make($plainPassword),
                'two_factor_enabled' => false,
                'force_password_change' => true,
            ]);

            return Member::create([
                ...$validated,
                'user_id' => $user->id,
            ]);
        });

        Mail::to($member->email)->send(new MemberCredentialsMail(
            member: $member,
            plainPassword: $plainPassword,
            appUrl: (string) config('app.mobile_app_url'),
        ));

        return response()->json([
            'message' => 'Member created successfully and mobile app credentials email sent.',
            'member' => $this->enrichMember(
                $member->load(['membershipPlan:id,name', 'user:id,email,status']),
            ),
        ], 201);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json([
            'member' => $this->enrichMember(
                $member->load(['membershipPlan:id,name', 'user:id,email,status']),
            ),
        ]);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate($this->updateRules($member));

        DB::transaction(function () use ($member, $validated): void {
            $member->update($validated);

            if ($member->user) {
                $member->user->update([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'status' => $this->userStatusFor($validated['membership_status']),
                ]);
            }
        });

        return response()->json([
            'message' => 'Member updated successfully.',
            'member' => $this->enrichMember(
                $member->fresh()->load(['membershipPlan:id,name', 'user:id,email,status']),
            ),
        ]);
    }

    public function updateCard(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate([
            'access_code' => $this->accessCodeRules($member),
        ]);

        $member->update([
            'access_code' => $validated['access_code'] ?? null,
        ]);

        return response()->json([
            'message' => $member->access_code
                ? 'Turnstile card linked successfully.'
                : 'Turnstile card unlinked successfully.',
            'member' => $this->enrichMember(
                $member->fresh()->load(['membershipPlan:id,name', 'user:id,email,status']),
            ),
        ]);
    }

    public function destroy(Member $member): JsonResponse
    {
        DB::transaction(function () use ($member): void {
            $user = $member->user;

            $member->delete();

            if ($user && $user->user_type === 'Member') {
                $user->delete();
            }
        });

        return response()->json([
            'message' => 'Member and mobile app login account deleted successfully.',
        ]);
    }

    public function sendExpiryReminder(Member $member, BulkSmsService $bulkSmsService): JsonResponse
    {
        $member->loadMissing('membershipPlan:id,price_amount,currency');

        if (! $member->expiry_date) {
            return response()->json([
                'message' => 'Member expiry date is not set.',
            ], 422);
        }

        $phone = $this->normalizePhone((string) $member->phone);

        if ($phone === '') {
            return response()->json([
                'message' => 'Member phone number is not valid.',
            ], 422);
        }

        $amount = $this->resolveReminderAmount($member);
        $currency = $member->membershipPlan?->currency ?: 'TZS';
        $message = $this->buildExpiryReminderMessage(
            memberName: (string) $member->full_name,
            expiryDate: Carbon::parse($member->expiry_date),
            amount: $amount,
            currency: $currency,
        );

        $expiryDate = Carbon::parse($member->expiry_date)->toDateString();

        $log = MembershipExpiryReminder::query()->firstOrNew([
            'member_id' => $member->id,
            'reminder_type' => 'manual_send',
            'expiry_date' => $expiryDate,
        ]);

        $log->fill([
            'phone' => $phone,
            'days_before_expiry' => max(0, now()->startOfDay()->diffInDays(Carbon::parse($member->expiry_date)->startOfDay(), false)),
            'membership_amount' => $amount,
            'status' => 'pending',
            'message' => $message,
            'provider_message_id' => null,
            'provider_send_reference' => null,
            'provider_status_name' => null,
            'provider_response' => null,
            'error_message' => null,
            'sent_at' => null,
        ]);
        $log->save();

        try {
            $response = $bulkSmsService->sendTextSingle(
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

            return response()->json([
                'message' => 'Expiry reminder SMS sent successfully.',
                'reminder' => $log->fresh(),
            ]);
        } catch (RuntimeException $exception) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
                'reminder' => $log->fresh(),
            ], 502);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storeRules(): array
    {
        return [
            ...$this->baseRules(),
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updateRules(Member $member): array
    {
        $emailRules = ['required', 'email', 'max:255'];
        $emailRules[] = $member->user_id
            ? Rule::unique('users', 'email')->ignore($member->user_id)
            : Rule::unique('users', 'email');

        return [
            ...$this->baseRules($member),
            'email' => $emailRules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(?Member $member = null): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'access_code' => $this->accessCodeRules($member),
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'membership_plan_id' => ['nullable', 'uuid', 'exists:membership_plans,id'],
            'membership_status' => ['required', Rule::in(['Active', 'Pending', 'Expired', 'Suspended'])],
            'start_date' => ['nullable', 'date'],
            'expiry_date' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $startDate = request()->input('start_date');

                    if (! $startDate || ! $value) {
                        return;
                    }

                    if (Carbon::parse($startDate)->startOfDay()->gt(Carbon::parse($value)->startOfDay())) {
                        $fail('The start date should not be greater than the expiry date.');
                    }
                },
            ],
            'amount_paid' => ['required', 'integer', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['Cash', 'M-Pesa', 'Airtel Money', 'Bank Transfer', 'Card'])],
            'payment_status' => ['required', Rule::in(['Paid', 'Pending', 'Failed'])],
            'height_cm' => ['nullable', 'integer', 'min:1'],
            'weight_kg' => ['nullable', 'integer', 'min:1'],
            'fitness_goal' => ['nullable', Rule::in(['Weight Loss', 'Muscle Gain', 'Strength', 'General Fitness'])],
            'workout_level' => ['nullable', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function userStatusFor(string $membershipStatus): string
    {
        return $membershipStatus === 'Active' ? 'Active' : 'Inactive';
    }

    /**
     * @return array<int, mixed>
     */
    private function accessCodeRules(?Member $member = null): array
    {
        return [
            'nullable',
            'string',
            'regex:/^[1-9]\d{0,9}$/',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value !== null && (int) $value > 4294967295) {
                    $fail('The turnstile card number must fit an unsigned 32-bit number.');
                }
            },
            $member
                ? Rule::unique('members', 'access_code')->ignore($member->id)
                : Rule::unique('members', 'access_code'),
        ];
    }

    /**
     * @param Collection<int, Member> $members
     * @return array<int, array<string, mixed>>
     */
    private function enrichMembersWithControllerPushStatus(Collection $members): array
    {
        $cards = $members
            ->pluck('access_code')
            ->filter(fn(mixed $code): bool => is_string($code) && trim($code) !== '')
            ->map(fn(string $code): string => trim($code))
            ->unique()
            ->values();

        $latestByCard = [];

        if ($cards->isNotEmpty()) {
            $commands = TurnstileCommand::query()
                ->select(['id', 'status', 'result_message', 'created_at', 'completed_at', 'reason'])
                ->where('type', 'add_card')
                ->latest('created_at')
                ->limit(5000)
                ->get();

            foreach ($commands as $command) {
                $cardNumber = $this->extractCardNumberFromReason($command->reason);

                if (!$cardNumber || !in_array($cardNumber, $cards->all(), true)) {
                    continue;
                }

                if (isset($latestByCard[$cardNumber])) {
                    continue;
                }

                $latestByCard[$cardNumber] = $command;

                if (count($latestByCard) === $cards->count()) {
                    break;
                }
            }
        }

        return $members
            ->map(fn(Member $member): array => $this->enrichMember(
                $member,
                $member->access_code ? ($latestByCard[trim($member->access_code)] ?? null) : null,
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichMember(Member $member, ?TurnstileCommand $command = null): array
    {
        $cardNumber = is_string($member->access_code) ? trim($member->access_code) : '';

        if ($cardNumber === '') {
            return [
                ...$member->toArray(),
                'controller_push_status' => 'Not Linked',
                'controller_push_message' => null,
                'controller_push_updated_at' => null,
            ];
        }

        if (!$command) {
            $command = TurnstileCommand::query()
                ->select(['id', 'status', 'result_message', 'created_at', 'completed_at', 'reason'])
                ->where('type', 'add_card')
                ->where('reason', 'like', '%"card_number":"' . $cardNumber . '"%')
                ->latest('created_at')
                ->first();
        }

        $status = match ($command?->status) {
            'Completed' => 'Pushed',
            'Pending' => 'Pending',
            'Failed', 'Expired' => 'Failed',
            default => 'Not Pushed',
        };

        return [
            ...$member->toArray(),
            'controller_push_status' => $status,
            'controller_push_message' => $command?->result_message,
            'controller_push_updated_at' => $command?->completed_at?->toISOString() ?? $command?->created_at?->toISOString(),
        ];
    }

    private function extractCardNumberFromReason(?string $reason): ?string
    {
        if (!$reason) {
            return null;
        }

        $decoded = json_decode($reason, true);

        if (!is_array($decoded) || !isset($decoded['card_number'])) {
            return null;
        }

        $cardNumber = trim((string) $decoded['card_number']);

        return $cardNumber !== '' ? $cardNumber : null;
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

    private function buildExpiryReminderMessage(string $memberName, Carbon $expiryDate, int $amount, string $currency): string
    {
        $formattedAmount = number_format($amount).' '.$currency;

        $expiryText = $expiryDate->format('d-M-Y');
        $reference = trim($memberName) !== '' ? $memberName : 'Member';

        return "Dear Aurex Member, your membership expires on {$expiryText}.\n\n"
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
