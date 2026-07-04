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
    public function index(Request $request)
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
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

            // Fetch recent activity (last 7 days)
            $todayWIB = \Carbon\Carbon::now('Asia/Jakarta');
            $todayStr = $todayWIB->toDateString();
            $lastWeekStr = $todayWIB->copy()->subDays(7)->toDateString();
            
            $recentActivity = $this->attendanceService->getAttendanceHistory($employeeId, $lastWeekStr, $todayStr);
            
            // Today's hours is just from today (checking if the UTC check_in falls into today WIB)
            $todayStartUTC = $todayWIB->copy()->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s');
            
            $todayHours = 0;
            foreach ($recentActivity as $act) {
                // If the UTC check_in is on or after today's start in UTC, it counts as today
                if ($act['check_in'] >= $todayStartUTC) {
                    $todayHours += $act['worked_hours'] ?? 0;
                }
            }

            return view('employee.dashboard', [
                'employee'           => $employee,
                'attendanceStatus'   => $attendanceStatus,
                'pendingLeaves'      => $pendingLeavesCount,
                'overtimeSummary'    => $overtimeSummary,
                'todayHours'         => $todayHours,
                'recentActivity'     => $recentActivity,
            ]);
        } catch (\Exception $e) {
            // Log the error and display it so we can debug the redirect loop!
            \Illuminate\Support\Facades\Log::error('Dashboard Error', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            dd($e->getMessage());
        }
    }
}
