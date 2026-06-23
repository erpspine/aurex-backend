<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppBannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'app_banners' => AppBanner::query()
                ->orderBy('display_order')
                ->latest()
                ->get(),
        ]);
    }

    public function show(AppBanner $appBanner): JsonResponse
    {
        return response()->json([
            'app_banner' => $appBanner,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $appBanner = AppBanner::create($this->validatedData($request));

        return response()->json([
            'message' => 'App banner created successfully.',
            'app_banner' => $appBanner,
        ], 201);
    }

    public function update(Request $request, AppBanner $appBanner): JsonResponse
    {
        $appBanner->update($this->validatedData($request, $appBanner));

        return response()->json([
            'message' => 'App banner updated successfully.',
            'app_banner' => $appBanner->fresh(),
        ]);
    }

    public function destroy(AppBanner $appBanner): JsonResponse
    {
        $this->deleteStoredFile($appBanner->image_url);
        $appBanner->delete();

        return response()->json([
            'message' => 'App banner deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'banner_type' => ['required', Rule::in(['Home Banner', 'Workout Banner', 'Diet Banner', 'Membership Banner', 'Class Banner'])],
            'target_audience' => ['required', Rule::in(['All Users', 'Free Users', 'Premium Users', 'Members Only', 'New Users'])],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_action' => ['required', Rule::in(['Open Workouts', 'Open Diet Plans', 'Open Membership Plans', 'Open Classes', 'External Link'])],
            'action_url' => ['nullable', 'string', 'max:255'],
            'display_order' => ['required', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
            'show_in_mobile_app' => ['required', 'boolean'],
            'priority' => ['required', Rule::in(['Normal', 'High', 'Featured'])],
            'allow_dismiss' => ['required', 'boolean'],
            'background_style' => ['required', Rule::in(['Image', 'Gradient', 'Solid Color', 'Video Banner'])],
            'text_alignment' => ['required', Rule::in(['Left', 'Center', 'Right'])],
            'background_color' => ['required', 'string', 'max:30'],
            'accent_color' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?AppBanner $appBanner = null): array
    {
        $data = $request->validate($this->rules());
        unset($data['image_file']);

        if ($request->hasFile('image_file')) {
            $this->deleteStoredFile($appBanner?->image_url);

            $data['image_url'] = Storage::disk('public')->url(
                $request->file('image_file')->store('mobile-app/banners', 'public')
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
