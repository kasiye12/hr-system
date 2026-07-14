<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get Management Dashboard Data
     */
    public function getManagementDashboard($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        // Build query
        $query = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->whereBetween('daily_entries.report_date', [$dateFrom, $dateTo]);

        if ($projectId > 0) {
            $query->where('daily_entries.project_id', $projectId);
        }

        if ($employmentType !== 'All') {
            $query->where('categories.employment_type', $employmentType);
        }

        // Get all entries grouped by date
        $entries = $query->select(
                DB::raw('DATE_FORMAT(daily_entries.report_date, "%Y-%m-%d") as report_date'),
                'categories.employment_type',
                DB::raw('SUM(daily_entries.headcount) as total')
            )
            ->groupBy('daily_entries.report_date', 'categories.employment_type')
            ->orderBy('daily_entries.report_date')
            ->get();

        // Get all dates
        $dates = $entries->pluck('report_date')->unique()->sort()->values()->toArray();

        // Build the data structure
        $data = [];
        $totals = ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0];

        foreach ($dates as $date) {
            $dateEntries = $entries->where('report_date', $date);
            $permanent = (int) $dateEntries->where('employment_type', 'Permanent')->sum('total');
            $contract = (int) $dateEntries->where('employment_type', 'Contract')->sum('total');
            $daily = (int) $dateEntries->where('employment_type', 'Daily')->sum('total');
            $total = $permanent + $contract + $daily;

            $data[] = [
                'date' => $date,
                'permanent' => $permanent,
                'contract' => $contract,
                'daily' => $daily,
                'total' => $total,
            ];

            // Accumulate totals
            $totals['Permanent'] += $permanent;
            $totals['Contract'] += $contract;
            $totals['Daily'] += $daily;
        }

        // Get latest headcount
        $latest = !empty($data) ? end($data) : ['permanent' => 0, 'contract' => 0, 'daily' => 0, 'total' => 0];

        return [
            'dates' => $dates,
            'data' => $data,
            'totals' => $totals,
            'latest' => $latest,
            'grand_total' => array_sum($totals),
            'count' => count($data),
        ];
    }

    /**
     * Get Daily Full Report with all job titles
     */
    public function getDailyFullReport($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $query = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->whereBetween('daily_entries.report_date', [$dateFrom, $dateTo]);

        if ($projectId > 0) {
            $query->where('daily_entries.project_id', $projectId);
        }

        if ($employmentType !== 'All') {
            $query->where('categories.employment_type', $employmentType);
        }

        return $query->select(
                DB::raw('DATE_FORMAT(daily_entries.report_date, "%Y-%m-%d") as report_date'),
                'projects.name as project',
                'categories.department',
                'categories.code',
                'categories.name as job_title',
                'categories.employment_type',
                'daily_entries.headcount'
            )
            ->orderBy('daily_entries.report_date')
            ->orderBy('projects.name')
            ->orderBy('categories.row_order')
            ->get();
    }

    /**
     * Get Daily Management Summary (by project per day)
     */
    public function getDailyManagement($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $query = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->whereBetween('daily_entries.report_date', [$dateFrom, $dateTo]);

        if ($projectId > 0) {
            $query->where('daily_entries.project_id', $projectId);
        }

        if ($employmentType !== 'All') {
            $query->where('categories.employment_type', $employmentType);
        }

        return $query->select(
                DB::raw('DATE_FORMAT(daily_entries.report_date, "%Y-%m-%d") as report_date'),
                'projects.name as project',
                DB::raw('SUM(CASE WHEN categories.employment_type = "Permanent" THEN daily_entries.headcount ELSE 0 END) as permanent'),
                DB::raw('SUM(CASE WHEN categories.employment_type = "Contract" THEN daily_entries.headcount ELSE 0 END) as contract'),
                DB::raw('SUM(CASE WHEN categories.employment_type = "Daily" THEN daily_entries.headcount ELSE 0 END) as daily'),
                DB::raw('SUM(daily_entries.headcount) as total')
            )
            ->groupBy('daily_entries.report_date', 'projects.id', 'projects.name')
            ->orderBy('daily_entries.report_date')
            ->orderBy('projects.name')
            ->get();
    }

    /**
     * Get Weekly Summary
     */
    public function getWeeklySummary($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $entries = $this->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
        
        // Group by week
        $weeks = [];
        foreach ($entries as $entry) {
            $date = Carbon::parse($entry->report_date);
            $weekKey = $date->year . '-W' . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT);
            $weekLabel = 'Week ' . $date->weekOfYear . ', ' . $date->year;
            
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'label' => $weekLabel,
                    'data' => [],
                    'totals' => ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0],
                    'days' => 0,
                ];
            }
            
            $weeks[$weekKey]['data'][] = $entry;
            $weeks[$weekKey]['days']++;
            
            if ($entry->employment_type === 'Permanent') {
                $weeks[$weekKey]['totals']['Permanent'] += $entry->headcount;
            } elseif ($entry->employment_type === 'Contract') {
                $weeks[$weekKey]['totals']['Contract'] += $entry->headcount;
            } elseif ($entry->employment_type === 'Daily') {
                $weeks[$weekKey]['totals']['Daily'] += $entry->headcount;
            }
        }

        // Calculate averages
        $result = [];
        foreach ($weeks as $key => $week) {
            $days = $week['days'];
            $result[] = [
                'week' => $week['label'],
                'permanent' => $days > 0 ? round($week['totals']['Permanent'] / $days) : 0,
                'contract' => $days > 0 ? round($week['totals']['Contract'] / $days) : 0,
                'daily' => $days > 0 ? round($week['totals']['Daily'] / $days) : 0,
                'days' => $days,
            ];
        }

        return $result;
    }

    /**
     * Get Weekly Management Summary
     */
    public function getWeeklyManagement($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $dailyManagement = $this->getDailyManagement($dateFrom, $dateTo, $projectId, $employmentType);
        
        // Group by week and project
        $weeks = [];
        foreach ($dailyManagement as $entry) {
            $date = Carbon::parse($entry->report_date);
            $weekKey = $date->year . '-W' . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT);
            $weekLabel = $date->format('F Y') . ' - Week ' . $date->weekOfYear . ' (' . $date->startOfWeek()->format('Y-m-d') . ' to ' . $date->endOfWeek()->format('Y-m-d') . ')';
            $projectKey = $entry->project;
            
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'label' => $weekLabel,
                    'projects' => [],
                    'totals' => ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0],
                    'days' => 0,
                ];
            }
            
            if (!isset($weeks[$weekKey]['projects'][$projectKey])) {
                $weeks[$weekKey]['projects'][$projectKey] = [
                    'project' => $projectKey,
                    'permanent' => 0,
                    'contract' => 0,
                    'daily' => 0,
                ];
            }
            
            $weeks[$weekKey]['projects'][$projectKey]['permanent'] += $entry->permanent;
            $weeks[$weekKey]['projects'][$projectKey]['contract'] += $entry->contract;
            $weeks[$weekKey]['projects'][$projectKey]['daily'] += $entry->daily;
            $weeks[$weekKey]['days']++;
        }

        // Calculate averages
        $result = [];
        foreach ($weeks as $weekKey => $week) {
            $days = $week['days'];
            foreach ($week['projects'] as $projectKey => $project) {
                $result[] = [
                    'week' => $week['label'],
                    'project' => $projectKey,
                    'permanent' => $days > 0 ? round($project['permanent'] / $days) : 0,
                    'contract' => $days > 0 ? round($project['contract'] / $days) : 0,
                    'daily' => $days > 0 ? round($project['daily'] / $days) : 0,
                ];
            }
            // Add total row
            $totalP = $days > 0 ? round(array_sum(array_column($week['projects'], 'permanent')) / $days) : 0;
            $totalC = $days > 0 ? round(array_sum(array_column($week['projects'], 'contract')) / $days) : 0;
            $totalD = $days > 0 ? round(array_sum(array_column($week['projects'], 'daily')) / $days) : 0;
            $result[] = [
                'week' => $week['label'],
                'project' => 'All Projects Total',
                'permanent' => $totalP,
                'contract' => $totalC,
                'daily' => $totalD,
            ];
        }

        return $result;
    }

    /**
     * Get Monthly Summary
     */
    public function getMonthlySummary($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $entries = $this->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
        
        // Group by month
        $months = [];
        foreach ($entries as $entry) {
            $date = Carbon::parse($entry->report_date);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('F Y');
            
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'label' => $monthLabel,
                    'data' => [],
                    'totals' => ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0],
                    'days' => 0,
                ];
            }
            
            $months[$monthKey]['data'][] = $entry;
            $months[$monthKey]['days']++;
            
            if ($entry->employment_type === 'Permanent') {
                $months[$monthKey]['totals']['Permanent'] += $entry->headcount;
            } elseif ($entry->employment_type === 'Contract') {
                $months[$monthKey]['totals']['Contract'] += $entry->headcount;
            } elseif ($entry->employment_type === 'Daily') {
                $months[$monthKey]['totals']['Daily'] += $entry->headcount;
            }
        }

        // Calculate averages
        $result = [];
        foreach ($months as $key => $month) {
            $days = $month['days'];
            $result[] = [
                'month' => $month['label'],
                'permanent' => $days > 0 ? round($month['totals']['Permanent'] / $days) : 0,
                'contract' => $days > 0 ? round($month['totals']['Contract'] / $days) : 0,
                'daily' => $days > 0 ? round($month['totals']['Daily'] / $days) : 0,
                'days' => $days,
            ];
        }

        return $result;
    }

    /**
     * Get Monthly Management Summary
     */
    public function getMonthlyManagement($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $dailyManagement = $this->getDailyManagement($dateFrom, $dateTo, $projectId, $employmentType);
        
        // Group by month and project
        $months = [];
        foreach ($dailyManagement as $entry) {
            $date = Carbon::parse($entry->report_date);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('F Y');
            $projectKey = $entry->project;
            
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'label' => $monthLabel,
                    'projects' => [],
                    'totals' => ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0],
                    'days' => 0,
                ];
            }
            
            if (!isset($months[$monthKey]['projects'][$projectKey])) {
                $months[$monthKey]['projects'][$projectKey] = [
                    'project' => $projectKey,
                    'permanent' => 0,
                    'contract' => 0,
                    'daily' => 0,
                ];
            }
            
            $months[$monthKey]['projects'][$projectKey]['permanent'] += $entry->permanent;
            $months[$monthKey]['projects'][$projectKey]['contract'] += $entry->contract;
            $months[$monthKey]['projects'][$projectKey]['daily'] += $entry->daily;
            $months[$monthKey]['days']++;
        }

        // Calculate averages
        $result = [];
        foreach ($months as $monthKey => $month) {
            $days = $month['days'];
            foreach ($month['projects'] as $projectKey => $project) {
                $result[] = [
                    'month' => $month['label'],
                    'project' => $projectKey,
                    'permanent' => $days > 0 ? round($project['permanent'] / $days) : 0,
                    'contract' => $days > 0 ? round($project['contract'] / $days) : 0,
                    'daily' => $days > 0 ? round($project['daily'] / $days) : 0,
                ];
            }
            // Add total row
            $totalP = $days > 0 ? round(array_sum(array_column($month['projects'], 'permanent')) / $days) : 0;
            $totalC = $days > 0 ? round(array_sum(array_column($month['projects'], 'contract')) / $days) : 0;
            $totalD = $days > 0 ? round(array_sum(array_column($month['projects'], 'daily')) / $days) : 0;
            $result[] = [
                'month' => $month['label'],
                'project' => 'All Projects Total',
                'permanent' => $totalP,
                'contract' => $totalC,
                'daily' => $totalD,
            ];
        }

        return $result;
    }

    /**
     * Get Daily Job Title Details
     */
    public function getDailyJobTitleDetails($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        return $this->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
    }

    /**
     * Get Weekly Job Title Details
     */
    public function getWeeklyJobTitleDetails($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $dailyDetails = $this->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
        
        // Group by week, project, department, code, job title, employment type
        $weeks = [];
        foreach ($dailyDetails as $entry) {
            $date = Carbon::parse($entry->report_date);
            $weekKey = $date->year . '-W' . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT);
            $weekLabel = $date->format('F Y') . ' - Week ' . $date->weekOfYear . ' (' . $date->startOfWeek()->format('Y-m-d') . ' to ' . $date->endOfWeek()->format('Y-m-d') . ')';
            $groupKey = $weekKey . '|' . $entry->project . '|' . $entry->department . '|' . $entry->code . '|' . $entry->job_title . '|' . $entry->employment_type;
            
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'label' => $weekLabel,
                    'items' => [],
                    'days' => 0,
                ];
            }
            
            if (!isset($weeks[$weekKey]['items'][$groupKey])) {
                $weeks[$weekKey]['items'][$groupKey] = [
                    'project' => $entry->project,
                    'department' => $entry->department,
                    'code' => $entry->code,
                    'job_title' => $entry->job_title,
                    'employment_type' => $entry->employment_type,
                    'total' => 0,
                ];
            }
            
            $weeks[$weekKey]['items'][$groupKey]['total'] += $entry->headcount;
            $weeks[$weekKey]['days']++;
        }

        // Calculate averages
        $result = [];
        foreach ($weeks as $weekKey => $week) {
            $days = $week['days'];
            foreach ($week['items'] as $item) {
                $result[] = [
                    'week' => $week['label'],
                    'project' => $item['project'],
                    'department' => $item['department'],
                    'code' => $item['code'],
                    'job_title' => $item['job_title'],
                    'employment_type' => $item['employment_type'],
                    'headcount' => $days > 0 ? round($item['total'] / $days) : 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Get Monthly Job Title Details
     */
    public function getMonthlyJobTitleDetails($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $dailyDetails = $this->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
        
        // Group by month, project, department, code, job title, employment type
        $months = [];
        foreach ($dailyDetails as $entry) {
            $date = Carbon::parse($entry->report_date);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('F Y');
            $groupKey = $monthKey . '|' . $entry->project . '|' . $entry->department . '|' . $entry->code . '|' . $entry->job_title . '|' . $entry->employment_type;
            
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'label' => $monthLabel,
                    'items' => [],
                    'days' => 0,
                ];
            }
            
            if (!isset($months[$monthKey]['items'][$groupKey])) {
                $months[$monthKey]['items'][$groupKey] = [
                    'project' => $entry->project,
                    'department' => $entry->department,
                    'code' => $entry->code,
                    'job_title' => $entry->job_title,
                    'employment_type' => $entry->employment_type,
                    'total' => 0,
                ];
            }
            
            $months[$monthKey]['items'][$groupKey]['total'] += $entry->headcount;
            $months[$monthKey]['days']++;
        }

        // Calculate averages
        $result = [];
        foreach ($months as $monthKey => $month) {
            $days = $month['days'];
            foreach ($month['items'] as $item) {
                $result[] = [
                    'month' => $month['label'],
                    'project' => $item['project'],
                    'department' => $item['department'],
                    'code' => $item['code'],
                    'job_title' => $item['job_title'],
                    'employment_type' => $item['employment_type'],
                    'headcount' => $days > 0 ? round($item['total'] / $days) : 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Get Project Summary
     */
    public function getProjectSummary($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $query = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->whereBetween('daily_entries.report_date', [$dateFrom, $dateTo]);

        if ($projectId > 0) {
            $query->where('daily_entries.project_id', $projectId);
        }

        if ($employmentType !== 'All') {
            $query->where('categories.employment_type', $employmentType);
        }

        return $query->select(
                'projects.name as project',
                DB::raw('SUM(CASE WHEN categories.employment_type = "Permanent" THEN daily_entries.headcount ELSE 0 END) as permanent'),
                DB::raw('SUM(CASE WHEN categories.employment_type = "Contract" THEN daily_entries.headcount ELSE 0 END) as contract'),
                DB::raw('SUM(CASE WHEN categories.employment_type = "Daily" THEN daily_entries.headcount ELSE 0 END) as daily'),
                DB::raw('SUM(daily_entries.headcount) as total')
            )
            ->groupBy('projects.id', 'projects.name')
            ->orderBy('total', 'desc')
            ->get();
    }

    /**
     * Get Department Summary
     */
    public function getDepartmentSummary($dateFrom, $dateTo, $projectId = 0, $employmentType = 'All')
    {
        $query = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->whereBetween('daily_entries.report_date', [$dateFrom, $dateTo]);

        if ($projectId > 0) {
            $query->where('daily_entries.project_id', $projectId);
        }

        if ($employmentType !== 'All') {
            $query->where('categories.employment_type', $employmentType);
        }

        return $query->select(
                'categories.department',
                'categories.employment_type',
                DB::raw('SUM(daily_entries.headcount) as total')
            )
            ->groupBy('categories.department', 'categories.employment_type')
            ->orderBy('categories.department')
            ->get();
    }
}