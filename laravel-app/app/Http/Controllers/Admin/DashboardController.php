<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\AttendanceService;
use App\Services\Odoo\EmployeeService;
use App\Services\Odoo\LeaveService;
use App\Services\Odoo\OvertimeService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
        protected AttendanceService $attendanceService,
        protected LeaveService $leaveService,
        protected OvertimeService $overtimeService,
    ) {}

    /**
     * Admin dashboard with aggregated company-wide metrics.
     *
     * Returns total employee count, today's attendance breakdown,
     * pending leave requests count, and pending overtime count.
     */
    public function index(): JsonResponse
    {
        try {
            // Total employees
            $employees = $this->employeeService->getAllEmployees();
            $totalEmployees = is_array($employees) ? count($employees) : 0;

            // Today's attendance summary
            $todayAttendance = $this->attendanceService->getAllAttendanceToday();
            $presentCount = is_array($todayAttendance) ? count($todayAttendance) : 0;
            $absentCount = $totalEmployees - $presentCount;

            // Pending leave requests
            $pendingLeaves = $this->leaveService->getPendingLeaves();
            $pendingLeavesCount = is_array($pendingLeaves) ? count($pendingLeaves) : 0;

            // Pending overtime approvals
            $pendingOvertime = $this->overtimeService->getAllPendingOvertime();
            $pendingOvertimeCount = is_array($pendingOvertime) ? count($pendingOvertime) : 0;

            return response()->json([
                'success' => true,
                'data'    => [
                    'total_employees'  => $totalEmployees,
                    'attendance'       => [
                        'present' => $presentCount,
                        'absent'  => max(0, $absentCount),
                    ],
                    'pending_leaves'   => $pendingLeavesCount,
                    'pending_overtime' => $pendingOvertimeCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load admin dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }
}
