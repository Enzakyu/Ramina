<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; Ramina HR</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ time() }}">
</head>
<body class="auth-page">

    <div class="auth-card card animate-fade-in delay-1">
        <div class="brand-logo" style="font-size: 2.5rem; margin-bottom: 0.5rem; justify-content: center; display: flex;">RAMINA</div>
        <p style="color: var(--text-secondary); margin-bottom: 2rem; font-weight: 500;">HR & Payroll Portal</p>
        
        @if(session('error'))
            <div class="alert alert-danger" style="text-align: left;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="text-align: left;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group animate-slide-in delay-2" style="text-align: left;">
                <label class="form-label">Email / Login ID</label>
                <div style="position: relative;">
                    <svg style="position: absolute; left: 12px; top: 12px; color: var(--text-secondary);" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <input type="text" name="email" class="form-control" style="padding-left: 40px;" placeholder="admin@ramina.com" required autofocus>
                </div>
            </div>
            
            <div class="form-group animate-slide-in delay-3" style="text-align: left;">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <svg style="position: absolute; left: 12px; top: 12px; color: var(--text-secondary);" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" name="password" class="form-control" style="padding-left: 40px;" placeholder="••••••••" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary animate-fade-in delay-4" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">
                Sign In securely
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
        </form>
        
        <div style="margin-top: 2rem; color: var(--text-secondary); font-size: 0.8rem;" class="animate-fade-in delay-4">
            &copy; 2026 Ramina. All rights reserved. <br>
            Powered by Odoo Backend
        </div>
    </div>

</body>
</html>
