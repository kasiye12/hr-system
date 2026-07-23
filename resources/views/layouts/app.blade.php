<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TNT HR')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #eef2ff;
            --success: #059669;
            --success-light: #ecfdf5;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --info: #0284c7;
            --info-light: #f0f9ff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --sidebar-w: 260px;
            --header-h: 64px;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.08);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* HEADER */
        .header {
            height: var(--header-h);
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
            color: var(--gray-900);
            letter-spacing: -0.5px;
        }
        .header-logo .icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px; font-weight: 800;
        }
        .header-logo span { color: var(--primary); }
        .header-user {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 16px; border-radius: 100px;
            background: var(--gray-100);
            font-weight: 600; font-size: 13px; color: var(--gray-700);
        }
        .header-user .avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
        }
        .header-user .role {
            font-size: 10px; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: var(--header-h);
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: var(--white);
            border-right: 1px solid var(--gray-200);
            padding: 20px 14px;
            overflow-y: auto;
            z-index: 50;
        }
        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

        .sidebar-section {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--gray-400);
            padding: 16px 12px 8px;
        }
        .sidebar a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; margin: 1px 0; border-radius: 10px;
            color: var(--gray-600); font-weight: 500; font-size: 13px;
            text-decoration: none; transition: all 0.15s;
        }
        .sidebar a:hover { background: var(--gray-100); color: var(--gray-900); }
        .sidebar a.active {
            background: var(--primary-light); color: var(--primary); font-weight: 600;
        }
        .sidebar a .ico { font-size: 17px; width: 22px; text-align: center; }
        .sidebar a .badge-new {
            margin-left: auto; background: var(--primary); color: #fff;
            font-size: 9px; padding: 2px 8px; border-radius: 100px; font-weight: 700;
        }

        .sidebar-footer {
            margin-top: auto; padding-top: 16px; border-top: 1px solid var(--gray-200);
            text-align: center; font-size: 11px; color: var(--gray-400);
        }

        /* MAIN */
        .main {
            margin-left: var(--sidebar-w);
            margin-top: var(--header-h);
            padding: 28px;
            min-height: calc(100vh - var(--header-h));
        }

        /* CARDS */
        .card {
            background: var(--white); border-radius: var(--radius);
            border: 1px solid var(--gray-200); box-shadow: var(--shadow);
            padding: 24px; margin-bottom: 20px;
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--gray-100);
        }
        .card-header h2 { font-size: 16px; font-weight: 700; color: var(--gray-900); }
        .card-header p { font-size: 12px; color: var(--gray-500); margin-top: 2px; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat-card {
            background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200);
            padding: 20px; box-shadow: var(--shadow); transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .stat-card .stat-value { font-size: 28px; font-weight: 800; color: var(--gray-900); }
        .stat-card .stat-label { font-size: 11px; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .stat-card.purple { border-left: 4px solid var(--primary); }
        .stat-card.green { border-left: 4px solid var(--success); }
        .stat-card.red { border-left: 4px solid var(--danger); }
        .stat-card.amber { border-left: 4px solid var(--warning); }

        /* TABLES */
        .table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--gray-200); }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: var(--gray-50); padding: 12px 16px; font-size: 10px;
            text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-500);
            font-weight: 700; text-align: left; border-bottom: 1px solid var(--gray-200);
        }
        td { padding: 12px 16px; border-bottom: 1px solid var(--gray-100); font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--gray-50); }
        .num { text-align: center; font-weight: 600; font-variant-numeric: tabular-nums; }

        /* BUTTONS */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px;
            cursor: pointer; border: none; text-decoration: none; transition: all 0.15s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-outline { background: var(--white); color: var(--gray-700); border: 1px solid var(--gray-300); }
        .btn-outline:hover { background: var(--gray-50); }
        .btn-sm { padding: 5px 12px; font-size: 11px; border-radius: 6px; }
        .btn-xs { padding: 3px 8px; font-size: 10px; border-radius: 5px; }

        /* BADGES */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 600;
        }
        .badge-success { background: var(--success-light); color: var(--success); }
        .badge-danger { background: var(--danger-light); color: var(--danger); }
        .badge-warning { background: var(--warning-light); color: var(--warning); }
        .badge-info { background: var(--info-light); color: var(--info); }

        /* ALERTS */
        .alert { padding: 12px 18px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; font-weight: 500; }
        .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #a7f3d0; }
        .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #fecaca; }
        .alert-warning { background: var(--warning-light); color: var(--warning); border: 1px solid #fde68a; }

        /* FORM */
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 8px;
            font-size: 13px; font-family: inherit; transition: border 0.15s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        /* MOBILE */
        .menu-btn { display: none; background: none; border: none; font-size: 24px; cursor: pointer; color: var(--gray-700); }
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 99; }

        @media (max-width: 768px) {
            .menu-btn { display: block; }
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .overlay.show { display: block; }
            .main { margin-left: 0; padding: 16px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header-logo { font-size: 16px; }
            .header-user { font-size: 11px; padding: 4px 10px; }
            .header { padding: 0 14px; }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div style="display:flex; align-items:center; gap:14px;">
            <button class="menu-btn" id="menuBtn" onclick="toggleSidebar()">☰</button>
            <div class="header-logo">
                <div class="icon">T</div>
                TNT<span>HR</span>
            </div>
        </div>
        <div class="header-user">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) }}</div>
            <div>
                <div>{{ auth()->user()->username ?? 'User' }}</div>
                <div class="role">{{ auth()->user()->role ?? 'Staff' }}</div>
            </div>
        </div>
    </header>

    <!-- OVERLAY -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-section">Main</div>
        <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) class="active" @endif>
            <span class="ico">📊</span> Dashboard
        </a>
        <a href="{{ route('daily.index') }}" @if(request()->routeIs('daily.*')) class="active" @endif>
            <span class="ico">📝</span> Daily Input
        </a>
        <a href="{{ route('applicants.index') }}" @if(request()->routeIs('applicants.*')) class="active" @endif>
            <span class="ico">👤</span> Applicants
        </a>

        <div class="sidebar-section">Management</div>
        <a href="{{ route('documents.index') }}" @if(request()->routeIs('documents.*')) class="active" @endif>
            <span class="ico">📄</span> Documents
        </a>
        <a href="{{ route('leaves.index') }}" @if(request()->routeIs('leaves.*')) class="active" @endif>
            <span class="ico">🏖️</span> Leave Management
            <span class="badge-new">NEW</span>
        </a>

        <div class="sidebar-section">Projects</div>
        <a href="{{ route('projects.index') }}" @if(request()->routeIs('projects.*')) class="active" @endif>
            <span class="ico">⚙️</span> Project Setup
        </a>
        <a href="{{ route('comparison.index') }}" @if(request()->routeIs('comparison.*')) class="active" @endif>
            <span class="ico">📈</span> Comparison
        </a>

        <div class="sidebar-section">Data</div>
        <a href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) class="active" @endif>
            <span class="ico">📊</span> Reports
        </a>
        <a href="{{ route('import.index') }}" @if(request()->routeIs('import.*')) class="active" @endif>
            <span class="ico">📥</span> Import Data
        </a>

        @if(auth()->user() && auth()->user()->role === 'admin')
        <div class="sidebar-section">Admin</div>
        <a href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) class="active" @endif>
            <span class="ico">👥</span> Users
        </a>
        <a href="{{ route('backup.index') }}" @if(request()->routeIs('backup.*')) class="active" @endif>
            <span class="ico">💾</span> Backup
        </a>
        @endif

        <div class="sidebar-section">Account</div>
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#dc2626;">
            <span class="ico">🚪</span> Log Out
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>

        <div class="sidebar-footer">TNT HR v2.0 © {{ date('Y') }}</div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @yield('content')
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('show');
        }
    </script>
</body>
</html>
