<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DailyApiController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ApplicantApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('api.dashboard.stats');
    Route::get('/dashboard/trend', [DashboardApiController::class, 'trend'])->name('api.dashboard.trend');

    // Daily Entries
    Route::get('/daily/entries/{date}', [DailyApiController::class, 'getEntries'])->name('api.daily.entries');
    Route::post('/daily/entries', [DailyApiController::class, 'saveEntries'])->name('api.daily.save');
    Route::get('/daily/dates', [DailyApiController::class, 'getDates'])->name('api.daily.dates');

    // Projects
    Route::get('/projects', [ProjectApiController::class, 'index'])->name('api.projects.index');
    Route::post('/projects', [ProjectApiController::class, 'store'])->name('api.projects.store');
    Route::put('/projects/{project}', [ProjectApiController::class, 'update'])->name('api.projects.update');
    Route::delete('/projects/{project}', [ProjectApiController::class, 'destroy'])->name('api.projects.destroy');

    // Categories
    Route::get('/categories', [ProjectApiController::class, 'categories'])->name('api.categories.index');
    Route::post('/categories', [ProjectApiController::class, 'storeCategory'])->name('api.categories.store');

    // Applicants
    Route::get('/applicants', [ApplicantApiController::class, 'index'])->name('api.applicants.index');
    Route::post('/applicants', [ApplicantApiController::class, 'store'])->name('api.applicants.store');
    Route::get('/applicants/{id}', [ApplicantApiController::class, 'show'])->name('api.applicants.show');
    Route::put('/applicants/{id}', [ApplicantApiController::class, 'update'])->name('api.applicants.update');
    Route::delete('/applicants/{id}', [ApplicantApiController::class, 'destroy'])->name('api.applicants.destroy');

    // Applicant Work Experience
    Route::post('/applicants/{id}/experience', [ApplicantApiController::class, 'addExperience'])->name('api.applicants.experience');
    Route::delete('/applicants/experience/{id}', [ApplicantApiController::class, 'deleteExperience'])->name('api.applicants.experience.delete');

    // Applicant Selection
    Route::post('/applicants/{id}/selection', [ApplicantApiController::class, 'saveSelection'])->name('api.applicants.selection');

    // Organizations
    Route::get('/organizations', [ApplicantApiController::class, 'organizations'])->name('api.organizations.index');

    // Positions
    Route::get('/positions', [ApplicantApiController::class, 'positions'])->name('api.positions.index');

    // Reports
    Route::get('/reports/export', [ReportController::class, 'export'])->name('api.reports.export');
    Route::get('/reports/applicants', [ApplicantApiController::class, 'export'])->name('api.reports.applicants');
});