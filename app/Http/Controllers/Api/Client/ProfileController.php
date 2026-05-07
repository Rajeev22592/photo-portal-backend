<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $client = $request->user()->client;
        if (! $client) {
            return $this->error('Client not found.', 403);
        }
        return $this->success($this->clientResource($client));
    }

    public function update(Request $request): JsonResponse
    {
        $client = $request->user()->client;
        if (! $client) {
            return $this->error('Client not found.', 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ]);

        if (isset($validated['password']) && $validated['password'] === '') {
            unset($validated['password']);
        }

        $client->update($validated);
        return $this->success($this->clientResource($client->fresh()));
    }

    private function clientResource($client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'tenant_id' => $client->tenant_id,
            'galleries_count' => $client->galleries()->count(),
            'created_at' => $client->created_at?->toIso8601String(),
        ];
    }
}
