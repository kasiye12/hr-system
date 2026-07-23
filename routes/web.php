<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\EthiopianLeaveController;
use App\Http\Controllers\LeaveSettingsController;
use App\Http\Controllers\LeaveImportController;
use App\Http\Controllers\CarryForwardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================
// AUTHENTICATION ROUTES
// ============================================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ============================================
// PROTECTED ROUTES (Requires Authentication)
// ============================================
Route::middleware(['auth'])->group(function () {

    // ============================================
    // DASHBOARD
    // ============================================
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ============================================
    // DAILY INPUT
    // ============================================
    Route::prefix('daily')->name('daily.')->group(function () {
        Route::get('/', [DailyController::class, 'index'])->name('index');
        Route::post('/save', [DailyController::class, 'save'])->name('save');
        Route::get('/export', [DailyController::class, 'export'])->name('export');
        Route::get('/export-excel', [DailyController::class, 'exportExcel'])->name('export-excel');
        Route::get('/api/dates', [DailyController::class, 'getDates'])->name('api.dates');
        Route::get('/api/totals', [DailyController::class, 'getProjectTotals'])->name('api.totals');
    });

    // ============================================
    // PROJECT SETUP
    // ============================================
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('/categories', [ProjectController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [ProjectController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [ProjectController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/categories/{category}/toggle', [ProjectController::class, 'toggleCategory'])->name('categories.toggle');
    });

    // ============================================
    // COMPARISON
    // ============================================
    Route::get('/comparison', [ComparisonController::class, 'index'])->name('comparison.index');

    // ============================================
    // REPORTS
    // ============================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/export', [ReportController::class, 'export'])->name('export');
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // ============================================
    // APPLICANTS
    // ============================================
    Route::prefix('applicants')->name('applicants.')->group(function () {
        Route::get('/', [ApplicantController::class, 'index'])->name('index');
        Route::post('/', [ApplicantController::class, 'store'])->name('store');
        Route::put('/{applicant}', [ApplicantController::class, 'update'])->name('update');
        Route::delete('/{applicant}', [ApplicantController::class, 'destroy'])->name('destroy');
        Route::post('/work-experience', [ApplicantController::class, 'storeWorkExperience'])->name('work-experience.store');
        Route::delete('/work-experience/{id}', [ApplicantController::class, 'deleteWorkExperience'])->name('work-experience.destroy');
        Route::get('/work-experience/{id}/edit', [ApplicantController::class, 'editWorkExperience'])->name('work-experience.edit');
        Route::put('/work-experience/{id}', [ApplicantController::class, 'updateWorkExperience'])->name('work-experience.update');
        Route::post('/review', [ApplicantController::class, 'saveCommitteeReview'])->name('review.save');
        Route::post('/organizations', [ApplicantController::class, 'storeOrganization'])->name('organizations.store');
        Route::delete('/organizations/{id}', [ApplicantController::class, 'deleteOrganization'])->name('organizations.destroy');
        Route::post('/positions', [ApplicantController::class, 'storePosition'])->name('positions.store');
        Route::put('/positions', [ApplicantController::class, 'updatePosition'])->name('positions.update');
        Route::delete('/positions/{id}', [ApplicantController::class, 'deletePosition'])->name('positions.destroy');
        Route::post('/criteria', [ApplicantController::class, 'storeCriterion'])->name('criteria.store');
        Route::delete('/criteria/{id}', [ApplicantController::class, 'deleteCriterion'])->name('criteria.destroy');
        Route::get('/export', [ApplicantController::class, 'exportApplicants'])->name('export');
        Route::get('/export-selection', [ApplicantController::class, 'exportSelection'])->name('export-selection');
        Route::get('/export-profile', [ApplicantController::class, 'exportSelectionProfile'])->name('export-profile');
        Route::get('/profile/{id}', [ApplicantController::class, 'exportSingleProfile'])->name('profile');
        Route::get('/experience/{id}', [ApplicantController::class, 'calculateExperience'])->name('experience');
        Route::get('/ranking/{positionId}', [ApplicantController::class, 'getRanking'])->name('ranking');
        Route::post('/ranks/update/{positionId}', [ApplicantController::class, 'updateRanks'])->name('ranks.update');
    });

    // ============================================
    // DOCUMENTS
    // ============================================
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/upload', [DocumentController::class, 'upload'])->name('upload');
        Route::get('/download/{id}', [DocumentController::class, 'download'])->name('download');
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // PASSWORD CHANGE
    // ============================================
    Route::get('/change-password', [UserController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [UserController::class, 'changePassword'])->name('password.update');

    // ============================================
    // IMPORT ROUTES
    // ============================================
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/upload', [ImportController::class, 'import'])->name('upload');
        Route::get('/template', [ImportController::class, 'downloadTemplate'])->name('template');
    });

    // ============================================
    // LEAVE MANAGEMENT - ETHIOPIAN PROCLAMATION 1156/2019
    // ============================================
    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::get('/', [EthiopianLeaveController::class, 'index'])->name('index');
        Route::get('/create', [EthiopianLeaveController::class, 'create'])->name('create');
        Route::post('/', [EthiopianLeaveController::class, 'store'])->name('store');
        Route::post('/{id}/approve', [EthiopianLeaveController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [EthiopianLeaveController::class, 'reject'])->name('reject');
        Route::post('/{id}/cancel', [EthiopianLeaveController::class, 'cancel'])->name('cancel');
        Route::get('/balances', [EthiopianLeaveController::class, 'balances'])->name('balances');
        Route::get('/calendar', [EthiopianLeaveController::class, 'calendar'])->name('calendar');
        Route::get('/report', [EthiopianLeaveController::class, 'report'])->name('report');
        Route::get('/calculate/{applicantId}', [EthiopianLeaveController::class, 'calculateEntitlement'])->name('calculate');
        Route::get('/breakdown', [EthiopianLeaveController::class, 'breakdown'])->name('breakdown');
        Route::get('/documents', function () {
            return view('leaves.documents');
        })->name('documents');
    });

    // ============================================
    // LEAVE SETTINGS (Admin only)
    // ============================================
    Route::prefix('leaves/settings')->name('leaves.settings.')->middleware('admin')->group(function () {
        Route::get('/', [LeaveSettingsController::class, 'index'])->name('index');
        Route::post('/type', [LeaveSettingsController::class, 'storeLeaveType'])->name('store-type');
        Route::put('/type/{id}', [LeaveSettingsController::class, 'updateLeaveType'])->name('update-type');
        Route::post('/type/{id}/toggle', [LeaveSettingsController::class, 'toggleLeaveType'])->name('toggle-type');
        Route::delete('/type/{id}', [LeaveSettingsController::class, 'deleteLeaveType'])->name('delete-type');
        Route::post('/holiday', [LeaveSettingsController::class, 'storeHoliday'])->name('store-holiday');
        Route::delete('/holiday/{id}', [LeaveSettingsController::class, 'deleteHoliday'])->name('delete-holiday');
        Route::post('/config', [LeaveSettingsController::class, 'updateSettings'])->name('update-config');
    });

    // ============================================
    // LEAVE IMPORT (Admin only)
    // ============================================
    Route::prefix('leaves/import')->name('leaves.import.')->middleware('admin')->group(function () {
        Route::get('/', [LeaveImportController::class, 'index'])->name('index');
        Route::post('/single', [LeaveImportController::class, 'storeSingle'])->name('store-single');
        Route::post('/multiple', [LeaveImportController::class, 'storeMultiple'])->name('store-multiple');
        Route::get('/template', [LeaveImportController::class, 'downloadTemplate'])->name('template');
        Route::delete('/{id}', [LeaveImportController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // CARRY FORWARD MANAGEMENT
    // ============================================
    Route::prefix('leaves/carryforward')->name('leaves.carryforward.')->group(function () {
        Route::get('/', [CarryForwardController::class, 'index'])->name('index');
        Route::post('/', [CarryForwardController::class, 'store'])->name('store');
        Route::post('/{id}/approve', [CarryForwardController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [CarryForwardController::class, 'reject'])->name('reject');
        Route::delete('/{id}', [CarryForwardController::class, 'destroy'])->name('destroy');
        Route::post('/recalculate', [CarryForwardController::class, 'recalculate'])->name('recalculate');
        Route::post('/clear/{id}', [CarryForwardController::class, 'clearExpired'])->name('clear');
        Route::post('/clear-expired', [CarryForwardController::class, 'clearAllExpired'])->name('clear-expired');
        Route::post('/clear/{applicantId}', [CarryForwardController::class, 'clear'])->name('clear');
    });

    // ============================================
    // ADMIN ROUTES
    // ============================================
    Route::middleware(['admin'])->group(function () {
        
        // Users Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        });

        // Backup
        Route::prefix('backup')->name('backup.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::get('/download', [BackupController::class, 'download'])->name('download');
            Route::get('/local', [BackupController::class, 'local'])->name('local');
            Route::get('/full', [BackupController::class, 'full'])->name('full');
            Route::delete('/{file}', [BackupController::class, 'delete'])->name('delete');
        });
    });
});

// ============================================
// FALLBACK ROUTE
// ============================================
Route::fallback(function () {
    return redirect()->route('dashboard');
});
// Standard HR Report
Route::get('/reports/standard', [App\Http\Controllers\ReportController::class, 'standard'])->name('reports.standard');
Route::get('/reports/standard/export', [App\Http\Controllers\ReportController::class, 'exportStandard'])->name('reports.standard.export');
