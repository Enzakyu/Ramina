@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
<div class="animate-fade-in">
    <h2 style="margin-bottom: 2rem; font-weight: 300;">Good day, <span style="color: var(--primary); font-weight: 600;">{{ session('user_name', 'Employee') }}</span></h2>

    <div class="stat-grid">
        <div class="card stat-card delay-1 animate-slide-in">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="stat-value" style="font-size: 1.5rem;">
                {{ $attendanceStatus['checked_in'] ?? false ? 'Checked In' : 'Not Checked In' }}
            </div>
            <div class="stat-label">Current Status</div>
        </div>
        
        <div class="card stat-card delay-2 animate-slide-in">
            <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--primary);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="stat-value">{{ number_format($todayHours ?? 0, 1) }}h</div>
            <div class="stat-label">Hours Worked Today</div>
        </div>
        
        <div class="card stat-card delay-3 animate-slide-in">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            </div>
            <div class="stat-value">{{ number_format($overtimeSummary['current_month'] ?? 0, 1) }}h</div>
            <div class="stat-label">Overtime This Month</div>
        </div>
        
        <div class="card stat-card delay-4 animate-slide-in">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--secondary);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="stat-value">{{ $pendingLeaves ?? 0 }}</div>
            <div class="stat-label">Pending Time Off Requests</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Quick Actions -->
        <div class="card animate-fade-in delay-2">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="{{ route('employee.attendance') }}" class="btn btn-primary" style="flex-grow: 1;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Attendance Kiosk
                </a>
                <a href="{{ route('employee.leaves') }}" class="btn btn-outline" style="flex-grow: 1;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Request Time Off
                </a>
            </div>
        </div>

        <!-- Announcements Widget -->
        <div class="card animate-fade-in delay-2">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title">Company Announcements</h3>
                <span class="badge bg-primary">{{ count($announcements ?? []) }} New</span>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @forelse($announcements ?? [] as $announcement)
                    <div style="padding: 1rem; border-left: 4px solid var(--primary); background: rgba(0,0,0,0.02); margin-bottom: 1rem; border-radius: 4px;">
                        <div style="display:flex; justify-content:space-between;">
                            <strong style="font-weight: 600;">{{ $announcement['title'] }}</strong>
                            <small style="color:var(--text-secondary)">{{ \Carbon\Carbon::parse($announcement['date'])->format('d M') }}</small>
                        </div>
                        <p style="margin-top:0.5rem; font-size:0.95rem; color:var(--text-secondary); line-height: 1.5;">
                            {!! $announcement['content'] !!}
                        </p>
                    </div>
                @empty
                    <p class="text-secondary text-center py-4">No announcements at this time.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity Timeline -->
        <div class="card animate-fade-in delay-3">
            <div class="card-header">
                <h3 class="card-title">Recent Attendance</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($recentActivity ?? [] as $act)
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <strong>{{ \Carbon\Carbon::parse($act['check_in'], 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y') }}</strong>
                                <p style="margin-top:0.5rem; font-size:0.9rem; color:var(--text-secondary);">
                                    Check In: {{ \Carbon\Carbon::parse($act['check_in'], 'UTC')->setTimezone('Asia/Jakarta')->format('H:i') }} <br>
                                    Check Out: {{ $act['check_out'] ? \Carbon\Carbon::parse($act['check_out'], 'UTC')->setTimezone('Asia/Jakarta')->format('H:i') : 'Active' }} <br>
                                    Hours: {{ number_format($act['worked_hours'] ?? 0, 1) }}h
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary">No recent attendance records.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
