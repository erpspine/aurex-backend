<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private const SETTINGS_KEY = 'dashboard_settings';

    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->settings(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $settings = array_replace_recursive($this->defaults(), $validated);

        AppSetting::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => $settings],
        );

        return response()->json([
            'message' => 'Settings saved successfully.',
            'settings' => $settings,
        ]);
    }

    private function settings(): array
    {
        $record = AppSetting::query()
            ->where('key', self::SETTINGS_KEY)
            ->first();

        return array_replace_recursive($this->defaults(), $record?->value ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'gym_profile' => ['required', 'array'],
            'gym_profile.gym_name' => ['required', 'string', 'max:255'],
            'gym_profile.email' => ['nullable', 'email', 'max:255'],
            'gym_profile.phone' => ['nullable', 'string', 'max:100'],
            'gym_profile.location' => ['nullable', 'string', 'max:255'],
            'gym_profile.description' => ['nullable', 'string'],

            'mobile_app' => ['required', 'array'],
            'mobile_app.allow_registration' => ['required', 'boolean'],
            'mobile_app.require_membership' => ['required', 'boolean'],
            'mobile_app.show_diet_plans' => ['required', 'boolean'],
            'mobile_app.show_workout_levels' => ['required', 'boolean'],
            'mobile_app.enable_class_booking' => ['required', 'boolean'],
            'mobile_app.enable_progress_tracking' => ['required', 'boolean'],

            'membership' => ['required', 'array'],
            'membership.grace_period' => ['nullable', 'string', 'max:100'],
            'membership.auto_expire' => ['required', 'boolean'],
            'membership.allow_walkins' => ['required', 'boolean'],
            'membership.expiry_reminder' => ['required', 'boolean'],

            'payments' => ['required', 'array'],
            'payments.default_currency' => ['required', Rule::in(['TZS', 'USD', 'KES'])],
            'payments.payment_methods' => ['required', Rule::in(['Cash, M-Pesa, Card', 'Cash Only', 'Online Only'])],
            'payments.receipt_prefix' => ['nullable', 'string', 'max:50'],
            'payments.allow_partial_payment' => ['required', 'boolean'],

            'notifications' => ['required', 'array'],
            'notifications.membership_expiry_alerts' => ['required', 'boolean'],
            'notifications.payment_confirmation_sms' => ['required', 'boolean'],
            'notifications.class_booking_notifications' => ['required', 'boolean'],
            'notifications.workout_reminders' => ['required', 'boolean'],

            'users_roles' => ['required', 'array'],
            'users_roles.default_staff_role' => ['required', Rule::in(['Admin', 'Manager', 'Trainer', 'Receptionist'])],
            'users_roles.allow_staff_account_creation' => ['required', 'boolean'],
            'users_roles.trainer_content_access' => ['required', Rule::in(['Read Only', 'Create & Edit', 'Full Access'])],
            'users_roles.payment_permission' => ['required', Rule::in(['Admin Only', 'Admin & Manager', 'All Staff'])],

            'security' => ['required', 'array'],
            'security.two_factor_authentication' => ['required', Rule::in(['Enabled', 'Disabled'])],
            'security.session_timeout' => ['required', Rule::in(['15 Minutes', '30 Minutes', '1 Hour'])],
            'security.password_policy' => ['required', Rule::in(['Strong', 'Medium', 'Basic'])],
            'security.login_access' => ['required', Rule::in(['Admin Only', 'Admin & Staff'])],
        ];
    }

    private function defaults(): array
    {
        return [
            'gym_profile' => [
                'gym_name' => 'AUREX Performance Arena',
                'email' => 'info@aurexgym.com',
                'phone' => '+255 712 345 678',
                'location' => 'Arusha, Tanzania',
                'description' => 'Premium fitness facility offering gym access, classes, personal training, diet plans and mobile app workouts.',
            ],
            'mobile_app' => [
                'allow_registration' => true,
                'require_membership' => true,
                'show_diet_plans' => true,
                'show_workout_levels' => true,
                'enable_class_booking' => true,
                'enable_progress_tracking' => true,
            ],
            'membership' => [
                'grace_period' => '3 days',
                'auto_expire' => true,
                'allow_walkins' => true,
                'expiry_reminder' => true,
            ],
            'payments' => [
                'default_currency' => 'TZS',
                'payment_methods' => 'Cash, M-Pesa, Card',
                'receipt_prefix' => 'AUX-REC',
                'allow_partial_payment' => false,
            ],
            'notifications' => [
                'membership_expiry_alerts' => true,
                'payment_confirmation_sms' => true,
                'class_booking_notifications' => true,
                'workout_reminders' => true,
            ],
            'users_roles' => [
                'default_staff_role' => 'Manager',
                'allow_staff_account_creation' => true,
                'trainer_content_access' => 'Create & Edit',
                'payment_permission' => 'Admin & Manager',
            ],
            'security' => [
                'two_factor_authentication' => 'Disabled',
                'session_timeout' => '30 Minutes',
                'password_policy' => 'Strong',
                'login_access' => 'Admin & Staff',
            ],
        ];
    }
}
