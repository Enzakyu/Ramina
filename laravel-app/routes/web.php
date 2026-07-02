<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\Employee\OvertimeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\LeaveApprovalController;
use App\Http\Controllers\Admin\PayrollController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Ramina HR System API routes.
| All routes return JSON responses. Authentication is handled via
| Odoo JSON-RPC with file-based sessions.
|
*/

// ─── Auth (no middleware) ────────────────────────────────────────────────────
Route::post('/api/login', [LoginController::class, 'login']);
Route::post('/api/logout', [LoginController::class, 'logout']);

// ─── Authenticated Routes ───────────────────────────────────────────────────
Route::middleware(['odoo.auth'])->prefix('api')->group(function () {

    // Current user
    Route::get('/me', [LoginController::class, 'me']);

    // ─── Employee Routes ────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('attendance')->group(function () {
        Route::get('/status', [AttendanceController::class, 'status']);
        Route::post('/toggle', [AttendanceController::class, 'toggle']);
        Route::get('/history', [AttendanceController::class, 'history']);
    });

    Route::prefix('leave')->group(function () {
        Route::get('/types', [LeaveController::class, 'types']);
        Route::get('/', [LeaveController::class, 'index']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::get('/{id}', [LeaveController::class, 'show']);
    });

    Route::prefix('payslip')->group(function () {
        Route::get('/', [PayslipController::class, 'index']);
        Route::get('/{id}', [PayslipController::class, 'show']);
    });

    Route::prefix('overtime')->group(function () {
        Route::get('/', [OvertimeController::class, 'index']);
        Route::get('/summary', [OvertimeController::class, 'summary']);
    });

    // ─── Admin Routes ───────────────────────────────────────────────────
    Route::middleware(['admin'])->prefix('admin')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::prefix('employees')->group(function () {
            Route::get('/', [AdminEmployeeController::class, 'index']);
            Route::post('/', [AdminEmployeeController::class, 'store']);
            Route::get('/{id}', [AdminEmployeeController::class, 'show']);
            Route::put('/{id}', [AdminEmployeeController::class, 'update']);
        });

        Route::prefix('leave-approval')->group(function () {
            Route::get('/pending', [LeaveApprovalController::class, 'pending']);
            Route::post('/{id}/approve', [LeaveApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [LeaveApprovalController::class, 'reject']);
        });

        Route::prefix('payroll')->group(function () {
            Route::get('/', [PayrollController::class, 'index']);
            Route::post('/generate', [PayrollController::class, 'generate']);
            Route::post('/{id}/confirm', [PayrollController::class, 'confirm']);
            Route::get('/{id}', [PayrollController::class, 'show']);
        });
    });
});
