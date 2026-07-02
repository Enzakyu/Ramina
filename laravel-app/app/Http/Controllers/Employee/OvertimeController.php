<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Odoo\OvertimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function __construct(
        protected OvertimeService $overtimeService,
    ) {}

    /**
     * List overtime records for the session employee.
     *
     * Accepts optional query parameters:
     *   - date_from (Y-m-d)
     *   - date_to   (Y-m-d)
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
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $records = $this->overtimeService->getOvertimeRecords($employeeId, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data'    => $records,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overtime records: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overtime summary for the current month.
     */
    public function summary(Request $request): JsonResponse
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found in session.',
            ], 404);
        }

        try {
            $now = now();
            $summary = $this->overtimeService->getOvertimeSummary(
                $employeeId,
                $now->startOfMonth()->toDateString(),
                $now->endOfMonth()->toDateString()
            );

            return response()->json([
                'success' => true,
                'data'    => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overtime summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}
