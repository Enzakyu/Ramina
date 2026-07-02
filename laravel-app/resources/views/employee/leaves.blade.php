@extends('layouts.app')

@section('title', 'Time Off')

@section('content')
<div class="animate-fade-in">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="font-weight: 300;">My Leaves</h2>
        <button class="btn btn-primary" data-toggle="modal" data-target="#requestLeaveModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Request Time Off
        </button>
    </div>

    <div class="card delay-1 animate-slide-in">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Duration</th>
                        <th>Days</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests ?? [] as $req)
                    <tr>
                        <td><strong>{{ is_array($req['holiday_status_id']) ? $req['holiday_status_id'][1] : $req['holiday_status_id'] }}</strong></td>
                        <td>{{ $req['name'] ?? '-' }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($req['date_from'])->format('d M') }} 
                            &rarr; 
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
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;">No leave requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="requestLeaveModal">
    <div class="modal">
        <div class="card-header">
            <h3 class="card-title">Request Time Off</h3>
            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem;" data-dismiss="modal">&times;</button>
        </div>
        <form action="{{ route('employee.leaves.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Leave Type</label>
                <select name="leave_type_id" class="form-select" required>
                    <option value="">-- Select --</option>
                    @foreach($leaveTypes ?? [] as $type)
                        <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description / Reason</label>
                <textarea name="name" class="form-control" required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endsection
