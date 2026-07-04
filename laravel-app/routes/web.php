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
| Ramina HR System Web routes.
|
*/

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ─── Authenticated Routes ───────────────────────────────────────────────────
Route::middleware(['odoo.auth'])->group(function () {

    // ─── Employee Routes ────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('employee.dashboard');

    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('employee.attendance');
        Route::post('/toggle', [AttendanceController::class, 'toggle'])->name('employee.attendance.toggle');
    });

    Route::prefix('leave')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('employee.leaves');
        Route::post('/', [LeaveController::class, 'store'])->name('employee.leaves.store');
    });

    Route::prefix('payslip')->group(function () {
        Route::get('/', [PayslipController::class, 'index'])->name('employee.payslips');
        Route::get('/{id}', [PayslipController::class, 'show'])->name('employee.payslips.show');
    });

    Route::prefix('overtime')->group(function () {
        Route::get('/', [OvertimeController::class, 'index'])->name('employee.overtime');
    });
    
    // HR Features (Employee)
    Route::get('/compensations', [App\Http\Controllers\Employee\HRFeaturesController::class, 'compensations'])->name('employee.compensations');
    Route::post('/compensations', [App\Http\Controllers\Employee\HRFeaturesController::class, 'storeCompensation'])->name('employee.compensations.store');

    // ─── Admin Routes ───────────────────────────────────────────────────
    Route::middleware(['admin'])->prefix('admin')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::prefix('employees')->group(function () {
            Route::get('/', [AdminEmployeeController::class, 'index'])->name('admin.employees');
            Route::post('/', [AdminEmployeeController::class, 'store'])->name('admin.employees.store');
            Route::get('/{id}', [AdminEmployeeController::class, 'show'])->name('admin.employees.show');
            Route::put('/{id}', [AdminEmployeeController::class, 'update'])->name('admin.employees.update');
        });

        Route::prefix('leave-approval')->group(function () {
            Route::get('/', [LeaveApprovalController::class, 'pending'])->name('admin.leaves');
            Route::post('/{id}/approve', [LeaveApprovalController::class, 'approve'])->name('admin.leaves.approve');
            Route::post('/{id}/reject', [LeaveApprovalController::class, 'reject'])->name('admin.leaves.reject');
        });

        Route::prefix('payroll')->group(function () {
            Route::get('/', [PayrollController::class, 'index'])->name('admin.payroll');
            Route::post('/generate', [PayrollController::class, 'generate'])->name('admin.payroll.generate');
            Route::get('/{id}', [PayrollController::class, 'show'])->name('admin.payroll.show');
            Route::post('/{id}/confirm', [PayrollController::class, 'confirm'])->name('admin.payroll.confirm');
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.settings');
            Route::post('/', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('admin.settings.update');
            Route::post('/departments', [App\Http\Controllers\Admin\SettingController::class, 'storeDepartment'])->name('admin.settings.departments.store');
            Route::post('/jobs', [App\Http\Controllers\Admin\SettingController::class, 'storeJob'])->name('admin.settings.jobs.store');
            Route::put('/jobs/{id}', [App\Http\Controllers\Admin\SettingController::class, 'updateJob'])->name('admin.settings.jobs.update');
        });

        // HR Features (Admin)
        Route::get('/announcements', [App\Http\Controllers\Admin\HRFeaturesController::class, 'announcements'])->name('admin.announcements');
        Route::post('/announcements', [App\Http\Controllers\Admin\HRFeaturesController::class, 'storeAnnouncement'])->name('admin.announcements.store');
        Route::delete('/announcements/{id}', [App\Http\Controllers\Admin\HRFeaturesController::class, 'destroyAnnouncement'])->name('admin.announcements.destroy');
        
        Route::get('/compensations', [App\Http\Controllers\Admin\HRFeaturesController::class, 'compensations'])->name('admin.compensations');
        Route::post('/compensations/{id}', [App\Http\Controllers\Admin\HRFeaturesController::class, 'updateCompensation'])->name('admin.compensations.update');
        
        Route::get('/payroll-data', [App\Http\Controllers\Admin\HRFeaturesController::class, 'payrollData'])->name('admin.payroll_data');
        Route::post('/adjustments', [App\Http\Controllers\Admin\HRFeaturesController::class, 'storeAdjustment'])->name('admin.adjustments.store');
    });
});
