@extends('layouts.app')

@section('title', 'Reimbursements & Compensations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="font-weight: 300;">My <span style="font-weight: 600; color: var(--primary);">Reimbursements</span></h2>
    <button class="btn btn-primary" onclick="document.getElementById('addCompModal').style.display='flex'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg>
        New Request
    </button>
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
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Date</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Description</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Amount</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-secondary);">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compensations as $comp)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;">{{ \Carbon\Carbon::parse($comp['date'])->format('d M Y') }}</td>
                        <td style="padding: 1rem;">{{ $comp['description'] }}</td>
                        <td style="padding: 1rem; font-weight: 600;">Rp {{ number_format($comp['amount'], 0, ',', '.') }}</td>
                        <td style="padding: 1rem;">
                            @if($comp['state'] === 'submitted')
                                <span class="badge bg-warning text-dark">Pending Review</span>
                            @elseif($comp['state'] === 'approved')
                                <span class="badge bg-success">Approved (Pending Payout)</span>
                            @elseif($comp['state'] === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif($comp['state'] === 'paid')
                                <span class="badge bg-primary">Paid via Payroll</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($comp['state']) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 3rem 1rem; color: var(--text-secondary);">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mb-3"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                            <p>No reimbursement requests found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addCompModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
    <div class="card animate-fade-in" style="width: 100%; max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="margin: 0; font-weight: 600;">Reimbursement Request</h4>
            <button class="btn btn-light" style="padding: 0.5rem;" onclick="document.getElementById('addCompModal').style.display='none'">✕</button>
        </div>
        <form action="{{ route('employee.compensations.store') }}" method="POST" onsubmit="showLoading()">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">Amount (Rp)</label>
                <input type="number" name="amount" class="form-control" required min="1" placeholder="500000">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Description / Purpose</label>
                <textarea name="description" class="form-control" rows="3" required placeholder="e.g., Client meeting lunch with XYZ Corp"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Request</button>
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
