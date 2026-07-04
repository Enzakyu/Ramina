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
    public function index(Request $request)
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
        }

        try {
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            $payslips = $this->payrollService->getPayslips($employeeId, $dateFrom, $dateTo);

            return view('employee.payslips', ['payslips' => $payslips]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch payslips: ' . $e->getMessage());
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
                return redirect()->route('employee.payslips')->with('error', 'Payslip not found.');
            }

            return view('employee.payslip-detail', [
                'payslip' => $payslip['payslip'],
                'payslipLines' => $payslip['lines'],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch payslip detail: ' . $e->getMessage());
        }
    }
}
