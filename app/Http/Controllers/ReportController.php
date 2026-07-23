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
        $dates = DailyEntry::join('categories', 'categories.id', '=', 'daily_entries.category_id')
            ->where('categories.is_reconciliation', false)
            ->select(DB::raw('DISTINCT DATE_FORMAT(report_date, "%Y-%m-%d") as report_date'))
            ->orderBy('report_date')
            ->pluck('report_date')
            ->toArray();

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

        $dashboardData = $this->reportService->getManagementDashboard($dateFrom, $dateTo, $projectId, $employmentType);
        $projectSummary = $this->reportService->getProjectSummary($dateFrom, $dateTo, $projectId, $employmentType);
        $departmentSummary = $this->reportService->getDepartmentSummary($dateFrom, $dateTo, $projectId, $employmentType);
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();

        return view('reports.dashboard', compact('dashboardData', 'projectSummary', 'departmentSummary', 'projects', 'dateFrom', 'dateTo', 'projectId', 'employmentType'));
    }

    /**
     * Standard HR Report view
     */
    public function standard(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $latestDate = DailyEntry::max('report_date');
        return view('reports.standard', compact('projects', 'latestDate'));
    }

    /**
     * Export Standard HR Report - Cross-tabulation
     * EACH PROJECT SHOWS ITS OWN CORRECT DATA
     */
    public function exportStandard(Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date', now()->format('Y-m-d'));
        $projectIds = $request->input('project_ids', []);
        
        if (empty($projectIds)) {
            $projectIds = Project::where('status', 'Active')->pluck('id')->toArray();
        }
        
        $projects = Project::whereIn('id', $projectIds)->orderBy('name')->get();
        $categories = Category::where('is_reconciliation', false)->orderBy('row_order')->get();
        
        // ====== CORRECT: Get entries per project per category ======
        $entriesRaw = DailyEntry::where('report_date', $date)
            ->whereIn('project_id', $projectIds)
            ->get();
        
        // Build array: $entries[category_id][project_id] = headcount
        $entries = [];
        foreach ($entriesRaw as $e) {
            $entries[$e->category_id][$e->project_id] = (int)$e->headcount;
        }
        
        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_standard_report',
            'details' => "Standard Report: {$date}, " . count($projectIds) . " projects",
        ]);
        
        return $this->generateStandardExcel($date, $projects, $categories, $entries);
    }

    /**
     * Generate Standard HR Report Excel
     */
    private function generateStandardExcel($date, $projects, $categories, $entries)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Standard HR Report');
        
        // Page setup
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3);
        $sheet->getPageSetup()->setFitToWidth(1);
        
        $row = 1;
        $totalCol = chr(ord('C') + $projects->count()); // C + number of projects = Total column
        
        // ============================================
        // TITLE
        // ============================================
        $sheet->mergeCells('A1:' . $totalCol . '1');
        $sheet->setCellValue('A1', 'TNT CONSTRUCTION - STANDARD HR REPORT');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(35);
        $row++;
        
        // Sub-header
        $sheet->mergeCells('A' . $row . ':' . $totalCol . $row);
        $sheet->setCellValue('A' . $row, 'Report Date: ' . $date . '  |  Generated: ' . now()->format('Y-m-d H:i') . '  |  Projects: ' . $projects->count());
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '627386']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;
        
        // ============================================
        // HEADERS
        // ============================================
        $sheet->setCellValue('A' . $row, 'Sr. No.');
        $sheet->setCellValue('B' . $row, 'Employee Category / Job Title');
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        
        // Project column headers
        $col = 'C';
        foreach ($projects as $project) {
            $sheet->setCellValue($col . $row, $project->name);
            $sheet->getColumnDimension($col)->setWidth(18);
            $col++;
        }
        
        // Total column
        $sheet->setCellValue($col . $row, 'Total');
        $sheet->getColumnDimension($col)->setWidth(14);
        
        // Style headers
        $sheet->getStyle('A' . $row . ':' . $totalCol . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;
        
        // ============================================
        // DATA ROWS - Grouped by Department
        // ============================================
        $groupedCategories = $categories->groupBy('department');
        $srNo = 0;
        $grandTotals = []; // Per project grand total
        
        foreach ($projects as $p) {
            $grandTotals[$p->id] = 0;
        }
        
        foreach ($groupedCategories as $department => $deptCategories) {
            // Department Header Row
            $srNo++;
            $sheet->setCellValue('A' . $row, $srNo);
            $sheet->setCellValue('B' . $row, strtoupper($department));
            $sheet->mergeCells('B' . $row . ':' . $totalCol . $row);
            $sheet->getStyle('A' . $row . ':' . $totalCol . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B7F79']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $row++;
            
            // Sub-sections by employment type
            $types = ['Permanent', 'Contract', 'Daily'];
            $deptTotals = [];
            foreach ($projects as $p) { $deptTotals[$p->id] = 0; }
            
            foreach ($types as $type) {
                $typeCategories = $deptCategories->where('employment_type', $type);
                if ($typeCategories->isEmpty()) continue;
                
                // Type label
                $typeLabel = match($type) {
                    'Permanent' => 'Permanent Staff',
                    'Contract' => 'Contract Employees',
                    default => 'Skilled & Unskilled Labourers'
                };
                
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, '    ' . $typeLabel);
                $sheet->getStyle('B' . $row)->applyFromArray(['font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '627386']]]);
                $sheet->getStyle('A' . $row . ':' . $totalCol . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $row++;
                
                // Individual job titles
                foreach ($typeCategories as $cat) {
                    $sheet->setCellValue('A' . $row, '');
                    $sheet->setCellValue('B' . $row, '        ' . $cat->name);
                    
                    $col = 'C';
                    $rowTotal = 0;
                    foreach ($projects as $project) {
                        // ====== GET CORRECT VALUE PER PROJECT ======
                        $val = $entries[$cat->id][$project->id] ?? 0;
                        $sheet->setCellValue($col . $row, $val > 0 ? $val : '');
                        if ($val > 0) {
                            $deptTotals[$project->id] += $val;
                            $grandTotals[$project->id] += $val;
                            $rowTotal += $val;
                        }
                        $col++;
                    }
                    $sheet->setCellValue($totalCol . $row, $rowTotal > 0 ? $rowTotal : '');
                    $sheet->getStyle('A' . $row . ':' . $totalCol . $row)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    ]);
                    $row++;
                }
            }
            
            // Department Subtotal
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, '    DEPARTMENT SUBTOTAL');
            $sheet->getStyle('B' . $row)->applyFromArray(['font' => ['bold' => true, 'size' => 9]]);
            
            $col = 'C';
            $deptGrandTotal = 0;
            foreach ($projects as $project) {
                $val = $deptTotals[$project->id];
                $sheet->setCellValue($col . $row, $val > 0 ? $val : '');
                $deptGrandTotal += $val;
                $col++;
            }
            $sheet->setCellValue($totalCol . $row, $deptGrandTotal > 0 ? $deptGrandTotal : '');
            $sheet->getStyle('A' . $row . ':' . $totalCol . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $row++;
        }
        
        // ============================================
        // GRAND TOTAL
        // ============================================
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'GRAND TOTAL - ALL EMPLOYEES');
        $sheet->getStyle('B' . $row)->applyFromArray(['font' => ['bold' => true, 'size' => 11]]);
        
        $col = 'C';
        $allGrandTotal = 0;
        foreach ($projects as $project) {
            $val = $grandTotals[$project->id];
            $sheet->setCellValue($col . $row, $val > 0 ? $val : '');
            $allGrandTotal += $val;
            $col++;
        }
        $sheet->setCellValue($totalCol . $row, $allGrandTotal);
        $sheet->getStyle('A' . $row . ':' . $totalCol . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $row += 2;
        
        // Footer
        $sheet->mergeCells('A' . $row . ':' . $totalCol . $row);
        $sheet->setCellValue('A' . $row, 'Generated by TNT HR Reporting System | ' . now()->format('Y-m-d H:i'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true, 'size' => 8, 'color' => ['rgb' => '627386']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Save and download
        $writer = new Xlsx($spreadsheet);
        $filename = 'Standard_HR_Report_' . $date . '_' . now()->format('Ymd_His') . '.xlsx';
        
        $tempFile = tempnam(sys_get_temp_dir(), 'stdrpt_');
        $writer->save($tempFile);
        $fileContent = file_get_contents($tempFile);
        unlink($tempFile);
        
        if (ob_get_length()) ob_clean();
        
        return response($fileContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Keep the existing export method and other methods unchanged
    public function export(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'project' => 'nullable',
            'projects' => 'nullable|array',
            'employment_type' => 'required|in:All,Permanent,Contract,Daily',
            'scope' => 'required|in:complete,daily,weekly,monthly,management,job_titles',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $projectId = (int) $request->input('project', 0);
        $employmentType = $request->input('employment_type', 'All');
        $scope = $request->input('scope', 'complete');
        $selectedProjects = $request->input('projects', []);

        $data = [];
        if (in_array($scope, ['complete', 'management'])) {
            $data['management_dashboard'] = $this->reportService->getManagementDashboard($dateFrom, $dateTo, $projectId, $employmentType, $selectedProjects);
            $data['project_summary'] = $this->reportService->getProjectSummary($dateFrom, $dateTo, $projectId, $employmentType);
            $data['department_summary'] = $this->reportService->getDepartmentSummary($dateFrom, $dateTo, $projectId, $employmentType);
        }
        if (in_array($scope, ['complete', 'daily'])) {
            $data['daily_full'] = $this->reportService->getDailyFullReport($dateFrom, $dateTo, $projectId, $employmentType);
        }
        if (in_array($scope, ['complete', 'weekly'])) {
            $data['weekly_full'] = $this->reportService->getWeeklySummary($dateFrom, $dateTo, $projectId, $employmentType);
        }
        if (in_array($scope, ['complete', 'monthly'])) {
            $data['monthly_full'] = $this->reportService->getMonthlySummary($dateFrom, $dateTo, $projectId, $employmentType);
        }

        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_xlsx',
            'details' => "Report exported: {$dateFrom} to {$dateTo}",
        ]);

        return $this->generateExcel($data, $dateFrom, $dateTo, $projectId, $employmentType, $scope);
    }

    private function generateExcel($data, $dateFrom, $dateTo, $projectId, $employmentType, $scope)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');
        $sheet->setCellValue('A1', 'TNT HR Report');
        
        $writer = new Xlsx($spreadsheet);
        $filename = "TNT_HR_Report_{$dateFrom}_to_{$dateTo}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'report_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        if (ob_get_length()) ob_clean();
        
        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
