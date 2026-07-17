<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get available dates
        $dates = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->select(DB::raw('DISTINCT DATE_FORMAT(report_date, "%Y-%m-%d") as report_date'))
            ->orderBy('report_date')
            ->pluck('report_date')
            ->toArray();

        if (empty($dates)) {
            $dates = [now()->format('Y-m-d')];
        }

        $selectedDate = $request->input('date', end($dates));
        $selectedProjectId = (int) $request->input('project', 0);
        $selectedType = $request->input('type', 'All');
        $selectedProjectStatus = $request->input('project_status', 'Active');

        // Get projects
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        // Get project IDs based on status
        $projectIds = [];
        if ($selectedProjectStatus !== 'All') {
            $projectIds = Project::where('status', $selectedProjectStatus)->pluck('id')->toArray();
        }

        // Calculate totals
        $totalsQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('daily_entries.report_date', $selectedDate)
            ->where('categories.is_reconciliation', false);

        if ($selectedProjectId > 0) {
            $totalsQuery->where('daily_entries.project_id', $selectedProjectId);
        } elseif (!empty($projectIds)) {
            $totalsQuery->whereIn('daily_entries.project_id', $projectIds);
        }

        $permanent = (int) $totalsQuery->where('categories.employment_type', 'Permanent')->sum('daily_entries.headcount');
        $contract = (int) $totalsQuery->where('categories.employment_type', 'Contract')->sum('daily_entries.headcount');
        $daily = (int) $totalsQuery->where('categories.employment_type', 'Daily')->sum('daily_entries.headcount');
        $grandTotal = $permanent + $contract + $daily;

        // Get graph data
        $graphQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->where('daily_entries.report_date', $selectedDate)
            ->where('categories.is_reconciliation', false);

        if ($selectedProjectId > 0) {
            $graphQuery->where('daily_entries.project_id', $selectedProjectId);
        } elseif (!empty($projectIds)) {
            $graphQuery->whereIn('daily_entries.project_id', $projectIds);
        }

        if ($selectedType !== 'All') {
            $graphQuery->where('categories.employment_type', $selectedType);
        }

        $graphProjects = $graphQuery->select(
                'projects.name',
                DB::raw('COALESCE(SUM(daily_entries.headcount), 0) as total')
            )
            ->groupBy('projects.id', 'projects.name', 'projects.slot')
            ->orderByRaw('COALESCE(projects.slot, 999), projects.name')
            ->get();

        // Get table data
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

        return view('dashboard.index', compact(
            'user',
            'dates',
            'selectedDate',
            'projects',
            'selectedProjectId',
            'selectedType',
            'selectedProjectStatus',
            'permanent',
            'contract',
            'daily',
            'grandTotal',
            'graphProjects',
            'tableData'
        ));
    }
}