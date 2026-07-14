<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('date');
        $selectedProjectId = $request->input('project', 0);
        $selectedType = $request->input('type', 'All');
        $selectedProjectStatus = $request->input('project_status', 'Active');

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

        if (!$selectedDate || !in_array($selectedDate, $dates)) {
            $selectedDate = end($dates);
        }

        // Get projects
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        // Build query conditions
        $whereConditions = [
            ['daily_entries.report_date', '=', $selectedDate],
            ['categories.is_reconciliation', '=', false],
        ];

        if ($selectedProjectId > 0) {
            $whereConditions[] = ['daily_entries.project_id', '=', $selectedProjectId];
        }

        if ($selectedProjectStatus !== 'All') {
            $projectsWithStatus = Project::where('status', $selectedProjectStatus)->pluck('id');
            $whereConditions[] = ['daily_entries.project_id', 'IN', $projectsWithStatus];
        }

        // Get type totals
        $typeTotals = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->where($whereConditions)
            ->select('categories.employment_type', DB::raw('COALESCE(SUM(daily_entries.headcount), 0) as total'))
            ->groupBy('categories.employment_type')
            ->get()
            ->keyBy('employment_type');

        $permanent = round($typeTotals['Permanent']->total ?? 0);
        $contract = round($typeTotals['Contract']->total ?? 0);
        $daily = round($typeTotals['Daily']->total ?? 0);
        $grandTotal = $permanent + $contract + $daily;

        // Get trend data
        $trendQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->where('categories.is_reconciliation', false);

        if ($selectedProjectId > 0) {
            $trendQuery->where('daily_entries.project_id', $selectedProjectId);
        }

        if ($selectedProjectStatus !== 'All') {
            $projectsWithStatus = Project::where('status', $selectedProjectStatus)->pluck('id');
            $trendQuery->whereIn('daily_entries.project_id', $projectsWithStatus);
        }

        if ($selectedType !== 'All') {
            $trendQuery->where('categories.employment_type', $selectedType);
        }

        $trendData = $trendQuery->select(
                DB::raw('DATE_FORMAT(daily_entries.report_date, "%Y-%m-%d") as report_date'),
                DB::raw('COALESCE(SUM(daily_entries.headcount), 0) as total')
            )
            ->groupBy('daily_entries.report_date')
            ->orderBy('daily_entries.report_date')
            ->get();

        // Get project graph data
        $graphQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->where([
                ['daily_entries.report_date', '=', $selectedDate],
                ['categories.is_reconciliation', '=', false],
            ]);

        if ($selectedProjectId > 0) {
            $graphQuery->where('daily_entries.project_id', $selectedProjectId);
        }

        if ($selectedProjectStatus !== 'All') {
            $projectsWithStatus = Project::where('status', $selectedProjectStatus)->pluck('id');
            $graphQuery->whereIn('daily_entries.project_id', $projectsWithStatus);
        }

        if ($selectedType !== 'All') {
            $graphQuery->where('categories.employment_type', $selectedType);
        }

        $graphProjects = $graphQuery->select(
                'projects.name',
                DB::raw('COALESCE(SUM(daily_entries.headcount), 0) as total')
            )
            ->groupBy('projects.id', 'projects.name', 'projects.slot')
            ->orderByRaw('total DESC, COALESCE(projects.slot, 999), projects.name')
            ->get();

        // Get table data
        $tableQuery = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->where([
                ['daily_entries.report_date', '=', $selectedDate],
                ['categories.is_reconciliation', '=', false],
            ]);

        if ($selectedProjectId > 0) {
            $tableQuery->where('daily_entries.project_id', $selectedProjectId);
        }

        if ($selectedProjectStatus !== 'All') {
            $projectsWithStatus = Project::where('status', $selectedProjectStatus)->pluck('id');
            $tableQuery->whereIn('daily_entries.project_id', $projectsWithStatus);
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
            'trendData',
            'graphProjects',
            'tableData'
        ));
    }
}