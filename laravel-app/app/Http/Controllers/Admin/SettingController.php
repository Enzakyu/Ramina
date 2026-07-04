<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Odoo\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(protected SettingService $settingService) {}

    public function index()
    {
        $settings = $this->settingService->getPayrollSettings();
        return view('admin.settings', compact('settings'));
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
}
