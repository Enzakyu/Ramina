@extends('layouts.app')

@section('title', 'Overtime & Bonuses')

@section('content')
<div class="animate-fade-in">
    
    <div class="stat-grid delay-1 animate-slide-in">
        <div class="card stat-card">
            <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--primary);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="stat-value">{{ number_format($overtimeSummary['current_month'] ?? 0, 1) }}h</div>
            <div class="stat-label">Total Overtime This Month</div>
        </div>
        
        <div class="card stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="stat-value">{{ number_format($overtimeSummary['unpaid'] ?? 0, 1) }}h</div>
            <div class="stat-label">Unpaid Overtime</div>
        </div>

        <div class="card stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="stat-value" style="font-size: 1.5rem; margin-top: 0.5rem;">Rp {{ number_format(($overtimeSummary['unpaid'] ?? 0) * 50000 * 1.5, 0, ',', '.') }}*</div>
            <div class="stat-label">Estimated Unpaid Bonus</div>
        </div>
    </div>
    
    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 2rem;" class="delay-2 animate-fade-in">* Estimated based on standard IDR 50k/hr base rate x 1.5 multiplier.</p>

    <div class="card delay-3 animate-slide-in">
        <div class="card-header">
            <h3 class="card-title">Overtime Records</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours Worked</th>
                        <th>Overtime Hours</th>
                        <th>Bonus Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overtimeRecords ?? [] as $record)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($record['date'])->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($record['check_in'])->format('H:i') }}</td>
                        <td>{{ $record['check_out'] ? \Carbon\Carbon::parse($record['check_out'])->format('H:i') : '--:--' }}</td>
                        <td>{{ number_format($record['worked_hours'] ?? 0, 1) }}h</td>
                        <td style="color: var(--warning); font-weight: 600;">+{{ number_format($record['overtime_hours'] ?? 0, 1) }}h</td>
                        <td>Rp {{ number_format($record['bonus_amount'] ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @if($record['state'] === 'paid')
                                <span class="badge badge-success">Paid</span>
                            @elseif($record['state'] === 'confirmed')
                                <span class="badge badge-primary">Confirmed</span>
                            @else
                                <span class="badge badge-secondary">Draft</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;">No overtime records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
