@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 class="page-title">Payroll Settings</h1>
    </div>

    <div class="card" style="max-width: 600px;">
        <div class="card-header">
            <h2 class="card-title">General Payroll Configuration</h2>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Standard Working Hours (per day)</label>
                <input type="number" name="standard_hours" class="form-control" step="0.5" min="1" max="24" value="{{ $settings['standard_hours'] }}" required>
                <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                    Any time worked beyond these standard hours will automatically be counted as overtime (default is 8.0 for 9 AM - 5 PM).
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Overtime Multiplier Rate</label>
                <input type="number" name="overtime_rate" class="form-control" step="0.1" min="1" max="10" value="{{ $settings['overtime_rate'] }}" required>
                <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                    Multiplier applied to the calculated hourly wage for overtime hours (default is 1.5x).
                </small>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
