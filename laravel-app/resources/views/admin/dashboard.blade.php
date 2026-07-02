@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="animate-fade-in">
    <h2 style="margin-bottom: 2rem; font-weight: 300;">Welcome back, <span style="color: var(--primary); font-weight: 600;">HR Admin</span></h2>

    <div class="stat-grid">
        <div class="card stat-card delay-1 animate-slide-in">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--secondary);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="stat-value">{{ $employeeCount ?? $totalEmployees ?? 0 }}</div>
            <div class="stat-label">Total Employees</div>
        </div>
        
        <div class="card stat-card delay-2 animate-slide-in">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="stat-value">{{ $checkedInToday ?? 0 }}</div>
            <div class="stat-label">Checked In Today</div>
        </div>
        
        <div class="card stat-card delay-3 animate-slide-in">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="stat-value">{{ $pendingLeaves ?? 0 }}</div>
            <div class="stat-label">Pending Leave Requests</div>
        </div>
        
        <div class="card stat-card delay-4 animate-slide-in">
            <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--primary);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="stat-value" style="font-size: 1.5rem; margin-top: 0.5rem;">Rp {{ number_format($payrollTotal ?? 0, 0, ',', '.') }}</div>
            <div class="stat-label">Payroll This Month</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        
        <!-- Recent Activity Timeline -->
        <div class="card animate-fade-in delay-2">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($recentActivity ?? [] as $act)
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <strong>{{ $act['employee_name'] ?? 'Employee' }}</strong>
                                <span style="float:right; font-size:0.8rem; color:var(--text-secondary);">
                                    {{ \Carbon\Carbon::parse($act['check_in'])->diffForHumans() }}
                                </span>
                                <p style="margin-top:0.5rem; font-size:0.9rem; color:var(--text-secondary);">
                                    Checked in at {{ \Carbon\Carbon::parse($act['check_in'])->format('H:i') }}
                                    @if($act['check_out'])
                                        and checked out at {{ \Carbon\Carbon::parse($act['check_out'])->format('H:i') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary">No recent activity.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card animate-fade-in delay-3">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="{{ route('admin.payroll') }}" class="btn btn-primary" style="justify-content: flex-start;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    Run Payroll
                </a>
                <a href="{{ route('admin.leaves') }}" class="btn btn-outline" style="justify-content: flex-start;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    Review Leaves
                </a>
                <a href="{{ route('admin.employees') }}" class="btn btn-outline" style="justify-content: flex-start;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    Add Employee
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
