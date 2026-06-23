<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GymClassController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'classes' => GymClass::query()->latest()->get(),
        ]);
    }

    public function show(GymClass $class): JsonResponse
    {
        return response()->json([
            'class' => $class,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $class = GymClass::create($request->validate($this->rules()));

        return response()->json([
            'message' => 'Class created successfully.',
            'class' => $class,
        ], 201);
    }

    public function update(Request $request, GymClass $class): JsonResponse
    {
        $class->update($request->validate($this->rules()));

        return response()->json([
            'message' => 'Class updated successfully.',
            'class' => $class->fresh(),
        ]);
    }

    public function destroy(GymClass $class): JsonResponse
    {
        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'class_type' => ['required', Rule::in(['HIIT', 'Strength', 'Cardio', 'Yoga', 'Beginner Fitness', 'Bodybuilding'])],
            'workout_level' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced', 'Elite', 'All Levels'])],
            'status' => ['required', Rule::in(['Active', 'Draft', 'Cancelled', 'Hidden'])],
            'capacity' => ['required', 'integer', 'min:1'],
            'booked_slots' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'class_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'repeat_schedule' => ['required', Rule::in(['Does Not Repeat', 'Daily', 'Weekly', 'Monthly'])],
            'booking_required' => ['required', 'boolean'],
            'booking_deadline' => ['nullable', 'string', 'max:255'],
            'cancellation_deadline' => ['nullable', 'string', 'max:255'],
            'late_entry_limit' => ['nullable', 'string', 'max:255'],
            'waitlist_limit' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'show_in_mobile_app' => ['required', 'boolean'],
            'allow_booking_from_app' => ['required', 'boolean'],
            'access_type' => ['required', Rule::in(['Free', 'Members Only', 'Premium'])],
        ];
    }
}
