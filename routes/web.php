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
            // Experience & Ranking
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
    // ADMIN ROUTES
    // ============================================
    Route::middleware(['admin'])->group(function () {
        
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        });

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

// ============================================
// LEAVE MANAGEMENT ROUTES
// ============================================
Route::prefix('leaves')->name('leaves.')->group(function () {
    Route::get('/', [App\Http\Controllers\LeaveController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\LeaveController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\LeaveController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [App\Http\Controllers\LeaveController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [App\Http\Controllers\LeaveController::class, 'reject'])->name('reject');
    Route::post('/{id}/cancel', [App\Http\Controllers\LeaveController::class, 'cancel'])->name('cancel');
    Route::get('/balances', [App\Http\Controllers\LeaveController::class, 'balances'])->name('balances');
    Route::get('/calendar', [App\Http\Controllers\LeaveController::class, 'calendar'])->name('calendar');
    Route::get('/report', [App\Http\Controllers\LeaveController::class, 'report'])->name('report');
    Route::get('/calculate/{applicantId}', [App\Http\Controllers\LeaveController::class, 'calculateEntitlement'])->name('calculate');
});

// ============================================
// ETHIOPIAN LEAVE MANAGEMENT ROUTES
// Proclamation 1156/2019 Compliant
// ============================================
Route::prefix('leaves')->name('leaves.')->group(function () {
    Route::get('/', [App\Http\Controllers\EthiopianLeaveController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\EthiopianLeaveController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\EthiopianLeaveController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [App\Http\Controllers\EthiopianLeaveController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [App\Http\Controllers\EthiopianLeaveController::class, 'reject'])->name('reject');
    Route::post('/{id}/cancel', [App\Http\Controllers\EthiopianLeaveController::class, 'cancel'])->name('cancel');
    Route::get('/balances', [App\Http\Controllers\EthiopianLeaveController::class, 'balances'])->name('balances');
    Route::get('/calendar', [App\Http\Controllers\EthiopianLeaveController::class, 'calendar'])->name('calendar');
});

// Leave Settings Routes (Admin only)
Route::prefix('leaves/settings')->name('leaves.settings.')->middleware('admin')->group(function () {
    Route::get('/', [App\Http\Controllers\LeaveSettingsController::class, 'index'])->name('index');
    Route::post('/type', [App\Http\Controllers\LeaveSettingsController::class, 'storeLeaveType'])->name('store-type');
    Route::put('/type/{id}', [App\Http\Controllers\LeaveSettingsController::class, 'updateLeaveType'])->name('update-type');
    Route::post('/type/{id}/toggle', [App\Http\Controllers\LeaveSettingsController::class, 'toggleLeaveType'])->name('toggle-type');
    Route::delete('/type/{id}', [App\Http\Controllers\LeaveSettingsController::class, 'deleteLeaveType'])->name('delete-type');
    Route::post('/holiday', [App\Http\Controllers\LeaveSettingsController::class, 'storeHoliday'])->name('store-holiday');
    Route::delete('/holiday/{id}', [App\Http\Controllers\LeaveSettingsController::class, 'deleteHoliday'])->name('delete-holiday');
    Route::post('/config', [App\Http\Controllers\LeaveSettingsController::class, 'updateSettings'])->name('update-config');
});

// Leave Import Routes
Route::prefix('leaves/import')->name('leaves.import.')->group(function () {
    Route::get('/', [App\Http\Controllers\LeaveImportController::class, 'index'])->name('index');
    Route::post('/single', [App\Http\Controllers\LeaveImportController::class, 'storeSingle'])->name('store-single');
    Route::post('/multiple', [App\Http\Controllers\LeaveImportController::class, 'storeMultiple'])->name('store-multiple');
    Route::get('/template', [App\Http\Controllers\LeaveImportController::class, 'downloadTemplate'])->name('template');
    Route::delete('/{id}', [App\Http\Controllers\LeaveImportController::class, 'destroy'])->name('destroy');
});

// Carry Forward Management Routes
Route::prefix('leaves/carryforward')->name('leaves.carryforward.')->group(function () {
    Route::get('/', [App\Http\Controllers\CarryForwardController::class, 'index'])->name('index');
    Route::post('/recalculate', [App\Http\Controllers\CarryForwardController::class, 'recalculate'])->name('recalculate');
    Route::post('/clear/{id}', [App\Http\Controllers\CarryForwardController::class, 'clearExpired'])->name('clear');
    Route::post('/clear-expired', [App\Http\Controllers\CarryForwardController::class, 'clearAllExpired'])->name('clear-expired');
});

// Experience Calculation API
Route::get('/applicants/experience/{id}', [App\Http\Controllers\ApplicantController::class, 'calculateExperience'])->name('applicants.experience');
Route::get('/applicants/ranking/{positionId}', [App\Http\Controllers\ApplicantController::class, 'getRanking'])->name('applicants.ranking');
Route::post('/applicants/ranks/update/{positionId}', [App\Http\Controllers\ApplicantController::class, 'updateRanks'])->name('applicants.ranks.update');

// Leave Documents Route
Route::get('/leaves/documents', function () {
    return view('leaves.documents');
})->name('leaves.documents');

// Leave Breakdown
Route::get('/leaves/breakdown', [App\Http\Controllers\EthiopianLeaveController::class, 'breakdown'])->name('leaves.breakdown');

// Carry Forward Management
Route::prefix('leaves/carryforward')->name('leaves.carryforward.')->group(function () {
    Route::get('/', [App\Http\Controllers\CarryForwardController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\CarryForwardController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [App\Http\Controllers\CarryForwardController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [App\Http\Controllers\CarryForwardController::class, 'reject'])->name('reject');
});

// Clear carry forward (admin only)
Route::post('/leaves/carryforward/clear/{applicantId}', [App\Http\Controllers\CarryForwardController::class, 'clear'])->name('leaves.carryforward.clear');

// Clear specific carry forward
Route::delete('/leaves/carryforward/{id}', [App\Http\Controllers\CarryForwardController::class, 'clear'])->name('leaves.carryforward.delete');
Route::post('/leaves/carryforward/recalculate', [App\Http\Controllers\CarryForwardController::class, 'recalculate'])->name('leaves.carryforward.recalculate');
Route::post('/leaves/carryforward/{id}/reject', [App\Http\Controllers\CarryForwardController::class, 'reject'])->name('leaves.carryforward.reject');
