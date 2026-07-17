<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DailyController extends Controller
{
    /**
     * Display the daily input page
     */
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

    /**
     * Save daily input data
     */
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

    /**
     * Export daily data as CSV
     */
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

    /**
     * Export daily data as professional Excel
     */
    public function exportExcel(Request $request)
    {
        $date = $request->input('date');
        $projectId = (int) $request->input('project', 0);

        if (!$date) {
            return redirect()->route('daily.index')
                ->with('error', 'Please select a date to export.');
        }

        // Get project
        $project = Project::find($projectId);
        $projectName = $project ? $project->name : 'All Projects';

        // Get categories with entries
        $categories = Category::where('is_reconciliation', false)
            ->orderBy('row_order')
            ->get();

        // Get entries for the selected date and project
        $entries = DailyEntry::where('report_date', $date)
            ->when($projectId > 0, function($query) use ($projectId) {
                return $query->where('project_id', $projectId);
            })
            ->get()
            ->keyBy('category_id');

        // Calculate totals
        $typeTotals = ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0];
        $departmentTotals = [];

        foreach ($categories as $category) {
            $headcount = $entries[$category->id]->headcount ?? 0;
            if (isset($typeTotals[$category->employment_type])) {
                $typeTotals[$category->employment_type] += $headcount;
            }
            
            if (!isset($departmentTotals[$category->department])) {
                $departmentTotals[$category->department] = ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0, 'Total' => 0];
            }
            $departmentTotals[$category->department][$category->employment_type] += $headcount;
            $departmentTotals[$category->department]['Total'] += $headcount;
        }

        $grandTotal = array_sum($typeTotals);

        // Generate Excel
        return $this->generateDailyExcel($date, $projectName, $categories, $entries, $typeTotals, $departmentTotals, $grandTotal);
    }

    /**
     * Generate Professional Daily Excel
     */
    private function generateDailyExcel($date, $projectName, $categories, $entries, $typeTotals, $departmentTotals, $grandTotal)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TNT HR Reporting')
            ->setLastModifiedBy('TNT HR Reporting')
            ->setTitle('Daily Manpower Report')
            ->setSubject('Daily Manpower')
            ->setDescription('Daily Manpower Report - ' . $date)
            ->setKeywords('HR, Manpower, Daily Report')
            ->setCategory('HR Report');

        // ============================================
        // SHEET 1: DAILY MANPOWER REPORT
        // ============================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daily Manpower Report');

        $row = 1;

        // ============================================
        // HEADER SECTION
        // ============================================
        // Title
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'TNT HR Manpower Reporting');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(40);
        $row++;

        // Subtitle
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A' . $row, 'DAILY MANPOWER REPORT');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1b7f79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        // Report Info
        $info = [
            ['Report Date:', $date],
            ['Project:', $projectName],
            ['Generated:', now()->format('Y-m-d H:i:s')],
            ['Generated By:', Auth::user()->username ?? 'System'],
        ];

        foreach ($info as $infoRow) {
            $sheet->setCellValue('A' . $row, $infoRow[0]);
            $sheet->setCellValue('B' . $row, $infoRow[1]);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
            ]);
            $row++;
        }
        $row++;

        // ============================================
        // SUMMARY SECTION
        // ============================================
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A' . $row, '📊 SUMMARY');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        // Summary Headers
        $headers = ['Employment Type', 'Headcount'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Summary Data
        $summaryData = [
            ['Permanent', $typeTotals['Permanent']],
            ['Contract', $typeTotals['Contract']],
            ['Daily Labor', $typeTotals['Daily']],
            ['Grand Total', $grandTotal],
        ];

        foreach ($summaryData as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            if ($data[0] === 'Grand Total') {
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                ]);
            }
            $row++;
        }
        $row += 2;

        // ============================================
        // DEPARTMENT SUMMARY SECTION
        // ============================================
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A' . $row, '🏢 DEPARTMENT SUMMARY');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        // Department Headers
        $deptHeaders = ['Department', 'Permanent', 'Contract', 'Daily', 'Total'];
        $col = 'A';
        foreach ($deptHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Department Data
        $deptGrandTotal = ['Permanent' => 0, 'Contract' => 0, 'Daily' => 0, 'Total' => 0];
        foreach ($departmentTotals as $dept => $totals) {
            $sheet->setCellValue('A' . $row, $dept);
            $sheet->setCellValue('B' . $row, $totals['Permanent']);
            $sheet->setCellValue('C' . $row, $totals['Contract']);
            $sheet->setCellValue('D' . $row, $totals['Daily']);
            $sheet->setCellValue('E' . $row, $totals['Total']);
            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            
            $deptGrandTotal['Permanent'] += $totals['Permanent'];
            $deptGrandTotal['Contract'] += $totals['Contract'];
            $deptGrandTotal['Daily'] += $totals['Daily'];
            $deptGrandTotal['Total'] += $totals['Total'];
            $row++;
        }

        // Department Grand Total
        $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
        $sheet->setCellValue('B' . $row, $deptGrandTotal['Permanent']);
        $sheet->setCellValue('C' . $row, $deptGrandTotal['Contract']);
        $sheet->setCellValue('D' . $row, $deptGrandTotal['Daily']);
        $sheet->setCellValue('E' . $row, $deptGrandTotal['Total']);
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $row += 2;

        // ============================================
        // DETAILED JOB TITLE SECTION
        // ============================================
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A' . $row, '📋 JOB TITLE DETAILS');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        // Detail Headers
        $detailHeaders = ['Department', 'Code', 'Job Title', 'Employment Type', 'Headcount'];
        $col = 'A';
        foreach ($detailHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Detail Data - Group by Department
        $currentDept = '';
        foreach ($categories as $category) {
            $headcount = $entries[$category->id]->headcount ?? 0;
            
            // Highlight department change
            if ($category->department !== $currentDept) {
                $sheet->setCellValue('A' . $row, $category->department);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE9F2']],
                ]);
                $sheet->mergeCells('A' . $row . ':D' . $row);
                $currentDept = $category->department;
                $row++;
            }
            
            $sheet->setCellValue('B' . $row, $category->code);
            $sheet->setCellValue('C' . $row, $category->name);
            $sheet->setCellValue('D' . $row, $category->employment_type);
            $sheet->setCellValue('E' . $row, $headcount);
            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            
            // Color code by employment type
            if ($category->employment_type === 'Permanent') {
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCF5E7']],
                ]);
            } elseif ($category->employment_type === 'Contract') {
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6E3FF']],
                ]);
            } elseif ($category->employment_type === 'Daily') {
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF5E5']],
                ]);
            }
            
            $row++;
        }

        // ============================================
        // FOOTER
        // ============================================
        $row += 2;
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A' . $row, 'Generated by TNT HR Reporting System • ' . now()->format('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '627386']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ============================================
        // FORMATTING - Auto-size columns
        // ============================================
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting to numeric columns
        $sheet->getStyle('B2:E' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // ============================================
        // SHEET 2: CHART DATA (Optional)
        // ============================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Chart Data');
        $row = 1;

        // Chart headers
        $sheet2->setCellValue('A' . $row, 'Department');
        $sheet2->setCellValue('B' . $row, 'Permanent');
        $sheet2->setCellValue('C' . $row, 'Contract');
        $sheet2->setCellValue('D' . $row, 'Daily');
        $sheet2->setCellValue('E' . $row, 'Total');
        $sheet2->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
        ]);
        $row++;

        foreach ($departmentTotals as $dept => $totals) {
            $sheet2->setCellValue('A' . $row, $dept);
            $sheet2->setCellValue('B' . $row, $totals['Permanent']);
            $sheet2->setCellValue('C' . $row, $totals['Contract']);
            $sheet2->setCellValue('D' . $row, $totals['Daily']);
            $sheet2->setCellValue('E' . $row, $totals['Total']);
            $row++;
        }

        // Total row
        $sheet2->setCellValue('A' . $row, 'GRAND TOTAL');
        $sheet2->setCellValue('B' . $row, $deptGrandTotal['Permanent']);
        $sheet2->setCellValue('C' . $row, $deptGrandTotal['Contract']);
        $sheet2->setCellValue('D' . $row, $deptGrandTotal['Daily']);
        $sheet2->setCellValue('E' . $row, $deptGrandTotal['Total']);
        $sheet2->getStyle('A' . $row . ':E' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
        ]);

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ============================================
        // SAVE AND DOWNLOAD
        // ============================================
        $writer = new Xlsx($spreadsheet);
        $filename = 'Daily_Manpower_Report_' . $date . '_' . now()->format('Ymd_His') . '.xlsx';

        // Log the export
        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'export_daily_excel',
            'details' => "Exported daily report for {$date}",
        ]);

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'daily_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get available dates for dropdown (API)
     */
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

    /**
     * Get project totals for a date (API)
     */
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