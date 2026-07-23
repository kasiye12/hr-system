<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use App\Models\Applicant;
use App\Models\LeaveRequest;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // DATE & FILTER SETUP
        $dates = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->select(DB::raw('DISTINCT DATE_FORMAT(report_date, "%Y-%m-%d") as report_date'))
            ->orderBy('report_date')
            ->pluck('report_date')
            ->toArray();

        if (empty($dates)) {
            $dates = [now()->format('Y-m-d')];
        }
        $dates = array_values($dates);

        $defaultDate = !empty($dates) ? end($dates) : now()->format('Y-m-d');
        $selectedDate = $request->input('date', $defaultDate);
        $selectedProjectId = (int) $request->input('project', 0);
        $selectedProjectStatus = $request->input('project_status', 'Active');

        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        $projectIds = [];
        if ($selectedProjectStatus !== 'All') {
            $projectIds = Project::where('status', $selectedProjectStatus)->pluck('id')->toArray();
        }

        // ============================================
        // MANPOWER TOTALS - FIXED: Use separate queries
        // ============================================
        // Create a BASE query (without employment_type filter)
        $baseQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('daily_entries.report_date', $selectedDate)
            ->where('categories.is_reconciliation', false);

        if ($selectedProjectId > 0) {
            $baseQuery->where('daily_entries.project_id', $selectedProjectId);
        } elseif (!empty($projectIds)) {
            $baseQuery->whereIn('daily_entries.project_id', $projectIds);
        }

        // Clone the base query for each type to avoid query pollution
        $permanent = (int) (clone $baseQuery)->where('categories.employment_type', 'Permanent')->sum('daily_entries.headcount');
        $contract = (int) (clone $baseQuery)->where('categories.employment_type', 'Contract')->sum('daily_entries.headcount');
        $daily = (int) (clone $baseQuery)->where('categories.employment_type', 'Daily')->sum('daily_entries.headcount');
        $grandTotal = $permanent + $contract + $daily;

        // ============================================
        // TABLE DATA
        // ============================================
        $tableQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->where('daily_entries.report_date', $selectedDate)
            ->where('categories.is_reconciliation', false);

        if ($selectedProjectId > 0) {
            $tableQuery->where('daily_entries.project_id', $selectedProjectId);
        } elseif (!empty($projectIds)) {
            $tableQuery->whereIn('daily_entries.project_id', $projectIds);
        }

        $tableData = $tableQuery->select(
                'projects.name as project_name',
                'projects.status as project_status',
                DB::raw('COALESCE(SUM(CASE WHEN categories.employment_type = "Permanent" THEN daily_entries.headcount ELSE 0 END), 0) as permanent'),
                DB::raw('COALESCE(SUM(CASE WHEN categories.employment_type = "Contract" THEN daily_entries.headcount ELSE 0 END), 0) as contract'),
                DB::raw('COALESCE(SUM(CASE WHEN categories.employment_type = "Daily" THEN daily_entries.headcount ELSE 0 END), 0) as daily')
            )
            ->groupBy('projects.id', 'projects.name', 'projects.status', 'projects.slot')
            ->orderByRaw('COALESCE(projects.slot, 999), projects.name')
            ->get();

        // ============================================
        // 30-DAY SUMMARY
        // ============================================
        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');
        $dashboardData = $this->reportService->getManagementDashboard($dateFrom, $dateTo, 0, 'All');

        // ============================================
        // HR STATS
        // ============================================
        $totalApplicants = Applicant::count();
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();
        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'Active')->count();

        return view('dashboard.index', compact(
            'user',
            'dates', 'selectedDate', 'projects', 'selectedProjectId',
            'selectedProjectStatus',
            'permanent', 'contract', 'daily', 'grandTotal',
            'tableData', 'dashboardData',
            'totalApplicants', 'onLeaveToday', 'pendingLeaves',
            'totalProjects', 'activeProjects'
        ));
    }
}
