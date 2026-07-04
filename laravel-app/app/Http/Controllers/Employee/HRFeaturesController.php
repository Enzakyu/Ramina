<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Odoo\HRFeaturesService;

class HRFeaturesController extends Controller
{
    protected HRFeaturesService $hrFeatures;

    public function __construct(HRFeaturesService $hrFeatures)
    {
        $this->hrFeatures = $hrFeatures;
    }

    public function compensations(Request $request)
    {
        $employeeId = session('employee_id');
        if (!$employeeId) {
            return redirect()->route('login');
        }

        $compensations = $this->hrFeatures->getCompensations($employeeId);
        return view('employee.compensations', compact('compensations'));
    }

    public function storeCompensation(Request $request)
    {
        $employeeId = session('employee_id');
        if (!$employeeId) {
            return redirect()->route('login');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255'
        ]);

        $this->hrFeatures->requestCompensation(
            $employeeId,
            (float) $request->amount,
            $request->description
        );

        return redirect()->route('employee.compensations')->with('success', 'Compensation request submitted. Awaiting HR approval.');
    }
}
