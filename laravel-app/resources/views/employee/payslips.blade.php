@extends('layouts.app')

@section('title', 'My Payslips')

@section('content')
<div class="animate-fade-in">
    
    <div class="card delay-1 animate-slide-in" style="margin-bottom: 2rem;">
        <form action="{{ route('employee.payslips') }}" method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
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
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        @forelse($payslips ?? [] as $payslip)
        <a href="{{ route('employee.payslips.show', $payslip['id']) }}" style="text-decoration: none; color: inherit;">
            <div class="card stat-card delay-2 animate-slide-in" style="cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div>
                        <div style="font-weight: 600; font-size: 1.1rem;">{{ $payslip['name'] }}</div>
                        <div style="color: var(--text-secondary); font-size: 0.9rem;">
                            {{ \Carbon\Carbon::parse($payslip['date_from'])->format('d M') }} - {{ \Carbon\Carbon::parse($payslip['date_to'])->format('d M Y') }}
                        </div>
                    </div>
                    @if($payslip['state'] === 'done')
                        <span class="badge badge-success">Paid</span>
                    @elseif($payslip['state'] === 'verify')
                        <span class="badge badge-warning">Verifying</span>
                    @else
                        <span class="badge badge-secondary">Draft</span>
                    @endif
                </div>
                
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--glass-border);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Net Pay</div>
                    <div style="font-size: 1.8rem; font-weight: 700; color: var(--primary);">
                        Rp {{ number_format($payslip['net_wage'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-secondary);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem;"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            <p>No payslips found for this period.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
