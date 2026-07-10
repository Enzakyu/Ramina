@extends('layouts.app')

@section('title', 'Employee Directory')

@section('content')
<div class="animate-fade-in">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div style="position: relative; width: 300px;">
            <svg style="position: absolute; left: 12px; top: 10px; color: var(--text-secondary);" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="employeeSearch" class="form-control" placeholder="Search employees..." style="padding-left: 36px;" onkeyup="filterEmployees()">
        </div>
        
        <button class="btn btn-primary" data-toggle="modal" data-target="#addEmployeeModal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            Add Employee
        </button>
    </div>

    <div class="card delay-1 animate-slide-in">
        <div class="table-responsive">
            <table class="table" id="employeesTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Job Position</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees ?? [] as $emp)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <div class="avatar" style="width:32px; height:32px; font-size:1rem;">{{ substr($emp['name'], 0, 1) }}</div>
                                <strong>{{ $emp['name'] }}</strong>
                            </div>
                        </td>
                        <td>{{ is_array($emp['department_id']) ? $emp['department_id'][1] : '-' }}</td>
                        <td>{{ is_array($emp['job_id']) ? $emp['job_id'][1] : '-' }}</td>
                        <td>{{ $emp['work_email'] ?? '-' }}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <a href="{{ route('admin.employees.show', $emp['id']) }}" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">View Profile</a>
                                <form action="{{ route('admin.employees.destroy', $emp['id']) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this employee?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border: none;">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="addEmployeeModal">
    <div class="modal">
        <div class="card-header">
            <h3 class="card-title">Add New Employee</h3>
            <button class="btn btn-outline" style="padding: 0.2rem 0.5rem;" data-dismiss="modal">&times;</button>
        </div>
        <form action="{{ route('admin.employees.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Job Position</label>
                    <select name="job_id" id="jobSelect" class="form-select" onchange="updateBasicSalary()">
                        <option value="">Select Job Position...</option>
                        @foreach($jobs ?? [] as $job)
                            <option value="{{ $job['id'] }}" data-salary="{{ $job['x_basic_salary'] ?? '' }}">{{ $job['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">Select Department...</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Work Email (Used for Login)</label>
                    <input type="email" name="work_email" class="form-control" placeholder="employee@ramina.com">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Login Password</label>
                    <input type="text" name="password" class="form-control" placeholder="Leave empty for 'password123'">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Basic Salary (IDR)</label>
                <input type="number" name="basic_salary" id="basicSalaryInput" class="form-control" placeholder="e.g. 5000000" min="0">
                <small class="text-secondary" style="margin-top:0.5rem; display:block;">Leaving this blank will use the job position's base salary, if available.</small>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Employee</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function filterEmployees() {
    let input = document.getElementById('employeeSearch').value.toLowerCase();
    let rows = document.getElementById('employeesTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let name = rows[i].getElementsByTagName('td')[0]?.innerText.toLowerCase() || '';
        let dept = rows[i].getElementsByTagName('td')[1]?.innerText.toLowerCase() || '';
        
        if (name.includes(input) || dept.includes(input)) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

function updateBasicSalary() {
    const jobSelect = document.getElementById('jobSelect');
    const salaryInput = document.getElementById('basicSalaryInput');
    
    if (jobSelect && jobSelect.selectedIndex > 0) {
        const option = jobSelect.options[jobSelect.selectedIndex];
        const salary = option.getAttribute('data-salary');
        if (salary && salary !== '0') {
            salaryInput.value = salary;
        }
    }
}
</script>
@endpush
@endsection
