@extends('layouts.app')

@section('title', 'Manage Compensations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="font-weight: 600; letter-spacing: -0.5px;">Reimbursement Requests</h2>
</div>

@if(session('success'))
    <div class="alert alert-success animate-fade-in mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="card animate-slide-in">
    <div class="card-body p-0">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Employee</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Date</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Description</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Amount</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Status</th>
                    <th style="padding: 1rem; text-align: right; font-weight: 600; color: var(--text-secondary);">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compensations as $comp)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600;">{{ $comp['employee_name'] }}</div>
                        </td>
                        <td style="padding: 1rem;">{{ \Carbon\Carbon::parse($comp['date'])->format('d M Y') }}</td>
                        <td style="padding: 1rem; max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            {{ $comp['description'] }}
                        </td>
                        <td style="padding: 1rem; font-weight: 600;">Rp {{ number_format($comp['amount'], 0, ',', '.') }}</td>
                        <td style="padding: 1rem;">
                            @if($comp['state'] === 'submitted')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($comp['state'] === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($comp['state'] === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif($comp['state'] === 'paid')
                                <span class="badge bg-primary">Paid</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($comp['state']) }}</span>
                            @endif
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            @if($comp['state'] === 'submitted')
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <form action="{{ route('admin.compensations.update', $comp['id']) }}" method="POST" onsubmit="showLoading()">
                                        @csrf
                                        <input type="hidden" name="state" value="approved">
                                        <button type="submit" class="btn btn-sm" style="background: rgba(16, 185, 129, 0.1); color: var(--success); border: none;">Approve</button>
                                    </form>
                                    <form action="{{ route('admin.compensations.update', $comp['id']) }}" method="POST" onsubmit="showLoading()">
                                        @csrf
                                        <input type="hidden" name="state" value="rejected">
                                        <button type="submit" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border: none;">Reject</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 3rem 1rem; color: var(--text-secondary);">
                            No reimbursement requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
