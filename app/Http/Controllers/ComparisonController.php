<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComparisonController extends Controller
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

        $date1 = $request->input('date1', $dates[0] ?? now()->format('Y-m-d'));
        $date2 = $request->input('date2', end($dates) ?? now()->format('Y-m-d'));

        // Get all projects
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        // Get data for both dates
        $data1 = $this->getProjectTotals($date1);
        $data2 = $this->getProjectTotals($date2);

        // Get detailed data for both dates
        $detailedData1 = $this->getDetailedTotals($date1);
        $detailedData2 = $this->getDetailedTotals($date2);

        // Prepare comparison data
        $projectNames = array_unique(array_merge(array_keys($data1), array_keys($data2)));
        sort($projectNames);

        $comparisonData = [];
        $total1 = 0;
        $total2 = 0;

        foreach ($projectNames as $name) {
            $x = $data1[$name] ?? 0;
            $y = $data2[$name] ?? 0;
            $diff = $y - $x;
            $pct = $x > 0 ? ($diff / $x * 100) : ($y > 0 ? 100 : 0);

            $comparisonData[] = [
                'name' => $name,
                'prev' => $x,
                'current' => $y,
                'diff' => $diff,
                'pct' => $pct,
                'status' => $this->getStatus($diff),
            ];

            $total1 += $x;
            $total2 += $y;
        }

        $diffTotal = $total2 - $total1;
        $pctTotal = $total1 > 0 ? ($diffTotal / $total1 * 100) : 0;

        // Get summary statistics
        $summary = $this->getSummaryStats($comparisonData);

        return view('comparison.index', compact(
            'dates',
            'date1',
            'date2',
            'comparisonData',
            'total1',
            'total2',
            'diffTotal',
            'pctTotal',
            'summary',
            'detailedData1',
            'detailedData2'
        ));
    }

    private function getProjectTotals($date)
    {
        $results = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('daily_entries.report_date', $date)
            ->where('categories.is_reconciliation', false)
            ->select('projects.name', DB::raw('COALESCE(SUM(daily_entries.headcount), 0) as total'))
            ->groupBy('projects.id', 'projects.name')
            ->orderByRaw('COALESCE(projects.slot, 999), projects.name')
            ->get();

        return $results->pluck('total', 'name')->toArray();
    }

    private function getDetailedTotals($date)
    {
        $results = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('daily_entries.report_date', $date)
            ->where('categories.is_reconciliation', false)
            ->select(
                'projects.name as project',
                'categories.employment_type',
                DB::raw('SUM(daily_entries.headcount) as total')
            )
            ->groupBy('projects.id', 'projects.name', 'categories.employment_type')
            ->get();

        $data = [];
        foreach ($results as $row) {
            if (!isset($data[$row->project])) {
                $data[$row->project] = ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0];
            }
            $data[$row->project][$row->employment_type] = (int) $row->total;
        }
        return $data;
    }

    private function getStatus($diff)
    {
        if ($diff > 0) return 'increase';
        if ($diff < 0) return 'decrease';
        return 'same';
    }

    private function getSummaryStats($data)
    {
        $totals = array_column($data, 'current');
        $prevTotals = array_column($data, 'prev');
        
        return [
            'max' => !empty($totals) ? max($totals) : 0,
            'min' => !empty($totals) ? min($totals) : 0,
            'avg' => !empty($totals) ? round(array_sum($totals) / count($totals), 2) : 0,
            'max_prev' => !empty($prevTotals) ? max($prevTotals) : 0,
            'min_prev' => !empty($prevTotals) ? min($prevTotals) : 0,
            'avg_prev' => !empty($prevTotals) ? round(array_sum($prevTotals) / count($prevTotals), 2) : 0,
            'increased' => count(array_filter($data, function($item) { return $item['diff'] > 0; })),
            'decreased' => count(array_filter($data, function($item) { return $item['diff'] < 0; })),
            'unchanged' => count(array_filter($data, function($item) { return $item['diff'] == 0; })),
        ];
    }
}