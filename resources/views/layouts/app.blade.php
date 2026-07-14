<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TNT HR Manpower Reporting')</title>
    <style>
        :root {
            --nav: #16324f;
            --nav2: #0f2235;
            --accent: #1b7f79;
            --bg: #f4f7fb;
            --card: #fff;
            --text: #1b2635;
            --muted: #627386;
            --line: #dce5ee;
            --danger: #a61b1b;
            --success: #18794e;
            --warn: #a45100;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }
        .topbar {
            background: linear-gradient(100deg, var(--nav2), var(--nav));
            color: #fff;
            padding: 8px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.13);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand .logo {
            width: 40px;
            height: 40px;
            background: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: var(--nav);
        }
        .brand-name {
            font-size: 18px;
            font-weight: 700;
        }
        .brand-sub {
            font-size: 11px;
            color: #bcd4e6;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .userbox {
            font-size: 13px;
            color: #dce8f4;
        }
        .shell {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: calc(100vh - 60px);
        }
        .sidebar {
            background: #fff;
            border-right: 1px solid var(--line);
            padding: 16px 8px;
        }
        .sidebar a {
            display: block;
            padding: 10px 14px;
            margin: 2px 0;
            border-radius: 7px;
            color: #24384c;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background: #e8f3f2;
            color: #0c625e;
        }
        .main {
            padding: 24px;
            min-width: 0;
        }
        h1 { font-size: 24px; margin: 0 0 6px; }
        .subtitle { color: var(--muted); margin-bottom: 16px; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 2px 7px rgba(21,49,78,0.04);
            overflow: auto;
            margin-bottom: 16px;
        }
        .grid { display: grid; gap: 16px; }
        .kpis { grid-template-columns: repeat(4, 1fr); }
        .two { grid-template-columns: 1fr 1fr; }
        .kpi .label { color: var(--muted); font-weight: 600; font-size: 13px; }
        .kpi .value { font-size: 28px; font-weight: 700; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }
        th {
            background: #eff4f8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #34495e;
        }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .grand-total-row {
            font-weight: 800;
            background: #dfeeea;
            color: #0c5d58;
        }
        .btn, button {
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: filter 0.15s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover, button:hover { filter: brightness(0.93); }
        .btn.secondary { background: #60758a; }
        .btn.danger { background: #a94343; }
        .btn.light { background: #e9eff5; color: #23384d; }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge.active { background: #dcf5e7; color: #12643c; }
        .badge.inactive { background: #eee; color: #666; }
        .badge.admin { background: #e6e3ff; color: #4b3bad; }
        .badge.editor { background: #dff3ff; color: #0d6289; }
        .badge.viewer { background: #eee; color: #555; }
        .alert {
            padding: 10px 14px;
            border-radius: 7px;
            margin: 10px 0;
        }
        .alert.success { background: #e9f7ef; color: var(--success); border: 1px solid #b9e3ca; }
        .alert.error { background: #ffeded; color: var(--danger); border: 1px solid #efbbbb; }
        .alert.warning { background: #fff5e5; color: var(--warn); border: 1px solid #f1d3a3; }
        .inline-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .field label { font-size: 12px; color: var(--muted); font-weight: 700; }
        input, select {
            padding: 8px 10px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 13px;
            min-width: 140px;
        }
        input:focus, select:focus { outline: none; border-color: var(--accent); }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin: 10px 0; }
        .sticky-save {
            position: sticky;
            bottom: 10px;
            background: rgba(255,255,255,0.95);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .empty {
            text-align: center;
            padding: 30px;
            color: var(--muted);
        }
        .section-gap { margin-top: 16px; }
        @media (max-width: 768px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { display: flex; overflow-x: auto; padding: 8px; border-right: none; border-bottom: 1px solid var(--line); }
            .sidebar a { white-space: nowrap; }
            .kpis, .two { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <div class="logo">TNT</div>
            <div>
                <div class="brand-name">TNT HR Manpower Reporting</div>
                <div class="brand-sub">Daily · Weekly · Monthly Reporting</div>
            </div>
        </div>
        <div class="userbox">
            {{ auth()->user()->username ?? 'Guest' }} · {{ auth()->user()->role ?? '' }}
        </div>
    </header>

    <div class="shell">
        <nav class="sidebar">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) class="active" @endif>Dashboard</a>
            <a href="{{ route('daily.index') }}" @if(request()->routeIs('daily.*')) class="active" @endif>Daily Input</a>
            <a href="{{ route('applicants.index') }}" @if(request()->routeIs('applicants.*')) class="active" @endif>Applicants</a>
            <a href="{{ route('projects.index') }}" @if(request()->routeIs('projects.*')) class="active" @endif>Project Setup</a>
            <a href="{{ route('comparison.index') }}" @if(request()->routeIs('comparison.*')) class="active" @endif>Comparison</a>
            <a href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) class="active" @endif>Reports</a>
            @if(auth()->user() && auth()->user()->role === 'admin')
                <a href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) class="active" @endif>Users</a>
                <a href="{{ route('backup.index') }}" @if(request()->routeIs('backup.*')) class="active" @endif>Backup</a>
            @endif
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Log Out</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </nav>
        <main class="main">
            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert warning">{{ session('warning') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>