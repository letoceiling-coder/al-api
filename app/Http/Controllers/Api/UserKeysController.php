<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAIKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserKeysController extends Controller
{
    /**
     * List all user keys
     */
    public function index(Request $request): JsonResponse
    {
        $keys = UserAIKey::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'provider' => $key->provider,
                    'label' => $key->label,
                    'masked_key' => $key->masked_key,
                    'is_active' => $key->is_active,
                    'created_at' => $key->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'keys' => $keys,
        ]);
    }

    /**
     * Store a new key
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:gemini,openai',
            'api_key' => 'required|string|min:10',
            'label' => 'nullable|string|max:100',
        ]);

        $key = UserAIKey::create([
            'user_id' => $request->user()->id,
            'provider' => $validated['provider'],
            'api_key' => $validated['api_key'],
            'label' => $validated['label'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API key added successfully',
            'key' => [
                'id' => $key->id,
                'provider' => $key->provider,
                'label' => $key->label,
                'masked_key' => $key->masked_key,
                'is_active' => $key->is_active,
            ],
        ], 201);
    }

    /**
     * Show key details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $key = UserAIKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'key' => [
                'id' => $key->id,
                'provider' => $key->provider,
                'label' => $key->label,
                'masked_key' => $key->masked_key,
                'is_active' => $key->is_active,
                'created_at' => $key->created_at->toIso8601String(),
                'updated_at' => $key->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update key
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $key = UserAIKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $key->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'API key updated successfully',
            'key' => [
                'id' => $key->id,
                'provider' => $key->provider,
                'label' => $key->label,
                'masked_key' => $key->masked_key,
                'is_active' => $key->is_active,
            ],
        ]);
    }

    /**
     * Delete key
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $key = UserAIKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $key->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key deleted successfully',
        ]);
    }

    /**
     * Toggle key active status
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $key = UserAIKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $key->update(['is_active' => !$key->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'API key ' . ($key->is_active ? 'enabled' : 'disabled'),
            'key' => [
                'id' => $key->id,
                'is_active' => $key->is_active,
            ],
        ]);
    }
}
