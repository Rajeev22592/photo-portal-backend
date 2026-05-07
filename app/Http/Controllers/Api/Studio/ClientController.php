<?php

namespace App\Http\Controllers\Api\Studio;

use App\Http\Controllers\Api\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $clients = $tenant->clients()
            ->withCount('galleries')
            ->when($request->has('page'), fn ($q) => $q->paginate(15), fn ($q) => $q->get());

        $data = $clients instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $clients->through(fn (Client $c) => $this->clientResource($c))
            : $clients->map(fn (Client $c) => $this->clientResource($c));

        return $this->success($data);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $client = $tenant->clients()->with('galleries')->find($id);
        if (! $client) {
            return $this->error('Client not found.', 404);
        }

        $resource = $this->clientResource($client);
        $resource['galleries'] = $client->galleries->map(fn ($g) => $this->galleryResource($g));
        return $this->success($resource);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $validated['tenant_id'] = $tenant->id;
        if (! empty($validated['password'])) {
            $validated['password'] = $validated['password'];
        } else {
            unset($validated['password']);
        }

        $client = Client::create($validated);
        return $this->success($this->clientResource($client->loadCount('galleries')), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $client = $tenant->clients()->find($id);
        if (! $client) {
            return $this->error('Client not found.', 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (isset($validated['password']) && $validated['password'] === '') {
            unset($validated['password']);
        }

        $client->update($validated);
        return $this->success($this->clientResource($client->loadCount('galleries')));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (! $tenant) {
            return $this->error('Tenant not found.', 403);
        }

        $client = $tenant->clients()->find($id);
        if (! $client) {
            return $this->error('Client not found.', 404);
        }

        User::where('client_id', $client->id)->update(['client_id' => null]);
        $client->delete();
        return $this->success(null);
    }

    private function clientResource(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'tenant_id' => $client->tenant_id,
            'galleries_count' => $client->galleries_count ?? $client->galleries()->count(),
            'created_at' => $client->created_at?->toIso8601String(),
        ];
    }

    private function galleryResource($gallery): array
    {
        return [
            'id' => $gallery->id,
            'title' => $gallery->title,
            'name' => $gallery->name,
            'client_id' => $gallery->client_id,
            'is_public' => $gallery->is_public,
            'event_date' => $gallery->event_date?->format('Y-m-d'),
            'cover_image' => $gallery->cover_image,
            'total_media_count' => $gallery->media_count ?? $gallery->media()->count(),
        ];
    }
}
