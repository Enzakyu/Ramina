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
    public function index(Request $request)
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
        }

        try {
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');
            
            $records = $this->overtimeService->getOvertimeRecords($employeeId, $dateFrom, $dateTo);
            
            $now = now();
            $summary = $this->overtimeService->getOvertimeSummary(
                $employeeId,
                $now->startOfMonth()->toDateString(),
                $now->endOfMonth()->toDateString()
            );

            return view('employee.overtime', [
                'records' => $records,
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch overtime records: ' . $e->getMessage());
        }
    }
}
