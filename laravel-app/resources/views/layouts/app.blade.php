<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ramina HR &mdash; @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ time() }}">
</head>
<body class="app-layout">
    
    @if(session()->has('odoo_uid'))
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo">RAMINA</div>
        </div>
        
        <nav class="sidebar-nav">
            @if(session('is_admin'))
                <!-- Admin Nav -->
                <div class="sidebar-title" style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; padding: 0 1rem; margin-bottom: 0.5rem;">Management</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.employees') }}" class="nav-item {{ request()->routeIs('admin.employees*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Employees
                </a>
                <a href="{{ route('admin.leaves') }}" class="nav-item {{ request()->routeIs('admin.leaves') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Leave Approvals
                </a>
                
                <div class="sidebar-title" style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; padding: 0 1rem; margin-bottom: 0.5rem;">Operations</div>
                <a href="{{ route('admin.announcements') }}" class="nav-item {{ request()->routeIs('admin.announcements') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Announcements
                </a>
                <a href="{{ route('admin.compensations') }}" class="nav-item {{ request()->routeIs('admin.compensations') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg>
                    Reimbursements
                </a>

                <div class="sidebar-title" style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; padding: 0 1rem; margin-bottom: 0.5rem;">Finance</div>
                <a href="{{ route('admin.payroll_data') }}" class="nav-item {{ request()->routeIs('admin.payroll_data') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Adjustments & KPIs
                </a>
                <a href="{{ route('admin.payroll') }}" class="nav-item {{ request()->routeIs('admin.payroll*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    Generate Payroll
                </a>

                <div class="sidebar-title" style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; padding: 0 1rem; margin-bottom: 0.5rem;">System</div>
                <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Settings
                </a>
            @else
                <!-- Employee Nav -->
                <a href="{{ route('employee.dashboard') }}" class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
                <a href="{{ route('employee.attendance') }}" class="nav-item {{ request()->routeIs('employee.attendance') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Attendance
                </a>
                <a href="{{ route('employee.leaves') }}" class="nav-item {{ request()->routeIs('employee.leaves*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Time Off
                </a>
                <a href="{{ route('employee.compensations') }}" class="nav-item {{ request()->routeIs('employee.compensations') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg>
                    Reimbursements
                </a>
                <a href="{{ route('employee.payslips') }}" class="nav-item {{ request()->routeIs('employee.payslips*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    Payslips
                </a>
                <a href="{{ route('employee.overtime') }}" class="nav-item {{ request()->routeIs('employee.overtime') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Overtime
                </a>
            @endif
        </nav>
        
        <div class="sidebar-footer">
            <div class="avatar">{{ substr(session('user_name', 'U'), 0, 1) }}</div>
            <div class="user-info">
                <div class="user-name">{{ session('user_name', 'User') }}</div>
                <div class="user-role">{{ session('is_admin') ? 'HR Admin' : 'Employee' }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;" title="Logout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </form>
        </div>
    </aside>
    @endif

    <main class="main-content" style="{{ !session()->has('odoo_uid') ? 'margin-left:0;' : '' }}">
    @if(session()->has('odoo_uid'))
        <header class="top-bar">
            <div class="page-title">@yield('title')</div>
            <button id="mobile-toggle" class="btn btn-outline" style="display:none;">Menu</button>
        </header>
    @endif
        
        <div class="content-wrapper">
            @if(session('success'))
                <div class="alert alert-success animate-slide-in">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger animate-slide-in">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger animate-slide-in">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @yield('content')
        </div>
    </main>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
