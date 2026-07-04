<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Odoo\OdooService;

class OdooAuth
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the session contains valid Odoo authentication data
     * and restores the Odoo session state on the service instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->session()->get('odoo_session_id');
        $uid = $request->session()->get('odoo_uid');

        if (!$sessionId || !$uid) {
            return redirect()->route('login.form')->with('error', 'Unauthenticated. Please log in.');
        }

        // Resolve OdooService from the container and restore session state
        $odooService = app(OdooService::class);
        $odooService->setSession($sessionId, $uid);

        $password = $request->session()->get('odoo_password');
        if ($password) {
            $odooService->setApiKey($password);
        }

        return $next($request);
    }
}
