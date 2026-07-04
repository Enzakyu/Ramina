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
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
        <!-- Departments -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Departments</h2>
            </div>
            
            <form action="{{ route('admin.settings.departments.store') }}" method="POST" onsubmit="RaminaHR.showLoading(this)">
                @csrf
                <div style="display: flex; gap: 1rem; align-items: flex-end; margin-bottom: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0; flex: 1;">
                        <label class="form-label">New Department Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>

            <div style="max-height: 300px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments ?? [] as $dept)
                            <tr>
                                <td>{{ $dept['name'] }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center">No departments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Job Positions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Job Positions</h2>
            </div>
            
            <form action="{{ route('admin.settings.jobs.store') }}" method="POST" onsubmit="RaminaHR.showLoading(this)">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">New Job Title</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Department (Optional)</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- None --</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self: flex-start;">Add Job Position</button>
                </div>
            </form>

            <div style="max-height: 300px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs ?? [] as $job)
                            <tr>
                                <td><strong>{{ $job['name'] }}</strong></td>
                                <td>{{ is_array($job['department_id']) ? $job['department_id'][1] : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-secondary text-center">No job positions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
