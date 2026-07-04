@extends('layouts.app')

@section('title', 'Payroll Management')

@section('content')
<div class="animate-fade-in">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
        <form action="{{ route('admin.payroll') }}" method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}" {{ ($selectedYear ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ sprintf('%02d', $m) }}" {{ ($selectedMonth ?? date('m')) == sprintf('%02d', $m) ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filter</button>
        </form>

        <button class="btn btn-primary" data-toggle="modal" data-target="#runPayrollModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            Generate Payslips
        </button>
    </div>

    <div class="stat-grid delay-1 animate-slide-in">
        <div class="card stat-card" style="background: rgba(6, 182, 212, 0.05); border-color: rgba(6, 182, 212, 0.2);">
            <div class="stat-value" style="font-size: 1.5rem; color: var(--primary);">Rp {{ number_format($totals['net'] ?? 0, 0, ',', '.') }}</div>
            <div class="stat-label">Total Net Salary</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" style="font-size: 1.5rem;">{{ count($payslips ?? []) }}</div>
            <div class="stat-label">Generated Payslips</div>
        </div>
    </div>

    <div class="card delay-2 animate-slide-in">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Reference</th>
                        <th>Period</th>
                        <th>Net Wage</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips ?? [] as $slip)
                    <tr>
                        <td><strong>{{ is_array($slip['employee_id']) ? $slip['employee_id'][1] : '-' }}</strong></td>
                        <td>{{ $slip['number'] ?? $slip['name'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($slip['date_from'])->format('d M') }} - {{ \Carbon\Carbon::parse($slip['date_to'])->format('d M') }}</td>
                        <td style="font-weight:600; color:var(--primary);">Rp {{ number_format($slip['net_wage'] ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @if($slip['state'] === 'done') <span class="badge badge-success">Paid</span>
                            @elseif($slip['state'] === 'verify') <span class="badge badge-warning">Verifying</span>
                            @else <span class="badge badge-secondary">{{ $slip['state'] }}</span> @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.payroll.show', $slip['id']) }}" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;">No payslips generated for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Run Payroll Modal -->
<div class="modal-backdrop" id="runPayrollModal">
    <div class="modal">
        <div class="card-header">
            <h3 class="card-title">Run Payroll</h3>
            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem;" data-dismiss="modal">&times;</button>
        </div>
        <form action="{{ route('admin.payroll.generate') }}" method="POST">
            @csrf
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Period From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ date('Y-m-01') }}" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Period To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ date('Y-m-t') }}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Employees (Search by Name)</label>
                <input type="text" id="employeeSearchInput" class="form-control" placeholder="Type name to filter..." style="margin-bottom: 0.5rem;" onkeyup="filterEmployees()">
                <div id="employeeList" style="max-height: 200px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; border: 1px solid var(--glass-border);">
                    @foreach($employees ?? [] as $emp)
                    <div class="employee-item" style="margin-bottom: 0.5rem;">
                        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                            <input type="checkbox" name="employee_ids[]" value="{{ $emp['id'] }}" checked>
                            <span class="emp-name">{{ $emp['name'] }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <script>
            function filterEmployees() {
                var input = document.getElementById('employeeSearchInput').value.toLowerCase();
                var items = document.getElementsByClassName('employee-item');
                for (var i = 0; i < items.length; i++) {
                    var name = items[i].querySelector('.emp-name').innerText.toLowerCase();
                    if (name.indexOf(input) > -1) {
                        items[i].style.display = "";
                    } else {
                        items[i].style.display = "none";
                    }
                }
            }
            </script>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Payslips</button>
            </div>
        </form>
    </div>
</div>
@endsection
