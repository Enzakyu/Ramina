<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\Odoo\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService,
    ) {}

    public function index(Request $request)
    {
        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
        }

        try {
            $state = $request->query('state');
            $leaves = $this->leaveService->getLeaveRequests($employeeId, $state);
            $leaveTypes = $this->leaveService->getLeaveTypes();

            return view('employee.leaves', [
                'leaves' => $leaves,
                'leaveTypes' => $leaveTypes,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch leave requests: ' . $e->getMessage());
        }
    }

    /**
     * Create a new leave request.
     *
     * Required fields: leave_type_id, date_from, date_to
     * Optional: description
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|integer',
            'date_from'     => 'required|date',
            'date_to'       => 'required|date|after_or_equal:date_from',
            'description'   => 'nullable|string|max:1000',
        ]);

        $employeeId = $request->session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->route('login.form')->with('error', 'Employee record not found in session.');
        }

        try {
            $this->leaveService->requestLeave(
                $employeeId,
                $validated['leave_type_id'],
                $validated['date_from'],
                $validated['date_to'],
                $validated['description'] ?? ''
            );

            return back()->with('success', 'Leave request submitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit leave request: ' . $e->getMessage());
        }
    }
}
