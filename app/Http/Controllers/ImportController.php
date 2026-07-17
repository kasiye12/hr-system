<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Project;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportController extends Controller
{
    /**
     * Display the import page
     */
    public function index()
    {
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();
        $categories = Category::where('is_reconciliation', false)
            ->orderBy('row_order')
            ->get();

        return view('import.index', compact('projects', 'categories'));
    }

    /**
 * Handle the import
 */
public function import(Request $request)
{
    $user = Auth::user();

    if (!$user->canEdit()) {
        return redirect()->route('import.index')
            ->with('error', 'You do not have permission to import data.');
    }

    // Validate the request
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        'import_type' => 'required|in:headcount,structure',
    ]);

    $importType = $request->input('import_type');

    if ($importType === 'headcount') {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'date_column' => 'required|string',
        ]);
    }

    $file = $request->file('file');

    try {
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Check if file has data
        if (empty($rows) || count($rows) < 2) {
            return redirect()->route('import.index')
                ->with('error', 'The file is empty or does not contain enough rows.');
        }

        // Get headers and data rows
        $headers = array_map('trim', $rows[0]);
        $dataRows = array_slice($rows, 1);

        // Remove empty rows
        $dataRows = array_filter($dataRows, function($row) {
            return !empty(array_filter($row, function($cell) {
                return !empty(trim($cell));
            }));
        });

        if (empty($dataRows)) {
            return redirect()->route('import.index')
                ->with('error', 'No data rows found in the file.');
        }

        // Process based on import type
        if ($importType === 'headcount') {
            $projectId = (int) $request->input('project_id');
            $dateColumn = trim($request->input('date_column'));
            return $this->importHeadcount($dataRows, $headers, $projectId, $dateColumn, $user);
        } else {
            return $this->importStructure($dataRows, $headers, $user);
        }

    } catch (\Exception $e) {
        return redirect()->route('import.index')
            ->with('error', 'Failed to import file: ' . $e->getMessage() . ' at line ' . $e->getLine());
    }
}

    private function importHeadcount($dataRows, $headers, $projectId, $dateColumn, $user)
{
    // Find date column index
    $dateIndex = array_search($dateColumn, $headers);
    if ($dateIndex === false) {
        return redirect()->route('import.index')
            ->with('error', "Date column '{$dateColumn}' not found in the file. Available columns: " . implode(', ', $headers));
    }

    // Get all categories for mapping
    $categories = Category::where('is_reconciliation', false)->get();

    if ($categories->isEmpty()) {
        return redirect()->route('import.index')
            ->with('error', 'No job titles found in the system. Please add job titles first.');
    }

    // Create mapping for category columns - EXACT MATCH on code
    $categoryMap = [];
    foreach ($categories as $category) {
        $categoryMap[strtoupper(trim($category->code))] = $category->id;
        // Also map by name for flexibility
        $categoryMap[strtoupper(trim($category->name))] = $category->id;
    }

    // Find which columns match categories
    $categoryColumns = [];
    $matchedHeaders = [];
    $unmatchedHeaders = [];
    
    foreach ($headers as $index => $header) {
        if ($index === $dateIndex) continue;
        $headerClean = strtoupper(trim($header));
        
        // Try to match - also handle headers with extra text like "CODE (Job Title)"
        $headerParts = explode('(', $headerClean);
        $headerClean = trim($headerParts[0]);
        
        if (isset($categoryMap[$headerClean])) {
            $categoryColumns[$index] = $categoryMap[$headerClean];
            $matchedHeaders[] = $header;
        } else {
            $unmatchedHeaders[] = $header;
        }
    }

    if (empty($categoryColumns)) {
        return redirect()->route('import.index')
            ->with('error', 'No matching category columns found. 
            Please check column headers match job title codes. 
            Available codes: ' . implode(', ', $categories->pluck('code')->toArray()) . '
            Your headers: ' . implode(', ', $headers));
    }

    $imported = 0;
    $updated = 0;
    $errors = [];
    $skipped = 0;

    DB::beginTransaction();

    try {
        foreach ($dataRows as $rowIndex => $row) {
            // Get date value
            $date = trim($row[$dateIndex] ?? '');
            if (empty($date)) {
                $skipped++;
                continue;
            }

            // Parse date
            try {
                if (is_numeric($date)) {
                    $dateObj = Date::excelToDateTimeObject((float)$date);
                    $formattedDate = $dateObj->format('Y-m-d');
                } else {
                    $formattedDate = date('Y-m-d', strtotime($date));
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($rowIndex + 2) . ": Invalid date format '{$date}'";
                continue;
            }

            if (!$formattedDate || $formattedDate === '1970-01-01') {
                $errors[] = "Row " . ($rowIndex + 2) . ": Could not parse date '{$date}'";
                continue;
            }

            // Process each category column
            foreach ($categoryColumns as $colIndex => $categoryId) {
                $headcount = (int) trim($row[$colIndex] ?? 0);
                if ($headcount < 0) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Headcount cannot be negative for column '{$headers[$colIndex]}'";
                    continue;
                }

                // Check if entry exists
                $existing = DailyEntry::where([
                    'report_date' => $formattedDate,
                    'project_id' => $projectId,
                    'category_id' => $categoryId,
                ])->first();

                if ($headcount == 0) {
                    if ($existing) {
                        $existing->delete();
                        $updated++;
                    }
                } else {
                    if ($existing) {
                        $existing->update(['headcount' => $headcount]);
                        $updated++;
                    } else {
                        DailyEntry::create([
                            'report_date' => $formattedDate,
                            'project_id' => $projectId,
                            'category_id' => $categoryId,
                            'headcount' => $headcount,
                        ]);
                        $imported++;
                    }
                }
            }
        }

        AuditLog::create([
            'username' => $user->username,
            'action' => 'import_headcount',
            'details' => "Imported {$imported} new records, updated {$updated} records for project ID: {$projectId}",
        ]);

        DB::commit();

        $message = "✅ Successfully imported {$imported} new records and updated {$updated} existing records.";
        if ($skipped > 0) $message .= " Skipped {$skipped} empty rows.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " and " . (count($errors) - 5) . " more errors.";
        }

        return redirect()->route('import.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('import.index')
            ->with('error', 'Failed to import data: ' . $e->getMessage());
    }
}

    /**
 * Import job title structure
 */
private function importStructure($dataRows, $headers, $user)
{
    // Clean headers - remove empty and trim
    $cleanHeaders = array_map('trim', $headers);
    
    // Try to detect columns - more flexible matching
    $departmentIndex = null;
    $codeIndex = null;
    $jobTitleIndex = null;
    $employmentTypeIndex = null;

    foreach ($cleanHeaders as $index => $header) {
        $headerLower = strtolower(trim($header));
        
        // Department detection
        if (strpos($headerLower, 'department') !== false || 
            strpos($headerLower, 'dept') !== false || 
            strpos($headerLower, 'depart') !== false ||
            $headerLower === 'department') {
            $departmentIndex = $index;
        } 
        // Code detection
        elseif (strpos($headerLower, 'code') !== false || 
                strpos($headerLower, 'id') !== false || 
                strpos($headerLower, 'code') !== false ||
                $headerLower === 'code') {
            $codeIndex = $index;
        } 
        // Job Title detection
        elseif (strpos($headerLower, 'job') !== false || 
                strpos($headerLower, 'title') !== false || 
                strpos($headerLower, 'position') !== false ||
                strpos($headerLower, 'job title') !== false ||
                $headerLower === 'jobtitle' ||
                $headerLower === 'job_title') {
            $jobTitleIndex = $index;
        } 
        // Employment Type detection
        elseif (strpos($headerLower, 'employment') !== false || 
                strpos($headerLower, 'type') !== false ||
                strpos($headerLower, 'emp type') !== false ||
                $headerLower === 'employmenttype' ||
                $headerLower === 'employment_type') {
            $employmentTypeIndex = $index;
        }
    }

    // If we couldn't detect columns, show error with available headers
    if ($departmentIndex === null || $codeIndex === null || $jobTitleIndex === null) {
        $availableHeaders = implode(', ', $cleanHeaders);
        return redirect()->route('import.index')
            ->with('error', "Could not detect required columns. Found headers: {$availableHeaders}. Required: Department, Code, Job Title, Employment Type");
    }

    $imported = 0;
    $errors = [];
    $skipped = 0;
    $validTypes = ['Permanent', 'Contract', 'Daily'];

    DB::beginTransaction();

    try {
        foreach ($dataRows as $rowIndex => $row) {
            // Get values from detected columns
            $department = trim($row[$departmentIndex] ?? '');
            $code = trim($row[$codeIndex] ?? '');
            $jobTitle = trim($row[$jobTitleIndex] ?? '');
            $employmentType = 'Permanent'; // Default
            
            // Get employment type if column exists
            if ($employmentTypeIndex !== null) {
                $employmentType = trim($row[$employmentTypeIndex] ?? 'Permanent');
            }

            // Skip empty rows
            if (empty($department) && empty($code) && empty($jobTitle)) {
                $skipped++;
                continue;
            }

            // Validate required fields
            if (empty($department)) {
                $errors[] = "Row " . ($rowIndex + 2) . ": Department is required";
                continue;
            }
            
            if (empty($code)) {
                $errors[] = "Row " . ($rowIndex + 2) . ": Code is required";
                continue;
            }
            
            if (empty($jobTitle)) {
                $errors[] = "Row " . ($rowIndex + 2) . ": Job Title is required";
                continue;
            }

            // Validate and format employment type
            if (!empty($employmentType)) {
                // Try to match employment type
                $empTypeLower = strtolower(trim($employmentType));
                if (strpos($empTypeLower, 'permanent') !== false || strpos($empTypeLower, 'perm') !== false) {
                    $employmentType = 'Permanent';
                } elseif (strpos($empTypeLower, 'contract') !== false || strpos($empTypeLower, 'cont') !== false) {
                    $employmentType = 'Contract';
                } elseif (strpos($empTypeLower, 'daily') !== false || strpos($empTypeLower, 'day') !== false) {
                    $employmentType = 'Daily';
                } else {
                    // Default to Permanent if not recognized
                    $employmentType = 'Permanent';
                }
            } else {
                $employmentType = 'Permanent';
            }

            // Check if category already exists
            $existing = Category::where('code', $code)->first();
            if ($existing) {
                $errors[] = "Row " . ($rowIndex + 2) . ": Code '{$code}' already exists. Skipping.";
                continue;
            }

            // Get max row order
            $maxOrder = Category::max('row_order') ?? 0;

            // Create category
            Category::create([
                'code' => $code,
                'name' => $jobTitle,
                'department' => $department,
                'employment_type' => $employmentType,
                'row_order' => $maxOrder + 1,
                'is_reconciliation' => false,
            ]);

            $imported++;
        }

        // Log the import
        AuditLog::create([
            'username' => $user->username,
            'action' => 'import_structure',
            'details' => "Imported {$imported} job titles",
        ]);

        DB::commit();

        $message = "✅ Successfully imported {$imported} job titles.";
        if ($skipped > 0) $message .= " Skipped {$skipped} empty rows.";
        if (!empty($errors)) {
            $message .= "<br><br>⚠️ Errors:<br>" . implode('<br>', array_slice($errors, 0, 10));
            if (count($errors) > 10) $message .= "<br> and " . (count($errors) - 10) . " more errors.";
        }

        return redirect()->route('import.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('import.index')
            ->with('error', 'Failed to import structure: ' . $e->getMessage());
    }
}

    /**
     * Download template
     */
    public function downloadTemplate(Request $request)
    {
        $type = $request->input('type', 'headcount');
        
        if ($type === 'headcount') {
            return $this->downloadHeadcountTemplate();
        } else {
            return $this->downloadStructureTemplate();
        }
    }

    private function downloadHeadcountTemplate()
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Headcount Data');

    // Get existing categories for template - FIXED: Get actual job titles
    $categories = Category::where('is_reconciliation', false)
        ->orderBy('row_order')
        ->get();

    // If no categories exist, use default headers
    if ($categories->isEmpty()) {
        $headers = ['Date', 'Job Title 1', 'Job Title 2', 'Job Title 3'];
    } else {
        $headers = ['Date'];
        foreach ($categories as $category) {
            $headers[] = $category->code . ' (' . $category->name . ')';
        }
    }

    // Set headers with styling
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                       'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $col++;
    }

    // Sample data - only if we have categories
    if (!$categories->isEmpty()) {
        $sampleData = [];
        $sampleData[] = ['2026-07-15'];
        $sampleData[] = ['2026-07-16'];
        $sampleData[] = ['2026-07-17'];
        
        // Add sample values for each category
        $values = [5, 3, 2];
        foreach ($sampleData as $index => &$row) {
            foreach ($categories as $catIndex => $category) {
                $row[] = $values[$catIndex % count($values)] ?? 0;
            }
        }
        
        $row = 2;
        foreach ($sampleData as $dataRow) {
            $col = 'A';
            foreach ($dataRow as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
    }

    // Instructions
    $row += 2;
    $sheet->setCellValue('A' . $row, '📌 INSTRUCTIONS:');
    $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);
    $row++;
    
    $sheet->setCellValue('A' . $row, '1. The "Date" column must be in YYYY-MM-DD format');
    $row++;
    $sheet->setCellValue('A' . $row, '2. Column headers must match the Job Title Codes shown above');
    $row++;
    $sheet->setCellValue('A' . $row, '3. Headcount must be whole numbers (0 or positive)');
    $row++;
    $sheet->setCellValue('A' . $row, '4. Delete the sample rows before importing your data');
    $row++;
    $sheet->setCellValue('A' . $row, '5. Do not change the header row');
    
    // Style instructions
    $sheet->getStyle('A' . ($row - 5) . ':A' . $row)->applyFromArray([
        'font' => ['color' => ['rgb' => 'A61B1B']],
    ]);

    // Auto-size columns
    foreach (range('A', $col) as $colLetter) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'HR_Headcount_Import_Template.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer->save('php://output');
    exit;
}

    private function downloadStructureTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Job Titles');

        $headers = ['Department', 'Code', 'Job Title', 'Employment Type'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                           'startColor' => ['rgb' => 'DCE5EE']],
            ]);
            $col++;
        }

        $sampleData = [
            ['Engineering', 'ENG-001', 'Senior Engineer', 'Permanent'],
            ['Engineering', 'ENG-002', 'Junior Engineer', 'Contract'],
            ['HR', 'HR-001', 'HR Manager', 'Permanent'],
            ['Finance', 'FIN-001', 'Accountant', 'Permanent'],
            ['Operations', 'OPS-001', 'Technician', 'Daily'],
        ];

        $row = 2;
        foreach ($sampleData as $dataRow) {
            $col = 'A';
            foreach ($dataRow as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        $sheet->setCellValue('A' . ($row + 2), 'Note: Employment Type must be: Permanent, Contract, or Daily');
        $sheet->getStyle('A' . ($row + 2))->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => 'FF0000']],
        ]);

        foreach (range('A', $col) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'HR_Structure_Import_Template.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }
}