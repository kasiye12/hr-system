@extends('layouts.app')

@section('title', 'Daily Input - TNT HR')

@section('content')
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

    <!-- Toolbar -->
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
            <div style="display:flex; gap:10px; align-items:end;">
                <button type="submit">Open</button>
                <a class="btn light" href="{{ route('daily.export', ['date' => $selectedDate, 'project' => $selectedProjectId]) }}">📥 Export CSV</a>
                <a class="btn secondary" href="{{ route('daily.index') }}">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- Daily Input Form -->
    <form method="post" action="{{ route('daily.save') }}" id="dailyForm">
        @csrf
        <input type="hidden" name="date" value="{{ $selectedDate }}">
        <input type="hidden" name="project" value="{{ $selectedProjectId }}">

        <div class="card" style="margin-top:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
                <h2 style="margin:0;">
                    {{ $selectedProject->name ?? '' }} 
                    <span style="font-size:14px; font-weight:normal; color:var(--muted);">
                        · {{ $selectedDate }}
                    </span>
                </h2>
                <div>
                    <span style="font-size:14px; color:var(--muted);">
                        📊 Last updated: {{ now()->format('H:i:s') }}
                    </span>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table id="dailyTable">
                    <thead>
                        <tr>
                            <th style="width:80px;">Code</th>
                            <th style="min-width:200px;">Employee Category / Job Title</th>
                            <th style="width:120px;">Type</th>
                            <th style="width:150px;" class="num">Headcount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $lastDept = null;
                            $lastType = null;
                            $hasCategories = false;
                        @endphp
                        @foreach($categories as $category)
                            @php $hasCategories = true; @endphp
                            @if($category->department != $lastDept)
                                <tr class="dept-row">
                                    <td colspan="4">
                                        <strong>🏢 {{ $category->department }}</strong>
                                    </td>
                                </tr>
                                @php $lastDept = $category->department; $lastType = null; @endphp
                            @endif
                            @if($category->employment_type != $lastType)
                                <tr class="type-row">
                                    <td colspan="4" style="padding-left:20px;">
                                        <span style="color:#145f5b; font-weight:700;">
                                            {{ $category->employment_type }} Employees
                                        </span>
                                    </td>
                                </tr>
                                @php $lastType = $category->employment_type; @endphp
                            @endif
                            <tr class="entry-row" data-type="{{ $category->employment_type }}">
                                <td><span class="badge" style="background:#e8f3f2; color:#0c625e;">{{ $category->code }}</span></td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->employment_type }}</td>
                                <td class="num">
                                    <input type="number" 
                                           min="0" 
                                           step="1" 
                                           class="headcount-input"
                                           name="cat_{{ $category->id }}" 
                                           data-employment-type="{{ $category->employment_type }}"
                                           data-category="{{ $category->name }}"
                                           value="{{ $entries[$category->id] ?? 0 }}"
                                           @if(!$canEdit) disabled @endif>
                                    @if(!$canEdit)
                                        <span class="small" style="display:block; color:var(--muted);">(read-only)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if(!$hasCategories)
                            <tr>
                                <td colspan="4" class="empty">
                                    <strong>⚠️ No job titles configured.</strong><br>
                                    <span style="font-size:12px;">
                                        Please go to <a href="{{ route('projects.index') }}">Project Setup</a> to add job titles.
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3"><strong>Total Permanent Employees</strong></td>
                            <td class="num" id="total-Permanent">{{ number_format($typeTotals['Permanent'] ?? 0) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3"><strong>Total Contract Employees</strong></td>
                            <td class="num" id="total-Contract">{{ number_format($typeTotals['Contract'] ?? 0) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3"><strong>Total Daily Employees</strong></td>
                            <td class="num" id="total-Daily">{{ number_format($typeTotals['Daily'] ?? 0) }}</td>
                        </tr>
                        <tr class="grand-total-row">
                            <td colspan="3"><strong>🏆 Grand Total Manpower</strong></td>
                            <td class="num" id="total-Grand">{{ number_format($grandTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($canEdit && $hasCategories)
                <div class="sticky-save">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <span class="small" id="saveStatus" style="color:var(--muted);">
                            ⚡ Changes are auto-saved when you click the button
                        </span>
                        <button type="submit" id="saveButton">
                            💾 Save Daily Input
                        </button>
                    </div>
                </div>
            @elseif(!$canEdit)
                <div class="alert warning" style="margin-top:12px;">
                    👁️ Viewer access: data entry is read-only.
                </div>
            @endif
        </div>
    </form>

    <!-- Quick Stats -->
    <div class="grid kpis" style="margin-top:16px;">
        <div class="card kpi">
            <div class="label">📊 Total Categories</div>
            <div class="value">{{ $categories->count() }}</div>
        </div>
        <div class="card kpi">
            <div class="label">📈 Total Headcount</div>
            <div class="value" id="liveGrandTotal">{{ number_format($grandTotal) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">🏢 Active Projects</div>
            <div class="value">{{ $projects->count() }}</div>
        </div>
        <div class="card kpi">
            <div class="label">📅 Report Date</div>
            <div class="value" style="font-size:18px;">{{ $selectedDate }}</div>
        </div>
    </div>

    <style>
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
        }

        .dept-row td {
            background: #dce9f2 !important;
            color: #253b50;
            font-weight: 800;
            padding: 8px 12px;
        }
        .type-row td {
            background: #edf6f4 !important;
            color: #145f5b;
            font-weight: 700;
            padding: 6px 12px;
        }
        .entry-row td {
            background: #fff;
        }
        .entry-row:hover td {
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

        .total-row td {
            background: #f8fbfd !important;
            font-weight: 700;
            padding: 10px 12px;
        }
        .grand-total-row td {
            background: #dfeeea !important;
            color: #0c5d58;
            font-weight: 800;
            font-size: 15px;
            padding: 12px 12px;
        }

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

        /* Mobile responsive */
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
            .daily-toolbar div {
                flex-direction: column;
            }
            .daily-toolbar div button,
            .daily-toolbar div a {
                width: 100%;
                text-align: center;
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
            #dailyTable {
                font-size: 12px;
            }
            #dailyTable th,
            #dailyTable td {
                padding: 6px 8px;
            }
        }

        @media (max-width: 480px) {
            .headcount-input {
                width: 60px;
                font-size: 12px;
                padding: 4px 6px;
            }
            #dailyTable th,
            #dailyTable td {
                padding: 4px 6px;
            }
            .grid.kpis {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>

    <!-- JavaScript for live totals -->
    <script>
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
        }

        // Add event listeners to all headcount inputs
        document.querySelectorAll('.headcount-input').forEach(function(el) {
            el.addEventListener("input", refreshTotals);
            el.addEventListener("change", function() {
                // Ensure value is not negative
                if (this.value < 0) this.value = 0;
                refreshTotals();
            });
        });
        
        // Initial refresh
        refreshTotals();

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save
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