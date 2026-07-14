<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComparisonController extends Controller
{
    public function index(Request $request)
    {
        // Get available dates
        $dates = DailyEntry::select(DB::raw('DISTINCT DATE_FORMAT(report_date, "%Y-%m-%d") as report_date'))
            ->orderBy('report_date')
            ->pluck('report_date')
            ->toArray();

        if (empty($dates)) {
            $dates = [now()->format('Y-m-d')];
        }

        $date1 = $request->input('date1', $dates[0] ?? now()->format('Y-m-d'));
        $date2 = $request->input('date2', end($dates) ?? now()->format('Y-m-d'));

        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        $data1 = $this->getProjectTotals($date1);
        $data2 = $this->getProjectTotals($date2);

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
            ];

            $total1 += $x;
            $total2 += $y;
        }

        $diffTotal = $total2 - $total1;
        $pctTotal = $total1 > 0 ? ($diffTotal / $total1 * 100) : 0;

        return view('comparison.index', compact(
            'dates',
            'date1',
            'date2',
            'comparisonData',
            'total1',
            'total2',
            'diffTotal',
            'pctTotal'
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
}