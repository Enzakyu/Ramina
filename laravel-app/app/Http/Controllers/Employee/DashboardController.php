<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Odoo\AttendanceService;
use App\Services\Odoo\EmployeeService;
use App\Services\Odoo\LeaveService;
use App\Services\Odoo\OvertimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected AttendanceService $attendanceService,
        protected LeaveService $leaveService,
        protected OvertimeService $overtimeService,
    ) {}

    /**
     * Employee dashboard aggregating key information.
     *
     * Returns employee info, today's attendance status, pending leave count,
     * and the current month's overtime summary.
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
            // Fetch employee details
            $employee = $this->employeeService->getEmployee($employeeId);

            // Fetch today's attendance status
            $attendanceStatus = $this->attendanceService->getAttendanceStatus($employeeId);

            // Fetch pending leave requests count
            $pendingLeaves = $this->leaveService->getPendingLeaves($employeeId);
            $pendingLeavesCount = is_array($pendingLeaves) ? count($pendingLeaves) : 0;

            // Fetch overtime summary for the current month
            $now = now();
            $overtimeSummary = $this->overtimeService->getOvertimeSummary(
                $employeeId,
                $now->startOfMonth()->toDateString(),
                $now->endOfMonth()->toDateString()
            );

            return response()->json([
                'success' => true,
                'data'    => [
                    'employee'           => $employee,
                    'attendance_status'  => $attendanceStatus,
                    'pending_leaves'     => $pendingLeavesCount,
                    'overtime_summary'   => $overtimeSummary,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }
}
