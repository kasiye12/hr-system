<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    /**
     * Display the backup page
     */
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        // Get database size
        try {
            $dbSize = DB::select("
                SELECT SUM(data_length + index_length) / 1024 / 1024 as size 
                FROM information_schema.tables 
                WHERE table_schema = ?", [env('DB_DATABASE')]
            );
            $dbSize = $dbSize[0]->size ?? 0;
        } catch (\Exception $e) {
            $dbSize = 0;
        }

        // Get backup files
        $backupPath = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $file) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_formatted' => $this->formatSize($file->getSize()),
                    'created_at' => $file->getMTime(),
                    'created_at_formatted' => date('Y-m-d H:i:s', $file->getMTime()),
                    'path' => $file->getPathname(),
                ];
            }
            // Sort by created date descending
            usort($backups, function($a, $b) {
                return $b['created_at'] - $a['created_at'];
            });
        }

        // Get backup statistics
        $stats = [
            'total_backups' => count($backups),
            'total_size' => array_sum(array_column($backups, 'size')),
            'total_size_formatted' => $this->formatSize(array_sum(array_column($backups, 'size'))),
            'last_backup' => $backups[0]['created_at_formatted'] ?? 'No backups found',
            'db_size_formatted' => $this->formatSize($dbSize * 1048576),
        ];

        return view('backup.index', compact('dbSize', 'backups', 'stats'));
    }

    /**
     * Download a specific backup file or create a new one
     */
    public function download($file = null)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        // If no file provided, create a new backup and download it
        if (!$file) {
            return $this->createAndDownloadBackup();
        }

        $path = storage_path('app/backups/' . $file);

        if (!File::exists($path)) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup file not found.');
        }

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'download_backup',
            'details' => "Downloaded backup: {$file}",
        ]);

        return response()->download($path, $file);
    }

    /**
     * Create and download a new backup
     */
    private function createAndDownloadBackup()
    {
        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        try {
            $this->createBackup($path);

            AuditLog::create([
                'username' => Auth::user()->username,
                'action' => 'download_backup',
                'details' => "Created and downloaded: {$filename}",
            ]);

            return response()->download($path, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Create a local backup
     */
    public function local()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        try {
            $this->createBackup($path);

            AuditLog::create([
                'username' => Auth::user()->username,
                'action' => 'local_backup',
                'details' => "Created local backup: {$filename}",
            ]);

            return redirect()->route('backup.index')
                ->with('success', "✅ Backup created successfully: {$filename}");

        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Create a full backup package (ZIP with SQL and files)
     */
    public function full()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        try {
            $stamp = now()->format('Y-m-d_H-i-s');
            $backupPath = storage_path('app/backups');
            
            // Create backup directory
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            // Create SQL backup
            $sqlFile = $backupPath . '/db_backup_' . $stamp . '.sql';
            $this->createBackup($sqlFile);

            // Create ZIP package
            $zipFile = $backupPath . '/tnt_hr_full_backup_' . $stamp . '.zip';
            $zip = new \ZipArchive();
            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Failed to create ZIP file.');
            }

            // Add SQL file
            if (File::exists($sqlFile)) {
                $zip->addFile($sqlFile, 'database_backup.sql');
            }

            // Add README
            $readme = "TNT HR FULL BACKUP PACKAGE\n";
            $readme .= "==============================\n\n";
            $readme .= "Created: " . now()->format('Y-m-d H:i:s') . "\n";
            $readme .= "Database: " . env('DB_DATABASE') . "\n\n";
            $readme .= "RESTORE INSTRUCTIONS:\n";
            $readme .= "1. Extract this ZIP file\n";
            $readme .= "2. Import database_backup.sql using:\n";
            $readme .= "   mysql -u " . env('DB_USERNAME') . " -p " . env('DB_DATABASE') . " < database_backup.sql\n";
            $readme .= "3. Or use phpMyAdmin to import the SQL file\n\n";
            $readme .= "Generated by TNT HR Reporting System";
            
            $zip->addFromString('README.txt', $readme);
            $zip->close();

            // Delete SQL file after adding to ZIP
            @unlink($sqlFile);

            AuditLog::create([
                'username' => Auth::user()->username,
                'action' => 'full_backup',
                'details' => "Created full backup package: " . basename($zipFile),
            ]);

            return response()->download($zipFile, basename($zipFile))->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Failed to create full backup: ' . $e->getMessage());
        }
    }

    /**
     * Delete a backup file
     */
    public function delete($file)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $path = storage_path('app/backups/' . $file);

        if (!File::exists($path)) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup file not found.');
        }

        try {
            File::delete($path);

            AuditLog::create([
                'username' => Auth::user()->username,
                'action' => 'delete_backup',
                'details' => "Deleted backup: {$file}",
            ]);

            return redirect()->route('backup.index')
                ->with('success', "🗑️ Backup '{$file}' deleted successfully.");

        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }

    /**
     * Create a database backup using MySQL dump
     */
    private function createBackup($path)
    {
        // Ensure backup directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Try using PHP method first (more reliable on shared hosting)
        $this->createBackupWithPHP($path);
        
        // Verify the backup was created
        if (!File::exists($path) || File::size($path) < 100) {
            throw new \Exception('Backup file is empty or was not created properly.');
        }
    }

    /**
     * Create backup using PHP (most reliable method)
     */
    private function createBackupWithPHP($path)
    {
        $content = "-- TNT HR Database Backup\n";
        $content .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "-- Database: " . env('DB_DATABASE') . "\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Get all tables - FIXED: Use proper method to get tables
        $tables = DB::select('SHOW TABLES');
        
        // Get the table names properly
        $tableNames = [];
        foreach ($tables as $table) {
            // Get the first property of the object (which is the table name)
            $tableArray = (array) $table;
            $tableNames[] = reset($tableArray);
        }

        foreach ($tableNames as $tableName) {
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $content .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            
            // Get the Create Table value
            $createTableArray = (array) $createTable[0];
            $createTableValue = $createTableArray['Create Table'] ?? $createTableArray[key($createTableArray)];
            $content .= $createTableValue . ";\n\n";

            // Get table data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows[0]);
                $columnsStr = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = [];
                    $rowArray = (array) $row;
                    foreach ($columns as $col) {
                        $val = $rowArray[$col] ?? null;
                        if ($val === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($val) . "'";
                        }
                    }
                    $content .= "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $content .= "\n";
            }
        }

        $content .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($path, $content);
    }

    /**
     * Format file size
     */
    private function formatSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}