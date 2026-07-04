<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Odoo\OdooService;
use App\Services\Odoo\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        protected OdooService $odooService,
        protected EmployeeService $employeeService,
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (request()->session()->has('odoo_uid')) {
            return request()->session()->get('is_admin') 
                ? redirect()->route('admin.dashboard') 
                : redirect()->route('employee.dashboard');
        }
        return view('auth.login');
    }

    /**
     * Authenticate user against Odoo via JSON-RPC.
     *
     * Validates email and password, authenticates with Odoo, retrieves the
     * employee record, checks for HR Manager group membership, and stores
     * all relevant data in the session.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Authenticate against Odoo
            $authResult = $this->odooService->authenticate(
                config('odoo.db'),
                $validated['email'],
                $validated['password']
            );

            if (!$authResult || empty($authResult['uid'])) {
                return back()->with('error', 'Invalid email or password.');
            }

            $uid = $authResult['uid'];
            $sessionId = $authResult['session_id'] ?? null;

            // Restore session on the service so subsequent calls work
            if ($sessionId) {
                $this->odooService->setSession($sessionId, $uid);
            }
            
            // Crucial: We must override the API key for the current request
            // so that subsequent execute_kw calls authenticate as the logged-in user
            $this->odooService->setApiKey($validated['password']);

            // Fetch employee record linked to this Odoo user
            $employee = $this->employeeService->getEmployeeByUserId($uid);

            // Determine admin status by checking HR Manager group membership
            $isAdmin = $this->checkAdminGroup($uid);

            // Build user data
            $userName = $employee['name'] ?? ($authResult['name'] ?? 'User');
            $employeeId = $employee['id'] ?? null;

            // Store session data
            $request->session()->put('odoo_session_id', $sessionId);
            $request->session()->put('odoo_uid', $uid);
            $request->session()->put('odoo_password', $validated['password']);
            $request->session()->put('user_name', $userName);
            $request->session()->put('employee_id', $employeeId);
            $request->session()->put('is_admin', $isAdmin);

            $request->session()->save();

            if ($isAdmin) {
                return redirect()->route('admin.dashboard')->with('success', 'Welcome, Admin!');
            }
            return redirect()->route('employee.dashboard')->with('success', 'Welcome back!');
        } catch (\Exception $e) {
            return back()->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Flush the session and log the user out.
     */
    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login.form')->with('success', 'Logged out successfully.');
    }

    /**
     * Check if the given user belongs to the hr.group_hr_manager group.
     *
     * Queries res.users for the user's groups_id field, then resolves the
     * HR Manager group's ID via ir.model.data and checks for membership.
     */
    private function checkAdminGroup(int $uid): bool
    {
        try {
            $groupData = $this->odooService->searchRead(
                'ir.model.data',
                [['module', '=', 'hr'], ['name', '=', 'group_hr_manager']],
                ['res_id'],
                ['offset' => 0, 'limit' => 1]
            );

            if (empty($groupData)) {
                return false;
            }

            $hrManagerGroupId = $groupData[0]['res_id'];

            // Check if the user is in the HR Manager group
            $groupCheck = $this->odooService->searchRead(
                'res.groups',
                [['id', '=', $hrManagerGroupId], ['user_ids', 'in', [$uid]]],
                ['id']
            );

            return !empty($groupCheck);
        } catch (\Exception $e) {
            // If we can't determine admin status, default to non-admin
            return false;
        }
    }
}
