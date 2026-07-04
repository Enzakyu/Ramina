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
    public function index()
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
            $pendingLeavesList = $this->leaveService->getPendingLeaves();
            $pendingLeaves = is_array($pendingLeavesList) ? count($pendingLeavesList) : 0;

            // Unique checked in employees today
            $checkedInToday = 0;
            if (is_array($todayAttendance) && count($todayAttendance) > 0) {
                $uniqueEmployees = [];
                foreach ($todayAttendance as $att) {
                    if (isset($att['employee_id']) && is_array($att['employee_id'])) {
                        $uniqueEmployees[$att['employee_id'][0]] = true;
                    }
                }
                $checkedInToday = count($uniqueEmployees);
            }

            // Payroll This Month
            $payrollService = app(\App\Services\Odoo\PayrollService::class);
            $payslips = $payrollService->getAllPayslips((int)date('m'), (int)date('Y'));
            $payrollTotal = 0;
            foreach ($payslips as $slip) {
                $payrollTotal += $slip['net_wage'] ?? 0;
            }

            // Recent Activity (we'll just use the today's attendance records)
            $recentActivity = is_array($todayAttendance) ? array_slice($todayAttendance, 0, 5) : [];

            return view('admin.dashboard', [
                'totalEmployees' => $totalEmployees,
                'checkedInToday' => $checkedInToday,
                'pendingLeaves' => $pendingLeaves,
                'payrollTotal' => $payrollTotal,
                'recentActivity' => $recentActivity,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load admin dashboard: ' . $e->getMessage());
        }
    }
}
