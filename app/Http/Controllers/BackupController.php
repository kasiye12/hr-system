<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        // Get database size
        $dbSize = DB::select("
            SELECT SUM(data_length + index_length) / 1024 / 1024 as size 
            FROM information_schema.tables 
            WHERE table_schema = ?", [env('DB_DATABASE')]
        );

        $dbSize = $dbSize[0]->size ?? 0;

        return view('backup.index', compact('dbSize'));
    }

    public function download()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        // Create backup directory
        if (!is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Dump database
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            env('DB_HOST'),
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_DATABASE'),
            $path
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup creation failed.');
        }

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'download_backup',
            'details' => $filename,
        ]);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function local()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        if (!is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            env('DB_HOST'),
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_DATABASE'),
            $path
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup creation failed.');
        }

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'local_backup',
            'details' => $filename,
        ]);

        return redirect()->route('backup.index')
            ->with('success', "Backup created successfully: {$filename}");
    }
}