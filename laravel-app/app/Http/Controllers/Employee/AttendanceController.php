<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Odoo\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * Get the current attendance status (checked in or out) for the session employee.
     */
    public function index(Request $request)
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
        }

        try {
            $status = $this->attendanceService->getAttendanceStatus($employeeId);
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $history = $this->attendanceService->getAttendanceHistory($employeeId, $dateFrom, $dateTo);

            return view('employee.attendance', [
                'status'  => $status,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch attendance status: ' . $e->getMessage());
        }
    }

    /**
     * Toggle attendance (check-in / check-out) for the session employee.
     */
    public function toggle(Request $request)
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
        }

        try {
            $result = $this->attendanceService->toggleAttendance($employeeId);
            return response()->json([
                'success' => true,
                'message' => 'Attendance toggled successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}
