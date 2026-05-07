<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** @var array<string> */
    private readonly array $roles;

    public function __construct(string $roles = '')
    {
        $this->roles = $roles ? array_map('trim', explode(',', $roles)) : [];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, $this->roles, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
