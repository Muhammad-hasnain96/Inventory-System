<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isBranchManager()) {
            return response()->json(['error' => 'You must be a branch manager'], 403);
        }

        return $next($request);
    }
}
