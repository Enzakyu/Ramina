<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Odoo\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService,
    ) {}

    /**
     * Return all available leave types.
     */
    public function types(): JsonResponse
    {
        try {
            $types = $this->leaveService->getLeaveTypes();

            return response()->json([
                'success' => true,
                'data'    => $types,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leave types: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List leave requests for the session employee.
     *
     * Accepts optional query parameter:
     *   - state (e.g., 'draft', 'confirm', 'validate', 'refuse')
     */
    public function index(Request $request): JsonResponse
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found in session.',
            ], 404);
        }

        try {
            $state = $request->query('state');

            $leaves = $this->leaveService->getLeaveRequests($employeeId, $state);

            return response()->json([
                'success' => true,
                'data'    => $leaves,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leave requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new leave request.
     *
     * Required fields: leave_type_id, date_from, date_to
     * Optional: description
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|integer',
            'date_from'     => 'required|date',
            'date_to'       => 'required|date|after_or_equal:date_from',
            'description'   => 'nullable|string|max:1000',
        ]);

        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found in session.',
            ], 404);
        }

        try {
            $result = $this->leaveService->requestLeave(
                $employeeId,
                $validated['leave_type_id'],
                $validated['date_from'],
                $validated['date_to'],
                $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully.',
                'data'    => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit leave request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail of a specific leave request.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $leaves = $this->leaveService->getLeaveRequests(null, null);

            // Filter to find the specific leave request by ID
            $leave = collect($leaves)->firstWhere('id', $id);

            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $leave,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leave request: ' . $e->getMessage(),
            ], 500);
        }
    }
}
