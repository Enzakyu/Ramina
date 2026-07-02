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
            return redirect()->route('employee.dashboard')->with('error', 'Forbidden. Administrator access required.');
        }

        return $next($request);
    }
}
