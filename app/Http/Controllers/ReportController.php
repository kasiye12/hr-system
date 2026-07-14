<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use App\Models\AuditLog;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        // Get available dates
        $dates = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->select(DB::raw('DISTINCT DATE_FORMAT(report_date, "%Y-%m-%d") as report_date'))
            ->orderBy('report_date')
            ->pluck('report_date')
            ->toArray();

        // Get projects
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        $firstDate = $dates[0] ?? now()->format('Y-m-d');
        $lastDate = end($dates) ?? now()->format('Y-m-d');

        return view('reports.index', compact('dates', 'projects', 'firstDate', 'lastDate'));
    }

    public function dashboard(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $projectId = (int) $request->input('project', 0);
        $employmentType = $request->input('employment_type', 'All');

        // Get report data
        $dashboardData = $this->reportService->getManagementDashboard($dateFrom, $dateTo, $projectId, $employmentType);
        $projectSummary = $this->reportService->getProjectSummary($dateFrom, $dateTo, $projectId, $employmentType);
        $departmentSummary = $this->reportService->getDepartmentSummary($dateFrom, $dateTo, $projectId, $employmentType);

        // Get projects for filter
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        return view('reports.dashboard', compact(
            'dashboardData',
            'projectSummary',
            'departmentSummary',
            'projects',
            'dateFrom',
            'dateTo',
            'projectId',
            'employmentType'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'project' => 'required|integer|min:0',
            'employment_type' => 'required|in:All,Permanent,Contract,Daily',
            'scope' => 'required|in:complete,daily,weekly,monthly,management,job_titles',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $projectId = (int) $request->input('project', 0);
        $employmentType = $request->input('employment_type', 'All');
        $scope = $request->input('scope', 'complete');

        // Get all data based on scope
        $data = [];
        
        if (in_array($scope, ['complete', 'management'])) {
            $data['management_dashboard'] = $this->reportService->getManagementDashboard($dateFrom, $dateTo, $projectId, $employmentType);
            $data['daily_management'] = $this->reportService->getDailyManagement($dateFrom, $dateTo, $projectId, $employmentType);
            $data['weekly_management'] = $this->reportService->getWeeklyManagement($dateFrom, $dateTo, $projectId, $employmentType);
            $data['monthly_management'] = $this->reportService->getMonthlyManagement($dateFrom, $dateTo, $projectId, $employmentType);
            $data['project_summary'] = $this->reportService->getProjectSummary($dateFrom, $dateTo, $projectId, $employmentType);
            $data['department_summary'] = $this->reportService->getDepartmentSummary($dateFrom, $dateTo, $projectId, $employmentType);
        }

        if (in_array($scope, ['complete', 'daily'])) {
            $data['daily_full'] = $this->reportService->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
            $data['daily_job_titles'] = $this->reportService->getDailyJobTitleDetails($dateFrom, $dateTo, $projectId, $employmentType);
        }

        if (in_array($scope, ['complete', 'weekly'])) {
            $data['weekly_full'] = $this->reportService->getWeeklySummary($dateFrom, $dateTo, $projectId, $employmentType);
            $data['weekly_job_titles'] = $this->reportService->getWeeklyJobTitleDetails($dateFrom, $dateTo, $projectId, $employmentType);
        }

        if (in_array($scope, ['complete', 'monthly'])) {
            $data['monthly_full'] = $this->reportService->getMonthlySummary($dateFrom, $dateTo, $projectId, $employmentType);
            $data['monthly_job_titles'] = $this->reportService->getMonthlyJobTitleDetails($dateFrom, $dateTo, $projectId, $employmentType);
        }

        if (in_array($scope, ['job_titles'])) {
            $data['daily_job_titles'] = $this->reportService->getDailyJobTitleDetails($dateFrom, $dateTo, $projectId, $employmentType);
            $data['weekly_job_titles'] = $this->reportService->getWeeklyJobTitleDetails($dateFrom, $dateTo, $projectId, $employmentType);
            $data['monthly_job_titles'] = $this->reportService->getMonthlyJobTitleDetails($dateFrom, $dateTo, $projectId, $employmentType);
        }

        // Log the export
        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_xlsx',
            'details' => "Report exported: {$dateFrom} to {$dateTo}",
        ]);

        // Generate Excel with multiple sheets
        return $this->generateExcel($data, $dateFrom, $dateTo, $projectId, $employmentType, $scope);
    }

    private function generateExcel($data, $dateFrom, $dateTo, $projectId, $employmentType, $scope)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TNT HR Reporting')
            ->setLastModifiedBy('TNT HR Reporting')
            ->setTitle('TNT HR Manpower Report')
            ->setSubject('HR Manpower Report')
            ->setDescription('Comprehensive HR Manpower Report')
            ->setKeywords('HR, Manpower, Report')
            ->setCategory('HR Report');

        $sheetIndex = 0;

        // ============================================
        // SHEET 1: Management Dashboard
        // ============================================
        if (isset($data['management_dashboard'])) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Management Dashboard');
            $this->buildManagementDashboardSheet($sheet, $data['management_dashboard'], $dateFrom, $dateTo, $projectId, $employmentType);
        }

        // ============================================
        // SHEET 2: Daily Full Report
        // ============================================
        if (isset($data['daily_full']) && $data['daily_full']->count() > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Daily Full Report');
            $this->buildDailyFullReportSheet($sheet, $data['daily_full']);
        }

        // ============================================
        // SHEET 3: Weekly Full Report
        // ============================================
        if (isset($data['weekly_full']) && count($data['weekly_full']) > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Weekly Full Report');
            $this->buildPeriodFullReportSheet($sheet, $data['weekly_full'], 'Weekly');
        }

        // ============================================
        // SHEET 4: Monthly Full Report
        // ============================================
        if (isset($data['monthly_full']) && count($data['monthly_full']) > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Monthly Full Report');
            $this->buildPeriodFullReportSheet($sheet, $data['monthly_full'], 'Monthly');
        }

        // ============================================
        // SHEET 5: Daily Management
        // ============================================
        if (isset($data['daily_management']) && $data['daily_management']->count() > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Daily Management');
            $this->buildManagementSummarySheet($sheet, $data['daily_management'], 'Period');
        }

        // ============================================
        // SHEET 6: Weekly Management
        // ============================================
        if (isset($data['weekly_management']) && count($data['weekly_management']) > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Weekly Management');
            $this->buildManagementSummarySheet($sheet, $data['weekly_management'], 'Period');
        }

        // ============================================
        // SHEET 7: Monthly Management
        // ============================================
        if (isset($data['monthly_management']) && count($data['monthly_management']) > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Monthly Management');
            $this->buildManagementSummarySheet($sheet, $data['monthly_management'], 'Period');
        }

        // ============================================
        // SHEET 8: Daily Job Title Details
        // ============================================
        if (isset($data['daily_job_titles']) && $data['daily_job_titles']->count() > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Daily Job Title Details');
            $this->buildJobTitleDetailsSheet($sheet, $data['daily_job_titles'], 'Period');
        }

        // ============================================
        // SHEET 9: Weekly Job Title Details
        // ============================================
        if (isset($data['weekly_job_titles']) && count($data['weekly_job_titles']) > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Weekly Job Title Details');
            $this->buildJobTitleDetailsSheet($sheet, $data['weekly_job_titles'], 'Period');
        }

        // ============================================
        // SHEET 10: Monthly Job Title Details
        // ============================================
        if (isset($data['monthly_job_titles']) && count($data['monthly_job_titles']) > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Monthly Job Title Details');
            $this->buildJobTitleDetailsSheet($sheet, $data['monthly_job_titles'], 'Period');
        }

        // ============================================
        // SHEET 11: Project Summary
        // ============================================
        if (isset($data['project_summary']) && $data['project_summary']->count() > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Project Summary');
            $this->buildProjectSummarySheet($sheet, $data['project_summary']);
        }

        // ============================================
        // SHEET 12: Department Summary
        // ============================================
        if (isset($data['department_summary']) && $data['department_summary']->count() > 0) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle('Department Summary');
            $this->buildDepartmentSummarySheet($sheet, $data['department_summary']);
        }

        // ============================================
        // SHEET 13: Report Info
        // ============================================
        $sheet = $spreadsheet->createSheet($sheetIndex++);
        $sheet->setTitle('Report Info');
        $this->buildReportInfoSheet($sheet, $dateFrom, $dateTo, $projectId, $employmentType, $scope, $data);

        // Remove default sheet if we created others
        if ($sheetIndex > 0) {
            $spreadsheet->removeSheetByIndex(0);
        }

        // Set active sheet to first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Create writer
        $writer = new Xlsx($spreadsheet);
        $filename = "TNT_HR_Report_{$scope}_{$dateFrom}_to_{$dateTo}_" . now()->format('Ymd_His') . ".xlsx";

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'report_');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildManagementDashboardSheet($sheet, $data, $dateFrom, $dateTo, $projectId, $employmentType)
    {
        $row = 1;

        // Title
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'TNT HR Report – Management Dashboard');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        // Generated
        $sheet->setCellValue('A' . $row, 'Generated');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $row++;

        // Report Parameters
        $sheet->setCellValue('A' . $row, 'Report Parameters');
        $projectName = $projectId > 0 ? (Project::find($projectId)->name ?? 'Selected Project') : 'All Projects';
        $sheet->setCellValue('B' . $row, "{$dateFrom} to {$dateTo} | {$projectName} | {$employmentType}");
        $row += 2;

        // Latest Headcount section
        $sheet->setCellValue('A' . $row, 'Employee Status');
        $sheet->setCellValue('B' . $row, 'Latest Headcount');
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $row++;

        // Data rows
        $statuses = [
            ['Permanent', $data['latest']['permanent']],
            ['Contract', $data['latest']['contract']],
            ['Daily Labor', $data['latest']['daily']],
            ['Total Manpower', $data['latest']['total']],
        ];
        foreach ($statuses as $status) {
            $sheet->setCellValue('A' . $row, $status[0]);
            $sheet->setCellValue('B' . $row, $status[1]);
            if ($status[0] === 'Total Manpower') {
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
                ]);
            }
            $row++;
        }
        $row += 2;

        // Daily Data Table
        $sheet->setCellValue('A' . $row, 'Report Date');
        $sheet->setCellValue('B' . $row, 'Permanent');
        $sheet->setCellValue('C' . $row, 'Contract');
        $sheet->setCellValue('D' . $row, 'Daily Labor');
        $sheet->setCellValue('E' . $row, 'Total');
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $row++;

        // Data rows
        foreach ($data['data'] as $rowData) {
            $sheet->setCellValue('A' . $row, $rowData['date']);
            $sheet->setCellValue('B' . $row, $rowData['permanent']);
            $sheet->setCellValue('C' . $row, $rowData['contract']);
            $sheet->setCellValue('D' . $row, $rowData['daily']);
            $sheet->setCellValue('E' . $row, $rowData['total']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('B2:E' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildDailyFullReportSheet($sheet, $data)
    {
        $row = 1;

        // Title
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'TNT HR Report – Daily Full Report');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Generated
        $sheet->setCellValue('A' . $row, 'Generated');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $row++;

        // Reporting Basis
        $sheet->setCellValue('A' . $row, 'Reporting Basis');
        $sheet->setCellValue('B' . $row, 'Actual saved headcount by date');
        $row++;

        // Report Parameters
        $sheet->setCellValue('A' . $row, 'Report Parameters');
        $sheet->setCellValue('B' . $row, '');
        $row += 2;

        // Management Summary by Project
        $sheet->setCellValue('A' . $row, 'MANAGEMENT SUMMARY BY PROJECT');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
        ]);
        $row++;

        // Headers
        $headers = ['Period', 'Project', 'Permanent', 'Contract', 'Daily Labor', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Group by date and project
        $grouped = [];
        foreach ($data as $entry) {
            $key = $entry->report_date . '|' . $entry->project;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'date' => $entry->report_date,
                    'project' => $entry->project,
                    'permanent' => 0,
                    'contract' => 0,
                    'daily' => 0,
                ];
            }
            if ($entry->employment_type === 'Permanent') {
                $grouped[$key]['permanent'] += $entry->headcount;
            } elseif ($entry->employment_type === 'Contract') {
                $grouped[$key]['contract'] += $entry->headcount;
            } elseif ($entry->employment_type === 'Daily') {
                $grouped[$key]['daily'] += $entry->headcount;
            }
        }

        foreach ($grouped as $item) {
            $total = $item['permanent'] + $item['contract'] + $item['daily'];
            $sheet->setCellValue('A' . $row, $item['date']);
            $sheet->setCellValue('B' . $row, $item['project']);
            $sheet->setCellValue('C' . $row, $item['permanent']);
            $sheet->setCellValue('D' . $row, $item['contract']);
            $sheet->setCellValue('E' . $row, $item['daily']);
            $sheet->setCellValue('F' . $row, $total);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('C2:F' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildPeriodFullReportSheet($sheet, $data, $periodType)
    {
        $row = 1;

        // Title
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', "TNT HR Report – {$periodType} Full Report");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Generated
        $sheet->setCellValue('A' . $row, 'Generated');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $row++;

        // Reporting Basis
        $sheet->setCellValue('A' . $row, 'Reporting Basis');
        $sheet->setCellValue('B' . $row, 'Average daily headcount for the period, rounded to the nearest whole employee');
        $row++;

        // Report Parameters
        $sheet->setCellValue('A' . $row, 'Report Parameters');
        $sheet->setCellValue('B' . $row, '');
        $row += 2;

        // Management Summary by Project
        $sheet->setCellValue('A' . $row, 'MANAGEMENT SUMMARY BY PROJECT');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
        ]);
        $row++;

        // Headers
        $headers = ['Period', 'Project', 'Permanent', 'Contract', 'Daily Labor', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Data rows
        foreach ($data as $item) {
            $total = $item['permanent'] + $item['contract'] + $item['daily'];
            $periodKey = $periodType === 'Weekly' ? 'week' : 'month';
            $sheet->setCellValue('A' . $row, $item[$periodKey]);
            $sheet->setCellValue('B' . $row, '');
            $sheet->setCellValue('C' . $row, $item['permanent']);
            $sheet->setCellValue('D' . $row, $item['contract']);
            $sheet->setCellValue('E' . $row, $item['daily']);
            $sheet->setCellValue('F' . $row, $total);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('C2:F' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildManagementSummarySheet($sheet, $data, $periodLabel)
    {
        $row = 1;

        // Title
        $title = str_replace('Period', 'Management Summary', $periodLabel);
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', "TNT HR Report – {$title}");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Generated
        $sheet->setCellValue('A' . $row, 'Generated');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $row++;

        // Report Parameters
        $sheet->setCellValue('A' . $row, 'Report Parameters');
        $sheet->setCellValue('B' . $row, '');
        $row += 2;

        // Headers
        $headers = ['Period', 'Project', 'Permanent', 'Contract', 'Daily', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Data rows
        foreach ($data as $item) {
            $total = $item['permanent'] + $item['contract'] + $item['daily'];
            $periodKey = isset($item['week']) ? 'week' : (isset($item['month']) ? 'month' : '');
            if ($periodKey) {
                $sheet->setCellValue('A' . $row, $item[$periodKey]);
            } else {
                $sheet->setCellValue('A' . $row, $item['report_date'] ?? '');
            }
            $sheet->setCellValue('B' . $row, $item['project'] ?? '');
            $sheet->setCellValue('C' . $row, $item['permanent']);
            $sheet->setCellValue('D' . $row, $item['contract']);
            $sheet->setCellValue('E' . $row, $item['daily']);
            $sheet->setCellValue('F' . $row, $total);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('C2:F' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildJobTitleDetailsSheet($sheet, $data, $periodLabel)
    {
        $row = 1;

        // Title
        $title = str_replace('Period', 'Job Title Details', $periodLabel);
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', "TNT HR Report – {$title}");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Generated
        $sheet->setCellValue('A' . $row, 'Generated');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $row++;

        // Report Parameters
        $sheet->setCellValue('A' . $row, 'Report Parameters');
        $sheet->setCellValue('B' . $row, '');
        $row += 2;

        // Headers
        $headers = ['Period', 'Project', 'Department', 'Code', 'Job Title', 'Employment Type', 'Headcount'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Data rows
        foreach ($data as $item) {
            $periodKey = isset($item['week']) ? 'week' : (isset($item['month']) ? 'month' : '');
            if ($periodKey) {
                $sheet->setCellValue('A' . $row, $item[$periodKey]);
            } else {
                $sheet->setCellValue('A' . $row, $item['report_date'] ?? '');
            }
            $sheet->setCellValue('B' . $row, $item['project'] ?? '');
            $sheet->setCellValue('C' . $row, $item['department'] ?? '');
            $sheet->setCellValue('D' . $row, $item['code'] ?? '');
            $sheet->setCellValue('E' . $row, $item['job_title'] ?? '');
            $sheet->setCellValue('F' . $row, $item['employment_type'] ?? '');
            $sheet->setCellValue('G' . $row, $item['headcount'] ?? 0);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('G2:G' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildProjectSummarySheet($sheet, $data)
    {
        $row = 1;

        // Title
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'TNT HR Report – Project Summary');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Headers
        $headers = ['Project', 'Permanent', 'Contract', 'Daily', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Data rows
        $grandTotal = ['permanent' => 0, 'contract' => 0, 'daily' => 0];
        foreach ($data as $rowData) {
            $sheet->setCellValue('A' . $row, $rowData->project);
            $sheet->setCellValue('B' . $row, $rowData->permanent);
            $sheet->setCellValue('C' . $row, $rowData->contract);
            $sheet->setCellValue('D' . $row, $rowData->daily);
            $sheet->setCellValue('E' . $row, $rowData->total);
            
            $grandTotal['permanent'] += $rowData->permanent;
            $grandTotal['contract'] += $rowData->contract;
            $grandTotal['daily'] += $rowData->daily;
            $row++;
        }

        // Grand Total
        $grandTotal['total'] = $grandTotal['permanent'] + $grandTotal['contract'] + $grandTotal['daily'];
        $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
        $sheet->setCellValue('B' . $row, $grandTotal['permanent']);
        $sheet->setCellValue('C' . $row, $grandTotal['contract']);
        $sheet->setCellValue('D' . $row, $grandTotal['daily']);
        $sheet->setCellValue('E' . $row, $grandTotal['total']);
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('B2:E' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildDepartmentSummarySheet($sheet, $data)
    {
        $row = 1;

        // Title
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'TNT HR Report – Department Summary');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Headers
        $headers = ['Department', 'Employment Type', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        // Data rows
        foreach ($data as $rowData) {
            $sheet->setCellValue('A' . $row, $rowData->department);
            $sheet->setCellValue('B' . $row, $rowData->employment_type);
            $sheet->setCellValue('C' . $row, $rowData->total);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply number formatting
        $sheet->getStyle('C2:C' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    private function buildReportInfoSheet($sheet, $dateFrom, $dateTo, $projectId, $employmentType, $scope, $data)
    {
        $row = 1;

        // Title
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'TNT HR Report – Information');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Report Info
        $info = [
            ['Generated By:', Auth::user()->username ?? 'System'],
            ['Generated On:', now()->format('Y-m-d H:i:s')],
            ['Date Range:', "{$dateFrom} to {$dateTo}"],
            ['Project:', $projectId > 0 ? (Project::find($projectId)->name ?? 'Selected Project') : 'All Projects'],
            ['Employment Type:', $employmentType],
            ['Report Scope:', ucfirst($scope)],
            [''],
            ['Report Statistics:'],
            ['Total Sheets:', count($data)],
            ['Total Records:', isset($data['daily_full']) ? $data['daily_full']->count() : 0],
            [''],
            ['System Information:'],
            ['Application:', 'TNT HR Manpower Reporting'],
            ['Version:', '1.0'],
            ['Server:', $_SERVER['SERVER_NAME'] ?? 'Local'],
        ];

        foreach ($info as $infoRow) {
            if (count($infoRow) == 2) {
                $sheet->setCellValue('A' . $row, $infoRow[0]);
                $sheet->setCellValue('B' . $row, $infoRow[1]);
                $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            } else {
                $sheet->setCellValue('A' . $row, $infoRow[0]);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                ]);
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}