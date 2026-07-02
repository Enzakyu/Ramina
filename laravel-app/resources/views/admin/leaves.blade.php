@extends('layouts.app')

@section('title', 'Leave Approvals')

@section('content')
<div class="animate-fade-in">
    
    <div style="margin-bottom: 2rem; display: flex; gap: 1rem;">
        <a href="?filter=confirm" class="btn {{ ($filter ?? 'confirm') === 'confirm' ? 'btn-primary' : 'btn-outline' }}">Pending Review</a>
        <a href="?filter=validate" class="btn {{ ($filter ?? 'confirm') === 'validate' ? 'btn-primary' : 'btn-outline' }}">Approved</a>
        <a href="?filter=refuse" class="btn {{ ($filter ?? 'confirm') === 'refuse' ? 'btn-primary' : 'btn-outline' }}">Refused</a>
        <a href="?filter=all" class="btn {{ ($filter ?? 'confirm') === 'all' ? 'btn-primary' : 'btn-outline' }}">All Requests</a>
    </div>

    <div class="card delay-1 animate-slide-in">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests ?? [] as $req)
                    <tr>
                        <td>
                            <strong>{{ is_array($req['employee_id']) ? $req['employee_id'][1] : '-' }}</strong>
                            <div style="font-size:0.8rem; color:var(--text-secondary);">{{ $req['name'] ?? 'No reason provided' }}</div>
                        </td>
                        <td>{{ is_array($req['holiday_status_id']) ? $req['holiday_status_id'][1] : '-' }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($req['date_from'])->format('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($req['date_to'])->format('d M Y') }}
                        </td>
                        <td>{{ number_format($req['number_of_days'] ?? 0, 1) }}</td>
                        <td>
                            @if($req['state'] === 'validate')
                                <span class="badge badge-success">Approved</span>
                            @elseif($req['state'] === 'refuse')
                                <span class="badge badge-danger">Refused</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($req['state'] === 'confirm')
                                <div style="display:flex; gap:0.5rem;">
                                    <button onclick="RaminaHR.approveLeave({{ $req['id'] }})" class="btn btn-success" style="padding: 0.25rem 0.5rem;" title="Approve">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </button>
                                    <button onclick="RaminaHR.rejectLeave({{ $req['id'] }})" class="btn btn-danger" style="padding: 0.25rem 0.5rem;" title="Reject">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            @else
                                <span style="color:var(--text-secondary); font-size:0.8rem;">Reviewed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;">No leave requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
