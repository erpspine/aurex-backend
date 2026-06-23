<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'equipment' => Equipment::query()->latest()->get(),
        ]);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json([
            'equipment' => $equipment,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $equipment = Equipment::create($this->validatedData($request));

        return response()->json([
            'message' => 'Equipment created successfully.',
            'equipment' => $equipment,
        ], 201);
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $equipment->update($this->validatedData($request, $equipment));

        return response()->json([
            'message' => 'Equipment updated successfully.',
            'equipment' => $equipment->fresh(),
        ]);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $this->deleteStoredFile($equipment->operation_video_url);

        $equipment->delete();

        return response()->json([
            'message' => 'Equipment deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Equipment $equipment = null): array
    {
        $serialRule = Rule::unique('equipment', 'serial_number');

        if ($equipment) {
            $serialRule->ignore($equipment->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['Machines', 'Free Weights', 'Cardio', 'Benches', 'Accessories'])],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', $serialRule],
            'location' => ['nullable', 'string', 'max:255'],
            'primary_muscle_group' => ['nullable', 'string', 'max:100'],
            'secondary_muscle_group' => ['nullable', 'string', 'max:100'],
            'supported_level' => ['nullable', 'string', 'max:100'],
            'linked_exercises' => ['required', 'integer', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'integer', 'min:0'],
            'last_service_date' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Active', 'Maintenance', 'Inactive', 'Damaged'])],
            'maintenance_priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Urgent'])],
            'description' => ['nullable', 'string'],
            'safety_instructions' => ['nullable', 'string'],
            'operation_video_file' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,mkv', 'max:51200'],
            'show_in_mobile_app' => ['required', 'boolean'],
            'access_type' => ['required', Rule::in(['Free', 'Premium', 'Members Only'])],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Equipment $equipment = null): array
    {
        $data = $request->validate($this->rules($equipment));

        unset($data['operation_video_file']);

        if ($request->hasFile('operation_video_file')) {
            $this->deleteStoredFile($equipment?->operation_video_url);

            $data['operation_video_url'] = Storage::disk('public')->url(
                $request->file('operation_video_file')->store('equipment/videos', 'public')
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
