<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService,
    ) {}

    /**
     * List all employees with pagination.
     *
     * Accepts optional query parameters:
     *   - limit  (default 20)
     *   - offset (default 0)
     */
    public function index(Request $request)
    {
        try {
            $limit = (int) $request->query('limit', 20);
            $offset = (int) $request->query('offset', 0);

            $employees = $this->employeeService->getAllEmployees([], $limit, $offset);

            return view('admin.employees.index', [
                'employees' => $employees,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch employees: ' . $e->getMessage());
        }
    }

    /**
     * Get a single employee's details and render profile view.
     */
    public function show(int $id, \App\Services\Odoo\AttendanceService $attendanceService, \App\Services\Odoo\PayrollService $payrollService)
    {
        try {
            $employee = $this->employeeService->getEmployee($id);

            if (!$employee) {
                return redirect()->route('admin.employees')->with('error', 'Employee not found.');
            }

            // Fetch recent attendance
            $now = \Carbon\Carbon::now();
            $dateFrom = $now->copy()->startOfMonth()->toDateString();
            $dateTo = $now->copy()->endOfMonth()->toDateString();
            $attendanceSummary = $attendanceService->getAttendanceHistory($id, $dateFrom, $dateTo);

            // Fetch recent payslips
            $recentPayslips = $payrollService->getPayslips($id);

            return view('admin.employees.show', [
                'employee'          => $employee,
                'attendanceSummary' => $attendanceSummary,
                'recentPayslips'    => $recentPayslips,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.employees')->with('error', 'Failed to fetch employee: ' . $e->getMessage());
        }
    }

    /**
     * Create a new employee record in Odoo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'work_email'    => 'nullable|email|max:255',
            'job_title'     => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'work_phone'    => 'nullable|string|max:50',
            'mobile_phone'  => 'nullable|string|max:50',
            'gender'        => 'nullable|string|in:male,female,other',
            'birthday'      => 'nullable|date',
            'marital'       => 'nullable|string|in:single,married,cohabitant,widower,divorced',
            'identification_id' => 'nullable|string|max:100',
        ]);

        try {
            $result = $this->employeeService->createEmployee($validated);

            return back()->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create employee: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing employee record in Odoo.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'work_email'    => 'nullable|email|max:255',
            'job_title'     => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'work_phone'    => 'nullable|string|max:50',
            'mobile_phone'  => 'nullable|string|max:50',
            'gender'        => 'nullable|string|in:male,female,other',
            'birthday'      => 'nullable|date',
            'marital'       => 'nullable|string|in:single,married,cohabitant,widower,divorced',
            'identification_id' => 'nullable|string|max:100',
        ]);

        try {
            $this->employeeService->updateEmployee($id, $validated);
            return back()->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update employee: ' . $e->getMessage());
        }
    }
}
