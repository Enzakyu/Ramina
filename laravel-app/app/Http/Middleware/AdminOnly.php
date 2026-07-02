<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has admin (HR Manager) privileges.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('is_admin', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Admin access required',
            ], 403);
        }

        return $next($request);
    }
}
