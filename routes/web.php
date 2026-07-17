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
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImportController;

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
    // ============================================
// DAILY INPUT
// ============================================
Route::prefix('daily')->name('daily.')->group(function () {
    Route::get('/', [DailyController::class, 'index'])->name('index');
    Route::post('/save', [DailyController::class, 'save'])->name('save');
    Route::get('/export', [DailyController::class, 'export'])->name('export');
    Route::get('/export-excel', [DailyController::class, 'exportExcel'])->name('export-excel');
    
    // API endpoints (optional)
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
    
    // Categories (Job Titles)
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