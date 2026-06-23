<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'trainers' => Trainer::query()->latest()->get(),
        ]);
    }

    public function show(Trainer $trainer): JsonResponse
    {
        return response()->json([
            'trainer' => $trainer,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $trainer = Trainer::create($request->validate($this->rules()));

        return response()->json([
            'message' => 'Trainer created successfully.',
            'trainer' => $trainer,
        ], 201);
    }

    public function update(Request $request, Trainer $trainer): JsonResponse
    {
        $trainer->update($request->validate($this->rules($trainer)));

        return response()->json([
            'message' => 'Trainer updated successfully.',
            'trainer' => $trainer->fresh(),
        ]);
    }

    public function destroy(Trainer $trainer): JsonResponse
    {
        $trainer->delete();

        return response()->json([
            'message' => 'Trainer deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Trainer $trainer = null): array
    {
        $emailRule = Rule::unique('trainers', 'email');

        if ($trainer) {
            $emailRule->ignore($trainer->id);
        }

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', $emailRule],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'specialty' => ['required', Rule::in([
                'Strength Training',
                'HIIT & Weight Loss',
                'Bodybuilding',
                'Beginner Fitness',
                'Cardio',
                'Nutrition Coach',
            ])],
            'experience' => ['nullable', 'string', 'max:100'],
            'certification' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'On Leave'])],
            'assigned_classes' => ['required', 'integer', 'min:0'],
            'assigned_clients' => ['required', 'integer', 'min:0'],
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'bio' => ['nullable', 'string'],
            'availability_days' => ['nullable', 'array'],
            'availability_days.*' => [Rule::in([
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ])],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'payment_type' => ['nullable', Rule::in(['Monthly Salary', 'Per Class', 'Per Client', 'Commission'])],
            'rate_amount' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['Cash', 'M-Pesa', 'Bank Transfer'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'allow_dashboard_login' => ['required', 'boolean'],
            'trainer_app_access' => ['required', 'boolean'],
            'role' => ['required', Rule::in(['Trainer', 'Senior Trainer', 'Head Coach'])],
        ];
    }
}
