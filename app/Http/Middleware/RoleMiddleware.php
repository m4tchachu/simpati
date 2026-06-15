<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // User must be authenticated
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user has any of the allowed roles
        foreach ($roles as $role) {
            if ($request->user()->role->value === $role) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Forbidden. This action requires: ' . implode(' or ', $roles),
        ], 403);
    }
}
