<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Odoo\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService,
    ) {}

    /**
     * List payslips for the session employee.
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

            $payslips = $this->payrollService->getPayslips($employeeId, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data'    => $payslips,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payslips: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed payslip information including salary lines.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $payslip = $this->payrollService->getPayslipDetail($id);

            if (!$payslip) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payslip not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $payslip,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payslip detail: ' . $e->getMessage(),
            ], 500);
        }
    }
}
