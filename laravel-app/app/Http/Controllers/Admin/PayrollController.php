<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\PayrollService;
use App\Services\Odoo\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService,
        protected EmployeeService $employeeService,
    ) {}

    /**
     * List all payslips with optional month/year filtering.
     *
     * Accepts optional query parameters:
     *   - month (1-12)
     *   - year  (e.g. 2026)
     */
    public function index(Request $request)
    {
        try {
            $month = $request->query('month');
            $year = $request->query('year');

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
            $employees = $this->employeeService->getAllEmployees();

            $totalNet = 0;
            foreach ($payslips as $slip) {
                $totalNet += $slip['net_wage'] ?? 0;
            }

            return view('admin.payroll', [
                'payslips' => $payslips,
                'employees' => $employees,
                'totals' => ['net' => $totalNet],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch payslips: ' . $e->getMessage());
        }
    }

    /**
     * Generate payslips for specified employees and date range.
     *
     * Required fields: employee_ids (array of int), date_from, date_to
     */
    public function generate(Request $request)
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

            return back()->with('success', 'Payslips generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate payslips: ' . $e->getMessage());
        }
    }

    /**
     * Confirm/finalize a draft payslip.
     */
    public function confirm(int $id)
    {
        try {
            $result = $this->payrollService->confirmPayslip($id);

            return back()->with('success', 'Payslip confirmed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to confirm payslip: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed payslip information including salary lines.
     */
    public function show(int $id)
    {
        try {
            $payslip = $this->payrollService->getPayslipDetail($id);

            if (!$payslip) {
                return redirect()->route('admin.payroll')->with('error', 'Payslip not found.');
            }

            return view('admin.payroll-detail', [
                'payslip' => $payslip['payslip'],
                'payslipLines' => $payslip['lines'],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch payslip detail: ' . $e->getMessage());
        }
    }
}
