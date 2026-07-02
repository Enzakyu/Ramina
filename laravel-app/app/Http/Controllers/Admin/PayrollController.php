<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService,
    ) {}

    /**
     * List all payslips with optional month/year filtering.
     *
     * Accepts optional query parameters:
     *   - month (1-12)
     *   - year  (e.g. 2026)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $month = $request->query('month');
            $year = $request->query('year');

            // Build date filters from month/year if provided
            $dateFrom = null;
            $dateTo = null;

            if ($month && $year) {
                $dateFrom = sprintf('%04d-%02d-01', (int) $year, (int) $month);
                $dateTo = date('Y-m-t', strtotime($dateFrom));
            } elseif ($year) {
                $dateFrom = sprintf('%04d-01-01', (int) $year);
                $dateTo = sprintf('%04d-12-31', (int) $year);
            }

            $payslips = $this->payrollService->getAllPayslips($dateFrom, $dateTo);

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
     * Generate payslips for specified employees and date range.
     *
     * Required fields: employee_ids (array of int), date_from, date_to
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer',
            'date_from'      => 'required|date',
            'date_to'        => 'required|date|after_or_equal:date_from',
        ]);

        try {
            $result = $this->payrollService->generatePayslips(
                $validated['employee_ids'],
                $validated['date_from'],
                $validated['date_to']
            );

            return response()->json([
                'success' => true,
                'message' => 'Payslips generated successfully.',
                'data'    => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payslips: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm/finalize a draft payslip.
     */
    public function confirm(int $id): JsonResponse
    {
        try {
            $result = $this->payrollService->confirmPayslip($id);

            return response()->json([
                'success' => true,
                'message' => 'Payslip confirmed successfully.',
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payslip: ' . $e->getMessage(),
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
