@extends('layouts.app')

@section('title', 'Backup - TNT HR')

@section('content')
    <h1>Database Backup</h1>
    <div class="subtitle">Create and download database backups.</div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="grid kpis">
        <div class="card kpi">
            <div class="label">Database Size</div>
            <div class="value">{{ number_format($dbSize, 2) }} MB</div>
        </div>
    </div>

    <div class="card">
        <h2>Backup Actions</h2>
        <div class="actions">
            <a href="{{ route('backup.download') }}" class="btn">Download Backup</a>
            <a href="{{ route('backup.local') }}" class="btn secondary">Create Local Backup</a>
        </div>
        <p class="subtitle" style="font-size:12px;">The backup includes all database tables and data.</p>
    </div>
@endsection