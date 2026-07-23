@extends('layouts.app')

@section('title', 'Daily Input - TNT HR')

@section('content')
    <style>
        /* ============================A================
           CUSTOM STYLES
           ============================================ */
        .daily-toolbar {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .daily-toolbar .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .daily-toolbar .field label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .daily-toolbar .field input,
        .daily-toolbar .field select {
            padding: 9px 10px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            min-width: 150px;
        }
        .daily-toolbar .field input:focus,
        .daily-toolbar .field select:focus {
            outline: none;
            border-color: #1b7f79;
            box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.1);
        }

        /* Department Filter */
        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            padding: 12px 16px;
            background: #f8fbfd;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #dce5ee;
        }
        .filter-bar label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .filter-bar select {
            padding: 8px 14px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 13px;
            background: #fff;
            min-width: 200px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23627386' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
        .filter-bar select:focus {
            outline: none;
            border-color: #1b7f79;
        }
        .filter-bar .badge-count {
            background: #1b7f79;
            color: #fff;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .filter-bar .btn-clear-filter {
            padding: 6px 14px;
            background: #e9eff5;
            color: #23384d;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
        }
        .filter-bar .btn-clear-filter:hover {
            background: #dce5ee;
        }

        /* Collapsible Sections */
        .collapsible {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 8px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .collapsible-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            cursor: pointer;
            transition: background 0.2s;
            user-select: none;
        }
        .collapsible-header:hover {
            background: #f8fbfd;
        }
        .collapsible-header .dept-name {
            font-weight: 700;
            color: #16324f;
            font-size: 14px;
        }
        .collapsible-header .dept-stats {
            display: flex;
            gap: 16px;
            align-items: center;
            font-size: 12px;
            color: var(--muted);
        }
        .collapsible-header .dept-stats span {
            background: #e8f3f2;
            color: #0c625e;
            padding: 2px 10px;
            border-radius: 999px;
            font-weight: 600;
        }
        .collapsible-header .toggle-icon {
            font-size: 16px;
            color: #627386;
            transition: transform 0.3s;
        }
        .collapsible-header .toggle-icon.open {
            transform: rotate(180deg);
        }
        .collapsible-body {
            padding: 0 16px 16px 16px;
            display: none;
        }
        .collapsible-body.open {
            display: block;
        }

        /* Table styling */
        .dept-table {
            width: 100%;
            border-collapse: collapse;
        }
        .dept-table th {
            background: #eff4f8;
            color: #34495e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 2px solid #dce5ee;
        }
        .dept-table th.num {
            text-align: right;
        }
        .dept-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .dept-table td.num {
            text-align: right;
        }
        .dept-table .type-label {
            font-size: 12px;
            font-weight: 600;
            color: #145f5b;
            background: #edf6f4;
            padding: 3px 10px;
            border-radius: 999px;
            display: inline-block;
        }
        .dept-table tr:hover td {
            background: #f8fbfd;
        }

        .headcount-input {
            width: 120px;
            text-align: right;
            padding: 6px 10px;
            border: 1px solid #dce5ee;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            transition: border-color 0.2s;
        }
        .headcount-input:focus {
            outline: none;
            border-color: #1b7f79;
            box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.1);
        }
        .headcount-input:disabled {
            background: #f4f7fb;
            color: #627386;
            cursor: not-allowed;
        }

        /* Totals section */
        .totals-section {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .totals-grid .total-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            background: #f8fbfd;
        }
        .totals-grid .total-item .label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .totals-grid .total-item .value {
            font-size: 22px;
            font-weight: 700;
            color: #16324f;
            margin-top: 4px;
        }
        .totals-grid .total-item.highlight {
            background: #dfeeea;
        }
        .totals-grid .total-item.highlight .value {
            color: #0c5d58;
        }

        /* Sticky save */
        .sticky-save {
            position: sticky;
            bottom: 10px;
            background: rgba(255,255,255,0.97);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            justify-content: flex-end;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-top: 16px;
            z-index: 10;
        }

        /* KPI cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        .kpi-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: center;
        }
        .kpi-card .label {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }
        .kpi-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #16324f;
            margin-top: 4px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .daily-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .daily-toolbar .field input,
            .daily-toolbar .field select {
                min-width: auto;
                width: 100%;
            }
            .daily-toolbar .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            .daily-toolbar .action-buttons button,
            .daily-toolbar .action-buttons a {
                width: 100%;
                text-align: center;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar select {
                min-width: auto;
                width: 100%;
            }
            .sticky-save {
                flex-direction: column;
                align-items: stretch;
            }
            .sticky-save button {
                width: 100%;
            }
            .headcount-input {
                width: 80px;
            }
            .totals-grid {
                grid-template-columns: 1fr 1fr;
            }
            .kpi-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .headcount-input {
                width: 60px;
                font-size: 12px;
                padding: 4px 6px;
            }
            .totals-grid {
                grid-template-columns: 1fr;
            }
            .kpi-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- ============================================ -->
    <!-- HEADER -->
    <!-- ============================================ -->
    <h1>📝 Daily Manpower Input</h1>
    <div class="subtitle">Enter detailed manpower once. Permanent, Contract, Daily and Grand Total figures update automatically.</div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif
    @if($message)
        <div class="alert success">{{ $message }}</div>
    @endif

    <!-- ============================================ -->
    <!-- TOOLBAR -->
    <!-- ============================================ -->
    <div class="card">
        <form class="daily-toolbar" method="get" action="{{ route('daily.index') }}">
            <div class="field">
                <label>📅 Report Date</label>
                <input type="date" name="date" value="{{ $selectedDate }}" required>
            </div>
            <div class="field">
                <label>🏗️ Project</label>
                <select name="project">
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @if($project->id == $selectedProjectId) selected @endif>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="action-buttons" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                <button type="submit">📂 Open</button>
                <a class="btn light" href="{{ route('daily.export', ['date' => $selectedDate, 'project' => $selectedProjectId]) }}">📥 Export CSV</a>
                <a class="btn" href="{{ route('daily.export-excel', ['date' => $selectedDate, 'project' => $selectedProjectId]) }}" style="background:#1b7f79;">
                    📊 Export Excel
                </a>
                <a class="btn secondary" href="{{ route('daily.index') }}">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- KPI CARDS -->
    <!-- ============================================ -->
    <div class="kpi-grid" style="margin-top:16px;">
        <div class="kpi-card">
            <div class="label">📊 Total Categories</div>
            <div class="value">{{ $categories->count() }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">📈 Total Headcount</div>
            <div class="value" id="liveGrandTotal">{{ number_format($grandTotal) }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">🏢 Active Projects</div>
            <div class="value">{{ $projects->count() }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">📅 Report Date</div>
            <div class="value" style="font-size:18px;">{{ $selectedDate }}</div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- DEPARTMENT FILTER -->
    <!-- ============================================ -->
    @php
        $departments = $categories->pluck('department')->unique()->sort()->values();
    @endphp

    @if($categories->count() > 0)
    <div class="filter-bar">
        <label>🏢 Filter by Department:</label>
        <select id="departmentFilter" onchange="filterByDepartment()">
            <option value="all">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept }}">{{ $dept }}</option>
            @endforeach
        </select>
        <span class="badge-count" id="visibleCount">{{ $categories->count() }}</span>
        <button class="btn-clear-filter" onclick="resetFilter()">Clear Filter</button>
        <span style="font-size:12px; color:var(--muted); margin-left:auto;">
            💡 Click department name to expand/collapse
        </span>
    </div>
    @endif

    <!-- ============================================ -->
    <!-- DAILY INPUT FORM -->
    <!-- ============================================ -->
    <form method="post" action="{{ route('daily.save') }}" id="dailyForm">
        @csrf
        <input type="hidden" name="date" value="{{ $selectedDate }}">
        <input type="hidden" name="project" value="{{ $selectedProjectId }}">

        <div style="margin-top:16px;">
            @php
                $groupedCategories = $categories->groupBy('department');
            @endphp

            @forelse($groupedCategories as $department => $deptCategories)
                @php
                    $deptTotal = 0;
                    $deptTypes = [];
                    foreach($deptCategories as $cat) {
                        $deptTotal += $entries[$cat->id] ?? 0;
                        $deptTypes[$cat->employment_type] = ($deptTypes[$cat->employment_type] ?? 0) + ($entries[$cat->id] ?? 0);
                    }
                @endphp

                <div class="collapsible" data-department="{{ $department }}">
                    <div class="collapsible-header" onclick="toggleCollapse(this)">
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <span class="dept-name">🏢 {{ $department }}</span>
                            <div class="dept-stats">
                                @foreach($deptTypes as $type => $count)
                                    <span>{{ $type }}: {{ number_format($count) }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="font-weight:700; color:#16324f; font-size:14px;">
                                Total: {{ number_format($deptTotal) }}
                            </span>
                            <span class="toggle-icon">▼</span>
                        </div>
                    </div>

                    <div class="collapsible-body open">
                        <table class="dept-table">
                            <thead>
                                <tr>
                                    <th style="width:80px;">Code</th>
                                    <th style="min-width:200px;">Job Title</th>
                                    <th style="width:120px;">Employment Type</th>
                                    <th style="width:150px;" class="num">Headcount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deptCategories as $category)
                                    <tr class="entry-row" data-type="{{ $category->employment_type }}">
                                        <td><span class="badge" style="background:#e8f3f2; color:#0c625e;">{{ $category->code }}</span></td>
                                        <td>{{ $category->name }}</td>
                                        <td><span class="type-label">{{ $category->employment_type }}</span></td>
                                        <td class="num">
                                            <input type="number" 
                                                   min="0" 
                                                   step="1" 
                                                   class="headcount-input"
                                                   name="cat_{{ $category->id }}" 
                                                   data-employment-type="{{ $category->employment_type }}"
                                                   data-department="{{ $department }}"
                                                   data-category="{{ $category->name }}"
                                                   value="{{ $entries[$category->id] ?? 0 }}"
                                                   @if(!$canEdit) disabled @endif>
                                            @if(!$canEdit)
                                                <span class="small" style="display:block; color:var(--muted);">(read-only)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="card" style="text-align:center; padding:40px;">
                    <div style="font-size:48px; margin-bottom:12px;">📭</div>
                    <h4 style="margin:0; color:#24384c;">No job titles configured</h4>
                    <p style="color:#627386; font-size:14px;">Please go to <a href="{{ route('projects.index') }}">Project Setup</a> to add job titles.</p>
                </div>
            @endforelse
        </div>

        <!-- ============================================ -->
        <!-- TOTALS SECTION -->
        <!-- ============================================ -->
        @if($categories->count() > 0)
        <div class="totals-section">
            <div class="totals-grid">
                <div class="total-item">
                    <div class="label">👔 Permanent</div>
                    <div class="value" id="total-Permanent">{{ number_format($typeTotals['Permanent'] ?? 0) }}</div>
                </div>
                <div class="total-item">
                    <div class="label">📄 Contract</div>
                    <div class="value" id="total-Contract">{{ number_format($typeTotals['Contract'] ?? 0) }}</div>
                </div>
                <div class="total-item">
                    <div class="label">🔧 Daily</div>
                    <div class="value" id="total-Daily">{{ number_format($typeTotals['Daily'] ?? 0) }}</div>
                </div>
                <div class="total-item highlight">
                    <div class="label">🏆 Grand Total</div>
                    <div class="value" id="total-Grand">{{ number_format($grandTotal) }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- ============================================ -->
        <!-- STICKY SAVE BUTTON -->
        <!-- ============================================ -->
        @if($canEdit && !$categories->isEmpty())
            <div class="sticky-save">
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <span class="small" id="saveStatus" style="color:var(--muted);">
                        ⚡ Press <kbd>Ctrl+S</kbd> to save
                    </span>
                    <button type="submit" id="saveButton">
                        💾 Save Daily Input
                    </button>
                </div>
            </div>
        @elseif(!$canEdit && !$categories->isEmpty())
            <div class="alert warning" style="margin-top:12px;">
                👁️ Viewer access: data entry is read-only.
            </div>
        @endif
    </form>

    <!-- ============================================ -->
    <!-- JAVASCRIPT -->
    <!-- ============================================ -->
    <script>
        // ============================================
        // DEPARTMENT FILTER
        // ============================================
        function filterByDepartment() {
            const filter = document.getElementById('departmentFilter').value;
            const collapsibles = document.querySelectorAll('.collapsible');
            let visibleCount = 0;

            collapsibles.forEach(function(el) {
                const dept = el.dataset.department;
                if (filter === 'all' || dept === filter) {
                    el.style.display = 'block';
                    visibleCount += el.querySelectorAll('.entry-row').length;
                } else {
                    el.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function resetFilter() {
            document.getElementById('departmentFilter').value = 'all';
            filterByDepartment();
        }

        // ============================================
        // COLLAPSIBLE SECTIONS
        // ============================================
        function toggleCollapse(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            
            if (body.classList.contains('open')) {
                body.classList.remove('open');
                icon.classList.remove('open');
                icon.textContent = '▶';
            } else {
                body.classList.add('open');
                icon.classList.add('open');
                icon.textContent = '▼';
            }
        }

        // ============================================
        // LIVE TOTALS
        // ============================================
        (function() {
            const types = ["Permanent", "Contract", "Daily"];
            
            function refreshTotals() {
                let grand = 0;
                types.forEach(function(type) {
                    let total = 0;
                    document.querySelectorAll('input[data-employment-type="' + type + '"]').forEach(function(el) {
                        const value = parseFloat(el.value || "0");
                        if (!Number.isNaN(value) && value >= 0) {
                            total += value;
                        }
                    });
                    grand += total;
                    const cell = document.getElementById("total-" + type);
                    if (cell) cell.textContent = Math.round(total).toLocaleString();
                });
                
                const grandCell = document.getElementById("total-Grand");
                if (grandCell) grandCell.textContent = Math.round(grand).toLocaleString();
                
                // Update live grand total in KPI
                const liveGrand = document.getElementById("liveGrandTotal");
                if (liveGrand) liveGrand.textContent = Math.round(grand).toLocaleString();

                // Update department totals
                const departments = {};
                document.querySelectorAll('.headcount-input').forEach(function(el) {
                    const dept = el.dataset.department;
                    const value = parseFloat(el.value || "0");
                    if (dept && !Number.isNaN(value) && value >= 0) {
                        departments[dept] = (departments[dept] || 0) + value;
                    }
                });

                // Update department stats
                document.querySelectorAll('.collapsible').forEach(function(el) {
                    const dept = el.dataset.department;
                    const total = departments[dept] || 0;
                    const header = el.querySelector('.collapsible-header');
                    const totalSpan = header.querySelector('span[style*="font-weight:700"]');
                    if (totalSpan) {
                        totalSpan.textContent = 'Total: ' + Math.round(total).toLocaleString();
                    }

                    // Update type stats for each department
                    const typeStats = {};
                    el.querySelectorAll('.headcount-input').forEach(function(input) {
                        const type = input.dataset.employmentType;
                        const val = parseFloat(input.value || "0");
                        if (type && !Number.isNaN(val) && val >= 0) {
                            typeStats[type] = (typeStats[type] || 0) + val;
                        }
                    });

                    const statsContainer = header.querySelector('.dept-stats');
                    if (statsContainer) {
                        let html = '';
                        Object.keys(typeStats).forEach(function(type) {
                            html += '<span>' + type + ': ' + Math.round(typeStats[type]).toLocaleString() + '</span>';
                        });
                        statsContainer.innerHTML = html;
                    }
                });
            }

            // Add event listeners to all headcount inputs
            document.querySelectorAll('.headcount-input').forEach(function(el) {
                el.addEventListener("input", refreshTotals);
                el.addEventListener("change", function() {
                    if (this.value < 0) this.value = 0;
                    refreshTotals();
                });
            });
            
            // Initial refresh
            refreshTotals();

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    const saveBtn = document.getElementById('saveButton');
                    if (saveBtn) saveBtn.click();
                }
            });

            // Show save confirmation
            const form = document.getElementById('dailyForm');
            if (form) {
                form.addEventListener('submit', function() {
                    const saveBtn = document.getElementById('saveButton');
                    if (saveBtn) {
                        saveBtn.textContent = '⏳ Saving...';
                        saveBtn.disabled = true;
                    }
                });
            }
        })();
    </script>
@endsection