<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Odoo\HRFeaturesService;
use App\Services\Odoo\EmployeeService;

class HRFeaturesController extends Controller
{
    protected HRFeaturesService $hrFeatures;
    protected EmployeeService $employeeService;

    public function __construct(HRFeaturesService $hrFeatures, EmployeeService $employeeService)
    {
        $this->hrFeatures = $hrFeatures;
        $this->employeeService = $employeeService;
    }

    // --- Announcements ---
    public function announcements()
    {
        $announcements = $this->hrFeatures->getAnnouncements();
        return view('admin.announcements', compact('announcements'));
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $this->hrFeatures->createAnnouncement([
            'title' => $request->title,
            'content' => $request->content,
            'active' => true,
        ]);

        return redirect()->route('admin.announcements')->with('success', 'Announcement published successfully.');
    }

    public function destroyAnnouncement($id)
    {
        $this->hrFeatures->deleteAnnouncement((int) $id);
        return redirect()->route('admin.announcements')->with('success', 'Announcement deleted.');
    }

    // --- Compensations ---
    public function compensations()
    {
        $compensations = $this->hrFeatures->getCompensations();
        // Attach employee names manually for simplicity, or fetch them if needed
        $employees = $this->employeeService->getAllEmployees();
        $employeeMap = collect($employees)->keyBy('id');
        
        foreach ($compensations as &$comp) {
            $comp['employee_name'] = $employeeMap[$comp['employee_id'][0]]['name'] ?? 'Unknown';
        }
        
        return view('admin.compensations', compact('compensations'));
    }

    public function updateCompensation(Request $request, $id)
    {
        $request->validate(['state' => 'required|in:approved,rejected']);
        $this->hrFeatures->updateCompensationState((int) $id, $request->state);
        return redirect()->route('admin.compensations')->with('success', 'Compensation request updated.');
    }

    // --- Adjustments & Performance ---
    public function payrollData()
    {
        $adjustments = $this->hrFeatures->getAdjustments();
        $reviews = $this->hrFeatures->getPerformanceReviews();
        $employees = collect($this->employeeService->getAllEmployees())->sortBy('name');
        
        $employeeMap = $employees->keyBy('id');
        foreach ($adjustments as &$adj) {
            $adj['employee_name'] = $employeeMap[$adj['employee_id'][0]]['name'] ?? 'Unknown';
        }
        foreach ($reviews as &$rev) {
            $rev['employee_name'] = $employeeMap[$rev['employee_id'][0]]['name'] ?? 'Unknown';
        }

        return view('admin.payroll_data', compact('adjustments', 'reviews', 'employees'));
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'adjustment_type' => 'required|in:allowance,deduction',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255'
        ]);

        $this->hrFeatures->createAdjustment([
            'employee_id' => (int) $request->employee_id,
            'adjustment_type' => $request->adjustment_type,
            'amount' => (float) $request->amount,
            'description' => $request->description,
            'state' => 'approved'
        ]);

        return redirect()->route('admin.payroll_data')->with('success', 'Adhoc Payroll Adjustment added.');
    }

    public function storePerformanceReview(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'kpi_score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string'
        ]);

        $this->hrFeatures->createPerformanceReview([
            'employee_id' => (int) $request->employee_id,
            'kpi_score' => (float) $request->kpi_score,
            'feedback' => $request->feedback,
        ]);

        return redirect()->route('admin.payroll_data')->with('success', 'Performance Review recorded.');
    }
}
