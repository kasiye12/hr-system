<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $canEdit = $user->canEdit();
        $selectedDate = $request->input('date');
        $message = $request->input('message');

        // Get active projects
        $projects = Project::where('status', 'Active')
            ->orderByRaw('COALESCE(slot, 999), name')
            ->get();

        if ($projects->isEmpty()) {
            return redirect()->route('projects.index')
                ->with('error', 'Please create an active project first.');
        }

        // Get selected date - if not provided, get the latest date with data or today
        if (!$selectedDate) {
            $latestDate = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
                ->where('categories.is_reconciliation', false)
                ->max('report_date');
            
            $selectedDate = $latestDate ?? now()->format('Y-m-d');
        }

        // Get selected project
        $selectedProjectId = (int) $request->input('project', $projects->first()->id);
        $selectedProject = Project::find($selectedProjectId);

        // Get categories (job titles)
        $categories = Category::where('is_reconciliation', false)
            ->orderBy('row_order')
            ->get();

        // Get existing entries for the selected date and project
        $entries = DailyEntry::where([
            'report_date' => $selectedDate,
            'project_id' => $selectedProjectId,
        ])
        ->pluck('headcount', 'category_id')
        ->toArray();

        // Calculate totals by employment type
        $typeTotals = ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0];
        foreach ($categories as $category) {
            if (isset($typeTotals[$category->employment_type])) {
                $typeTotals[$category->employment_type] += $entries[$category->id] ?? 0;
            }
        }
        $grandTotal = array_sum($typeTotals);

        return view('daily.index', compact(
            'selectedDate',
            'projects',
            'selectedProjectId',
            'selectedProject',
            'categories',
            'entries',
            'typeTotals',
            'grandTotal',
            'canEdit',
            'message'
        ));
    }

    public function save(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('daily.index')
                ->with('error', 'You do not have permission to edit data.');
        }

        $request->validate([
            'date' => 'required|date',
            'project' => 'required|integer|exists:projects,id',
        ]);

        $date = $request->input('date');
        $projectId = (int) $request->input('project');

        DB::beginTransaction();

        try {
            $validCategories = Category::where('is_reconciliation', false)
                ->pluck('id')
                ->toArray();

            $savedCount = 0;
            $deletedCount = 0;

            foreach ($request->all() as $key => $value) {
                if (!str_starts_with($key, 'cat_')) {
                    continue;
                }

                $categoryId = (int) substr($key, 4);

                if (!in_array($categoryId, $validCategories)) {
                    continue;
                }

                $headcount = (int) ($value ?? 0);

                if ($headcount < 0) {
                    throw new \Exception('Headcount cannot be negative.');
                }

                if ($headcount == 0) {
                    // Delete entry if headcount is 0
                    $deleted = DailyEntry::where([
                        'report_date' => $date,
                        'project_id' => $projectId,
                        'category_id' => $categoryId,
                    ])->delete();
                    
                    if ($deleted) {
                        $deletedCount++;
                    }
                } else {
                    // Update or create entry
                    DailyEntry::updateOrCreate(
                        [
                            'report_date' => $date,
                            'project_id' => $projectId,
                            'category_id' => $categoryId,
                        ],
                        ['headcount' => $headcount]
                    );
                    $savedCount++;
                }
            }

            AuditLog::create([
                'username' => $user->username,
                'action' => 'save_daily',
                'details' => "{$date} | project_id={$projectId} | saved: {$savedCount}, deleted: {$deletedCount}",
            ]);

            DB::commit();

            return redirect()->route('daily.index', [
                'date' => $date,
                'project' => $projectId,
                'message' => '✅ Daily input saved successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('daily.index', [
                'date' => $date,
                'project' => $projectId,
            ])->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $date = $request->input('date');
        $projectId = (int) $request->input('project', 0);

        if (!$date) {
            return redirect()->route('daily.index')
                ->with('error', 'Please select a date to export.');
        }

        // Build export data
        $query = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('daily_entries.report_date', $date)
            ->where('categories.is_reconciliation', false);

        if ($projectId > 0) {
            $query->where('daily_entries.project_id', $projectId);
        }

        $data = $query->select(
                DB::raw("'{$date}' as report_date"),
                'projects.name as project',
                'categories.department',
                'categories.code',
                'categories.name as job_title',
                'categories.employment_type',
                'daily_entries.headcount'
            )
            ->orderBy('projects.name')
            ->orderBy('categories.row_order')
            ->get();

        if ($data->isEmpty()) {
            return redirect()->route('daily.index', ['date' => $date, 'project' => $projectId])
                ->with('error', 'No data found for the selected date.');
        }

        // Generate CSV
        $filename = "HR_Daily_{$date}.csv";
        $handle = fopen('php://temp', 'w+');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");
        
        fputcsv($handle, ['Report Date', 'Project', 'Department', 'Code', 'Job Title', 'Employment Type', 'Headcount']);
        
        foreach ($data as $row) {
            fputcsv($handle, [
                $row->report_date,
                $row->project,
                $row->department,
                $row->code,
                $row->job_title,
                $row->employment_type,
                $row->headcount
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        // Log the export
        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'export_daily_csv',
            'details' => "{$date} | project_id={$projectId}",
        ]);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    // API endpoint for getting daily data (for AJAX)
    public function getData(Request $request)
    {
        $date = $request->input('date');
        $projectId = (int) $request->input('project', 0);

        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        $entries = DailyEntry::where('report_date', $date)
            ->when($projectId > 0, function($query) use ($projectId) {
                return $query->where('project_id', $projectId);
            })
            ->with(['category', 'project'])
            ->get();

        return response()->json([
            'date' => $date,
            'entries' => $entries,
            'total' => $entries->sum('headcount'),
        ]);
    }

    // Get available dates for dropdown
    public function getDates(Request $request)
    {
        $dates = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->select(DB::raw('DISTINCT DATE_FORMAT(report_date, "%Y-%m-%d") as report_date'))
            ->orderBy('report_date', 'desc')
            ->pluck('report_date')
            ->toArray();

        return response()->json($dates);
    }

    // Get project totals for a date
    public function getProjectTotals(Request $request)
    {
        $date = $request->input('date');

        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        $totals = DailyEntry::join('projects', 'projects.id', '=', 'daily_entries.project_id')
            ->join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('daily_entries.report_date', $date)
            ->where('categories.is_reconciliation', false)
            ->select(
                'projects.name as project',
                DB::raw('SUM(daily_entries.headcount) as total')
            )
            ->groupBy('projects.id', 'projects.name')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json($totals);
    }
}