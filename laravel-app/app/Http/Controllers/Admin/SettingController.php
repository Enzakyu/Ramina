<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\SettingService;
use App\Services\Odoo\EmployeeService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService,
        protected EmployeeService $employeeService
    ) {}

    public function index()
    {
        $settings = $this->settingService->getPayrollSettings();
        $departments = $this->employeeService->getDepartments();
        $jobs = $this->employeeService->getJobs();
        return view('admin.settings', compact('settings', 'departments', 'jobs'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'standard_hours' => 'required|numeric|min:1|max:24',
            'overtime_rate'  => 'required|numeric|min:1|max:10',
        ]);

        try {
            $this->settingService->updatePayrollSettings($validated);
            return back()->with('success', 'Payroll settings updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $this->employeeService->createDepartment($validated);
            return back()->with('success', 'Department created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create department: ' . $e->getMessage());
        }
    }

    public function storeJob(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|integer',
            'basic_salary' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->employeeService->createJob($validated);
            return back()->with('success', 'Job position created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create job position: ' . $e->getMessage());
        }
    }

    public function updateJob(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|integer',
            'basic_salary' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->employeeService->updateJob($id, $validated);
            return back()->with('success', 'Job position updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update job position: ' . $e->getMessage());
        }
    }
}
