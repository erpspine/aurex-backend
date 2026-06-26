<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BodyPart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BodyPartController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'body_parts' => BodyPart::query()
                ->withCount('exercises')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(BodyPart $bodyPart): JsonResponse
    {
        return response()->json([
            'body_part' => $bodyPart->loadCount('exercises'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $bodyPart = BodyPart::create($this->validatedData($request));

        return response()->json([
            'message' => 'Body part created successfully.',
            'body_part' => $bodyPart->loadCount('exercises'),
        ], 201);
    }

    public function update(Request $request, BodyPart $bodyPart): JsonResponse
    {
        $bodyPart->update($this->validatedData($request, $bodyPart));

        return response()->json([
            'message' => 'Body part updated successfully.',
            'body_part' => $bodyPart->fresh()->loadCount('exercises'),
        ]);
    }

    public function destroy(BodyPart $bodyPart): JsonResponse
    {
        $this->deleteStoredFile($bodyPart->image_url);
        $bodyPart->delete();

        return response()->json([
            'message' => 'Body part deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?BodyPart $bodyPart = null): array
    {
        $nameRule = Rule::unique('body_parts', 'name');

        if ($bodyPart) {
            $nameRule->ignore($bodyPart->id);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', Rule::in(['Active', 'Draft', 'Hidden'])],
            'show_in_mobile_app' => ['required', 'boolean'],
            'publish_status' => ['required', Rule::in(['Published', 'Draft', 'Hidden'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?BodyPart $bodyPart = null): array
    {
        $data = $request->validate($this->rules($bodyPart));
        unset($data['image_file']);

        if ($request->hasFile('image_file')) {
            $this->deleteStoredFile($bodyPart?->image_url);

            $data['image_url'] = Storage::disk('public')->url(
                $request->file('image_file')->store('body-parts/images', 'public')
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
