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
     * Authenticate user against Odoo via JSON-RPC.
     *
     * Validates email and password, authenticates with Odoo, retrieves the
     * employee record, checks for HR Manager group membership, and stores
     * all relevant data in the session.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            // Authenticate against Odoo
            $authResult = $this->odooService->authenticate(
                $validated['email'],
                $validated['password']
            );

            if (!$authResult || empty($authResult['uid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            $uid = $authResult['uid'];
            $sessionId = $authResult['session_id'] ?? null;

            // Restore session on the service so subsequent calls work
            if ($sessionId) {
                $this->odooService->setSession($sessionId, $uid);
            }

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
            $request->session()->put('user_name', $userName);
            $request->session()->put('employee_id', $employeeId);
            $request->session()->put('is_admin', $isAdmin);

            $request->session()->save();

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => [
                    'uid'         => $uid,
                    'user_name'   => $userName,
                    'employee_id' => $employeeId,
                    'is_admin'    => $isAdmin,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Flush the session and log the user out.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->session()->flush();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the currently authenticated user's session data.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'uid'         => $request->session()->get('odoo_uid'),
                'user_name'   => $request->session()->get('user_name'),
                'employee_id' => $request->session()->get('employee_id'),
                'is_admin'    => $request->session()->get('is_admin', false),
            ],
        ]);
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
            // Get the HR Manager group's actual ID from ir.model.data
            $groupData = $this->odooService->searchRead(
                'ir.model.data',
                [['module', '=', 'hr'], ['name', '=', 'group_hr_manager']],
                ['res_id'],
                0,
                1
            );

            if (empty($groupData)) {
                return false;
            }

            $hrManagerGroupId = $groupData[0]['res_id'];

            // Read the user's group IDs
            $userData = $this->odooService->execute_kw(
                'res.users',
                'read',
                [[$uid]],
                ['fields' => ['groups_id']]
            );

            if (empty($userData) || empty($userData[0]['groups_id'])) {
                return false;
            }

            $userGroupIds = $userData[0]['groups_id'];

            return in_array($hrManagerGroupId, $userGroupIds);
        } catch (\Exception $e) {
            // If we can't determine admin status, default to non-admin
            return false;
        }
    }
}
