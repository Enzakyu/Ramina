@extends('layouts.app')

@section('title', 'Adhoc Payroll & Performance Data')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="font-weight: 600; letter-spacing: -0.5px;">Adhoc Adjustments & KPIs</h2>
</div>

@if(session('success'))
    <div class="alert alert-success animate-fade-in mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="row">
    <!-- Adhoc Adjustments -->
    <div class="col-md-7 mb-4">
        <div class="card animate-slide-in h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 style="margin: 0; font-weight: 600;">Payroll Adjustments (THR / Deductions)</h4>
                <button class="btn btn-sm btn-primary" onclick="document.getElementById('addAdjModal').style.display='flex'">+ Add</button>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="padding: 1rem; text-align: left;">Employee</th>
                            <th style="padding: 1rem; text-align: left;">Type</th>
                            <th style="padding: 1rem; text-align: left;">Amount</th>
                            <th style="padding: 1rem; text-align: left;">Desc</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adj)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem;">{{ $adj['employee_name'] }}</td>
                                <td style="padding: 1rem;">
                                    @if($adj['adjustment_type'] === 'allowance')
                                        <span class="badge bg-success">Bonus/THR</span>
                                    @else
                                        <span class="badge bg-danger">Penalty</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem; font-weight: 600;">Rp {{ number_format($adj['amount'], 0, ',', '.') }}</td>
                                <td style="padding: 1rem; font-size: 0.9rem; max-width:150px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">{{ $adj['description'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 2rem;">No adjustments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Performance KPIs -->
    <div class="col-md-5 mb-4">
        <div class="card animate-slide-in delay-1 h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 style="margin: 0; font-weight: 600;">Performance Reviews</h4>
                <button class="btn btn-sm btn-outline" onclick="document.getElementById('addKpiModal').style.display='flex'">+ Record KPI</button>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="padding: 1rem; text-align: left;">Employee</th>
                            <th style="padding: 1rem; text-align: left;">Month</th>
                            <th style="padding: 1rem; text-align: left;">KPI Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $rev)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem;">{{ $rev['employee_name'] }}</td>
                                <td style="padding: 1rem;">{{ \Carbon\Carbon::parse($rev['date'])->format('M Y') }}</td>
                                <td style="padding: 1rem;">
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <div style="flex-grow:1; height:6px; background:var(--border-color); border-radius:3px; overflow:hidden;">
                                            <div style="height:100%; width:{{ $rev['kpi_score'] }}%; background:var(--primary);"></div>
                                        </div>
                                        <span style="font-weight:600;">{{ $rev['kpi_score'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center" style="padding: 2rem;">No reviews recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->

<!-- Add Adjustment Modal -->
<div id="addAdjModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
    <div class="card animate-fade-in" style="width: 100%; max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="margin: 0; font-weight: 600;">Add Payroll Adjustment</h4>
            <button type="button" class="btn btn-light" style="padding: 0.5rem;" onclick="document.getElementById('addAdjModal').style.display='none'">✕</button>
        </div>
        <form action="{{ route('admin.adjustments.store') }}" method="POST" onsubmit="showLoading()">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">Select Employee...</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp['id'] }}">{{ $emp['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 form-group">
                    <label class="form-label">Type</label>
                    <select name="adjustment_type" class="form-control" required>
                        <option value="allowance">Bonus/THR (Addition)</option>
                        <option value="deduction">Penalty (Deduction)</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Amount (Rp)</label>
                    <input type="number" name="amount" class="form-control" required min="1" placeholder="500000">
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" required placeholder="e.g. Idul Fitri THR">
            </div>
            <button type="submit" class="btn btn-primary w-100">Apply to Next Payroll</button>
        </form>
    </div>
</div>

<!-- Add KPI Modal -->
<div id="addKpiModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
    <div class="card animate-fade-in" style="width: 100%; max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="margin: 0; font-weight: 600;">Record Performance Review</h4>
            <button type="button" class="btn btn-light" style="padding: 0.5rem;" onclick="document.getElementById('addKpiModal').style.display='none'">✕</button>
        </div>
        <form action="{{ route('admin.performance.store') }}" method="POST" onsubmit="showLoading()">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">Select Employee...</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp['id'] }}">{{ $emp['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">KPI Score (0-100)</label>
                <input type="number" name="kpi_score" class="form-control" required min="0" max="100" placeholder="e.g. 85">
                <small class="text-secondary">This score will multiply their performance bonus during payroll.</small>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Manager Feedback</label>
                <textarea name="feedback" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
            </div>
            <button type="submit" class="btn btn-outline w-100">Save Review</button>
        </form>
    </div>
</div>

<script>
function showLoading() {
    if(document.getElementById('global-loader')) {
        document.getElementById('global-loader').style.display = 'flex';
    }
}
</script>
@endsection
