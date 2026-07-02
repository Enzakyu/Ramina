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
    public function status(Request $request): JsonResponse
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found in session.',
            ], 404);
        }

        try {
            $status = $this->attendanceService->getAttendanceStatus($employeeId);

            return response()->json([
                'success' => true,
                'data'    => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle attendance (check-in / check-out) for the session employee.
     */
    public function toggle(Request $request): JsonResponse
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found in session.',
            ], 404);
        }

        try {
            $result = $this->attendanceService->toggleAttendance($employeeId);

            return response()->json([
                'success' => true,
                'message' => 'Attendance toggled successfully.',
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle attendance: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance history for the session employee.
     *
     * Accepts optional query parameters:
     *   - date_from (Y-m-d)
     *   - date_to   (Y-m-d)
     */
    public function history(Request $request): JsonResponse
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found in session.',
            ], 404);
        }

        try {
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $history = $this->attendanceService->getAttendanceHistory(
                $employeeId,
                $dateFrom,
                $dateTo
            );

            return response()->json([
                'success' => true,
                'data'    => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance history: ' . $e->getMessage(),
            ], 500);
        }
    }
}
