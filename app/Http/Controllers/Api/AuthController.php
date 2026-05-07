<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerStudio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
            'studio_name' => ['required', 'string', 'max:255'],
        ]);

        $tenant = Tenant::create([
            'studio_name' => $validated['studio_name'],
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'tenant',
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $this->userResponse($user),
            'tenant' => $this->tenantResponse($tenant),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        $data = [
            'token' => $token,
            'user' => $this->userResponse($user),
        ];

        if ($user->tenant_id) {
            $data['tenant'] = $this->tenantResponse($user->tenant);
        }
        if ($user->client_id) {
            $data['client'] = $this->clientResponse($user->client);
        }

        return $this->success($data);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return $this->success(null);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->userResponse($request->user()));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return $this->success(['message' => __($status)]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return $this->success(['message' => __('Password reset successfully.')]);
    }

    private function userResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'tenant_id' => $user->tenant_id,
            'phone' => $user->phone,
        ];
    }

    private function tenantResponse(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'studio_name' => $tenant->studio_name,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'is_active' => $tenant->is_active,
        ];
    }

    private function clientResponse(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'tenant_id' => $client->tenant_id,
        ];
    }
}
