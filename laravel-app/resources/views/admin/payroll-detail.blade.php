@extends('layouts.app')

@section('title', 'Payslip Validation')

@section('content')
<div class="animate-fade-in">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <a href="{{ route('admin.payroll') }}" class="btn btn-outline">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Payroll
        </a>
        <div style="display: flex; gap: 1rem;">
            <button onclick="window.print()" class="btn btn-outline">Print</button>
            
            @if(($payslip['state'] ?? '') === 'draft' || ($payslip['state'] ?? '') === 'verify')
            <form action="{{ route('admin.payroll.show', $payslip['id']) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-primary">Confirm & Mark as Done</button>
            </form>
            @endif
        </div>
    </div>

    <!-- Re-use the layout from employee payslip-detail -->
    <div class="card delay-1 animate-slide-in print-area">
        <div style="text-align: center; margin-bottom: 3rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">RAMINA HR</h1>
            <h2 style="font-weight: 300;">Salary Slip</h2>
            <p style="color: var(--text-secondary);">Period: {{ \Carbon\Carbon::parse($payslip['date_from'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($payslip['date_to'])->format('d M Y') }}</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
            <div>
                <table class="table" style="background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <tr>
                        <th style="width: 120px;">Employee</th>
                        <td>{{ is_array($payslip['employee_id']) ? $payslip['employee_id'][1] : '' }}</td>
                    </tr>
                    <tr>
                        <th>Reference</th>
                        <td>{{ $payslip['number'] ?? $payslip['name'] }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($payslip['state'] === 'done')
                                <span class="badge badge-success">Paid</span>
                            @else
                                <span class="badge badge-warning">{{ ucfirst($payslip['state']) }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: flex-end; background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.2); border-radius: 8px; padding: 2rem;">
                <div style="color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Net Salary</div>
                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary);">
                    Rp {{ number_format($payslip['net_wage'] ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Salary Breakdown</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th style="text-align: right;">Amount (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payslipLines ?? [] as $line)
                    @php
                        $isNet = $line['code'] === 'NET';
                        $isBasic = $line['code'] === 'BASIC';
                    @endphp
                    <tr style="{{ $isNet ? 'font-weight: 700; background: rgba(6, 182, 212, 0.1);' : ($isBasic ? 'font-weight: 600;' : '') }}">
                        <td>{{ $line['code'] }}</td>
                        <td>{{ $line['name'] }}</td>
                        <td style="text-align: right;">{{ number_format($line['total'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
