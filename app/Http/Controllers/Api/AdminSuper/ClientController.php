<?php

namespace App\Http\Controllers\Api\AdminSuper;

use App\Http\Controllers\Api\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::with('tenant')->withCount('galleries')
            ->when($request->has('page'), fn ($q) => $q->paginate(15), fn ($q) => $q->get())
            ->map(fn (Client $c) => $this->resource($c));
        return $this->success($clients);
    }

    public function show(int $id): JsonResponse
    {
        $client = Client::with(['tenant', 'galleries'])->withCount('galleries')->find($id);
        if (! $client) {
            return $this->error('Client not found.', 404);
        }
        $r = $this->resource($client);
        $r['galleries'] = $client->galleries->map(fn ($g) => [
            'id' => $g->id, 'title' => $g->title, 'name' => $g->name, 'client_id' => $g->client_id,
            'is_public' => $g->is_public, 'event_date' => $g->event_date?->format('Y-m-d'),
        ]);
        return $this->success($r);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);

        $client = Client::create($validated);
        return $this->success($this->resource($client->loadCount('galleries')), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $client = Client::find($id);
        if (! $client) {
            return $this->error('Client not found.', 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
        ]);

        if (isset($validated['password']) && $validated['password'] === '') {
            unset($validated['password']);
        }

        $client->update($validated);
        return $this->success($this->resource($client->loadCount('galleries')));
    }

    public function destroy(int $id): JsonResponse
    {
        $client = Client::find($id);
        if (! $client) {
            return $this->error('Client not found.', 404);
        }
        $client->delete();
        return $this->success(null);
    }

    private function resource(Client $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'phone' => $c->phone,
            'tenant_id' => $c->tenant_id,
            'galleries_count' => $c->galleries_count ?? $c->galleries()->count(),
            'created_at' => $c->created_at?->toIso8601String(),
        ];
    }
}
