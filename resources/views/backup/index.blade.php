@extends('layouts.app')

@section('title', 'Database Backup - TNT HR')

@section('content')
<style>
    .backup-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .backup-header h1 {
        margin: 0;
        font-size: 26px;
        color: #16324f;
    }
    .backup-header .subtitle {
        color: #627386;
        font-size: 14px;
        margin-top: 2px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #dce5ee;
        border-radius: 10px;
        padding: 16px 20px;
        text-align: center;
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .stat-card .number {
        font-size: 24px;
        font-weight: 700;
        color: #16324f;
    }
    .stat-card .label {
        font-size: 11px;
        color: #627386;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 600;
        margin-top: 2px;
    }
    .stat-card.highlight {
        background: #dfeeea;
        border-color: #1b7f79;
    }
    .stat-card.highlight .number {
        color: #0c5d58;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    .action-card {
        background: #f8fbfd;
        border: 1px solid #dce5ee;
        border-radius: 10px;
        padding: 16px 20px;
        text-align: center;
        transition: all 0.2s;
        text-decoration: none;
        color: #16324f;
    }
    .action-card:hover {
        border-color: #1b7f79;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        text-decoration: none;
        color: #16324f;
    }
    .action-card .action-icon {
        font-size: 32px;
        margin-bottom: 6px;
    }
    .action-card .action-name {
        font-weight: 700;
        font-size: 14px;
    }
    .action-card .action-desc {
        font-size: 12px;
        color: #627386;
        margin-top: 2px;
    }
    .action-card .action-badge {
        display: inline-block;
        padding: 1px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        margin-top: 6px;
    }
    .action-card .action-badge.new {
        background: #dcf5e7;
        color: #12643c;
    }

    .table-card {
        background: #fff;
        border: 1px solid #dce5ee;
        border-radius: 12px;
        padding: 20px 24px;
        box-shadow: 0 2px 8px rgba(21,49,78,0.05);
        margin-top: 20px;
    }
    .table-card .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .table-card .table-header h3 {
        margin: 0;
        font-size: 16px;
        color: #16324f;
        font-weight: 700;
    }
    .table-card .table-header .records-count {
        font-size: 12px;
        color: #627386;
        background: #f8fbfd;
        padding: 4px 12px;
        border-radius: 999px;
    }

    .table-wrap {
        overflow-x: auto;
    }
    .table-wrap table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .table-wrap th {
        background: #f8fbfd;
        color: #34495e;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 10px 14px;
        text-align: left;
        border-bottom: 2px solid #dce5ee;
        font-weight: 700;
    }
    .table-wrap td {
        padding: 10px 14px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    .table-wrap tr:hover td {
        background: #f8fbfd;
    }

    .btn-sm {
        padding: 4px 10px;
        font-size: 11px;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-sm:hover { filter: brightness(0.93); }
    .btn-sm.btn-download {
        background: #1b7f79;
        color: #fff;
    }
    .btn-sm.btn-delete {
        background: #ffeded;
        color: #a61b1b;
    }
    .btn-sm.btn-delete:hover {
        background: #ffdddd;
    }

    .empty-state {
        text-align: center;
        padding: 30px 20px;
        color: #627386;
    }
    .empty-state .icon {
        font-size: 40px;
        margin-bottom: 8px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        .action-grid {
            grid-template-columns: 1fr;
        }
        .backup-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .table-card {
            padding: 12px 16px;
        }
    }
</style>

<div class="backup-header">
    <div>
        <h1>💾 Database Backup</h1>
        <div class="subtitle">Create, manage, and download database backups</div>
    </div>
    <div>
        <span style="font-size:12px; color:#627386; background:#f8fbfd; padding:4px 12px; border-radius:999px;">
            🗄️ {{ count($backups) }} backups
        </span>
    </div>
</div>

@if(session('success'))
    <div class="alert success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert error">{{ session('error') }}</div>
@endif

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card highlight">
        <div class="number">{{ $stats['total_backups'] }}</div>
        <div class="label">Total Backups</div>
    </div>
    <div class="stat-card">
        <div class="number">{{ $stats['total_size_formatted'] }}</div>
        <div class="label">Total Storage Used</div>
    </div>
    <div class="stat-card">
        <div class="number">{{ $stats['db_size_formatted'] }}</div>
        <div class="label">Database Size</div>
    </div>
    <div class="stat-card">
        <div class="number" style="font-size:18px;">{{ $stats['last_backup'] }}</div>
        <div class="label">Last Backup</div>
    </div>
</div>

<!-- Actions -->
<div class="action-grid">
    <a href="{{ route('backup.download') }}" class="action-card">
        <div class="action-icon">📥</div>
        <div class="action-name">Download Backup</div>
        <div class="action-desc">Create and download a new backup</div>
        <span class="action-badge new">Latest</span>
    </a>
    <a href="{{ route('backup.local') }}" class="action-card">
        <div class="action-icon">💾</div>
        <div class="action-name">Create Local Backup</div>
        <div class="action-desc">Save backup to server storage</div>
    </a>
    <a href="{{ route('backup.full') }}" class="action-card">
        <div class="action-icon">📦</div>
        <div class="action-name">Full Backup Package</div>
        <div class="action-desc">ZIP with SQL + README</div>
        <span class="action-badge new">Complete</span>
    </a>
</div>

<!-- Backup List -->
<div class="table-card">
    <div class="table-header">
        <h3>📋 Backup Files</h3>
        <span class="records-count">{{ count($backups) }} files</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Created</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td>
                            <span style="font-weight:600; color:#16324f;">{{ $backup['name'] }}</span>
                        </td>
                        <td>{{ $backup['size_formatted'] }}</td>
                        <td>{{ $backup['created_at_formatted'] }}</td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                                <a href="{{ route('backup.download', ['file' => $backup['name']]) }}" class="btn-sm btn-download">
                                    ⬇️ Download
                                </a>
                                <form method="POST" action="{{ route('backup.delete', ['file' => $backup['name']]) }}" style="display:inline;" onsubmit="return confirm('Delete this backup?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-delete">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="icon">📭</div>
                                <div style="font-weight:600; font-size:16px;">No backups found</div>
                                <div style="font-size:13px;">Create your first backup using the buttons above.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Info -->
<div style="margin-top:16px; padding:16px 20px; background:#f8fbfd; border-radius:8px; border:1px solid #dce5ee;">
    <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
        <span style="font-weight:700; color:#16324f;">ℹ️ Backup Information:</span>
        <span style="font-size:13px; color:#627386;">
            • Backups are stored in <strong>storage/app/backups</strong>
        </span>
        <span style="font-size:13px; color:#627386;">
            • Full backup packages include SQL dump and instructions
        </span>
        <span style="font-size:13px; color:#627386;">
            • Database: <strong>{{ env('DB_DATABASE') }}</strong>
        </span>
        <span style="font-size:13px; color:#627386;">
            • Maximum backup files: <strong>Unlimited</strong>
        </span>
    </div>
</div>
@endsection