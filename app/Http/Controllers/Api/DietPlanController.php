<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DietPlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'diet_plans' => DietPlan::query()->latest()->get(),
        ]);
    }

    public function show(DietPlan $dietPlan): JsonResponse
    {
        return response()->json([
            'diet_plan' => $dietPlan,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dietPlan = DietPlan::create($this->validatedData($request));

        return response()->json([
            'message' => 'Diet plan created successfully.',
            'diet_plan' => $dietPlan,
        ], 201);
    }

    public function update(Request $request, DietPlan $dietPlan): JsonResponse
    {
        $dietPlan->update($this->validatedData($request, $dietPlan));

        return response()->json([
            'message' => 'Diet plan updated successfully.',
            'diet_plan' => $dietPlan->fresh(),
        ]);
    }

    public function destroy(DietPlan $dietPlan): JsonResponse
    {
        $this->deleteStoredFile($dietPlan->cover_image_url);
        $dietPlan->delete();

        return response()->json([
            'message' => 'Diet plan deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['required', Rule::in(['Weight Loss', 'Muscle Gain', 'General Fitness', 'High Protein', 'Balanced Diet'])],
            'workout_level' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced', 'Elite', 'All Levels'])],
            'diet_type' => ['required', Rule::in(['Normal', 'Vegetarian', 'Vegan', 'Keto', 'Low Carb', 'High Protein'])],
            'daily_calories' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'protein' => ['nullable', 'string', 'max:100'],
            'carbs' => ['nullable', 'string', 'max:100'],
            'fat' => ['nullable', 'string', 'max:100'],
            'fiber' => ['nullable', 'string', 'max:100'],
            'meals' => ['nullable', 'array'],
            'meals.*.name' => ['required_with:meals', 'string', 'max:100'],
            'meals.*.food' => ['required_with:meals', 'string', 'max:500'],
            'meals.*.calories' => ['nullable', 'string', 'max:100'],
            'meal_instructions' => ['nullable', 'string'],
            'nutritionist_notes' => ['nullable', 'string'],
            'cover_image_file' => ['nullable', 'image', 'max:5120'],
            'show_in_mobile_app' => ['required', 'boolean'],
            'access_type' => ['required', Rule::in(['Free', 'Premium', 'Members Only'])],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?DietPlan $dietPlan = null): array
    {
        $data = $request->validate($this->rules());
        unset($data['cover_image_file']);

        if ($request->hasFile('cover_image_file')) {
            $this->deleteStoredFile($dietPlan?->cover_image_url);

            $data['cover_image_url'] = Storage::disk('public')->url(
                $request->file('cover_image_file')->store('diet-plans/covers', 'public')
            );
        }

        return $data;
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url || ! str_contains($url, '/storage/')) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $path), '/');

        if ($storagePath !== '') {
            Storage::disk('public')->delete($storagePath);
        }
    }
}
