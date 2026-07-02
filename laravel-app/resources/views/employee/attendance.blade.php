@extends('layouts.app')

@section('title', 'Attendance Kiosk')

@section('content')
<div class="animate-fade-in">
    
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        
        <!-- Kiosk Panel -->
        <div class="card text-center delay-1 animate-slide-in" style="display: flex; flex-direction: column; align-items: center; padding: 3rem 2rem;">
            <div id="live-clock" style="font-size: 3rem; font-weight: 700; font-variant-numeric: tabular-nums; margin-bottom: 2rem; color: var(--text-primary); text-shadow: 0 0 20px rgba(255,255,255,0.1);">
                --:--:--
            </div>

            @php
                $checkedIn = $isCheckedIn ?? false;
            @endphp

            <div class="check-in-wrapper">
                <button onclick="RaminaHR.toggleAttendance(this)" class="btn-check-in {{ $checkedIn ? 'is-checked-in' : '' }}">
                    @if($checkedIn)
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                        <span>CHECK OUT</span>
                    @else
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                        <span>CHECK IN</span>
                    @endif
                </button>
            </div>
            
            <div style="margin-top: 1rem; color: var(--text-secondary);">
                Status: 
                @if($checkedIn)
                    <span style="color: var(--warning); font-weight: 600;">Currently Checked In</span>
                    <br>Since {{ $currentAttendance ? \Carbon\Carbon::parse($currentAttendance['check_in'])->format('H:i') : '--' }}
                @else
                    <span style="color: var(--text-secondary); font-weight: 600;">Checked Out</span>
                @endif
            </div>
        </div>

        <!-- History -->
        <div class="card delay-2 animate-slide-in">
            <div class="card-header">
                <h3 class="card-title">Attendance History ({{ date('F Y') }})</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history ?? [] as $record)
                        @php
                            $date = \Carbon\Carbon::parse($record['check_in']);
                            $out = $record['check_out'] ? \Carbon\Carbon::parse($record['check_out']) : null;
                            $hours = $record['worked_hours'] ?? 0;
                            $isOvertime = $hours > 8;
                        @endphp
                        <tr style="{{ $isOvertime ? 'background: rgba(245, 158, 11, 0.05);' : '' }}">
                            <td>{{ $date->format('d M Y') }}</td>
                            <td>{{ $date->format('H:i') }}</td>
                            <td>{{ $out ? $out->format('H:i') : '--:--' }}</td>
                            <td style="{{ $isOvertime ? 'color: var(--warning); font-weight:600;' : '' }}">
                                {{ number_format($hours, 1) }}h
                                @if($isOvertime)
                                    <br><span style="font-size:0.75rem; color:var(--warning);">+{{ number_format($hours - 8, 1) }}h OT</span>
                                @endif
                            </td>
                            <td>
                                @if(!$out)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Completed</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
