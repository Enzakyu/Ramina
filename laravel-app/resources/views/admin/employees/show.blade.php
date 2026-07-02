@extends('layouts.app')

@section('title', 'Employee Profile')

@section('content')
<div class="animate-fade-in">
    
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('admin.employees') }}" class="btn btn-outline">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Directory
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        
        <!-- Profile Card -->
        <div class="card delay-1 animate-slide-in">
            <div style="display: flex; flex-direction: column; align-items: center; padding: 2rem 0; border-bottom: 1px solid var(--glass-border);">
                <div class="avatar" style="width: 100px; height: 100px; font-size: 3rem; margin-bottom: 1rem;">
                    {{ substr($employee['name'] ?? 'E', 0, 1) }}
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600;">{{ $employee['name'] ?? 'Employee Name' }}</h3>
                <p style="color: var(--primary); font-weight: 500;">{{ is_array($employee['job_title'] ?? null) ? $employee['job_title'][1] : ($employee['job_title'] ?? 'Position') }}</p>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">{{ is_array($employee['department_id'] ?? null) ? $employee['department_id'][1] : 'Department' }}</p>
            </div>
            
            <div style="padding: 2rem 0;">
                <div style="margin-bottom: 1rem;">
                    <div style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Work Email</div>
                    <div>{{ $employee['work_email'] ?? '-' }}</div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Work Phone</div>
                    <div>{{ $employee['work_phone'] ?? '-' }}</div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <div style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Manager</div>
                    <div>{{ is_array($employee['parent_id'] ?? null) ? $employee['parent_id'][1] : '-' }}</div>
                </div>
            </div>
            
            <button class="btn btn-outline" style="width: 100%;" data-toggle="modal" data-target="#editEmployeeModal">
                Edit Profile
            </button>
        </div>

        <!-- Info Tabs -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <div class="card delay-2 animate-slide-in">
                <div class="card-header">
                    <h3 class="card-title">Recent Attendance (This Month)</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendanceSummary ?? [] as $att)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($att['check_in'])->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($att['check_in'])->format('H:i') }}</td>
                                <td>{{ $att['check_out'] ? \Carbon\Carbon::parse($att['check_out'])->format('H:i') : '-' }}</td>
                                <td>{{ number_format($att['worked_hours'] ?? 0, 1) }}h</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;">No recent attendance</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card delay-3 animate-slide-in">
                <div class="card-header">
                    <h3 class="card-title">Recent Payslips</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Period</th>
                                <th>Net Wage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayslips ?? [] as $slip)
                            <tr>
                                <td><a href="{{ route('admin.payroll.show', $slip['id']) }}" style="color:var(--primary);text-decoration:none;">{{ $slip['name'] }}</a></td>
                                <td>{{ \Carbon\Carbon::parse($slip['date_from'])->format('d M') }} - {{ \Carbon\Carbon::parse($slip['date_to'])->format('d M') }}</td>
                                <td>Rp {{ number_format($slip['net_wage'] ?? 0, 0, ',', '.') }}</td>
                                <td>
                                    @if($slip['state'] === 'done') <span class="badge badge-success">Paid</span>
                                    @else <span class="badge badge-secondary">{{ $slip['state'] }}</span> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;">No recent payslips</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editEmployeeModal">
    <div class="modal">
        <div class="card-header">
            <h3 class="card-title">Edit Employee</h3>
            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem;" data-dismiss="modal">&times;</button>
        </div>
        <form action="{{ route('admin.employees.update', $employee['id'] ?? 0) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="{{ $employee['name'] ?? '' }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Work Email</label>
                <input type="email" name="work_email" class="form-control" value="{{ $employee['work_email'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label">Work Phone</label>
                <input type="text" name="work_phone" class="form-control" value="{{ $employee['work_phone'] ?? '' }}">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
