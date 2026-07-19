@extends('layouts.app')

@section('title', 'Applicant Registration - TNT HR')

@section('content')
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
            --blue: #397a9f;
            --orange: #d38b2a;
        }
        
        * { box-sizing: border-box; }
        
        .applicant-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
            background: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            box-shadow: 0 2px 7px rgba(21,49,78,0.04);
        }
        .applicant-nav a {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #24384c;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .applicant-nav a:hover { background: #e8f3f2; color: #0c625e; }
        .applicant-nav a.active { background: var(--accent); color: #fff; }
        .applicant-nav a .badge-count {
            background: #e8f3f2; color: #0c625e;
            font-size: 10px; padding: 1px 8px; border-radius: 999px; font-weight: 700;
        }
        .applicant-nav a.active .badge-count { background: rgba(255,255,255,0.2); color: #fff; }
        
        .section-card { display: none; animation: fadeIn 0.3s ease; }
        .section-card.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .stats-grid .stat-card {
            background: #fff; border: 1px solid var(--line); border-radius: 8px;
            padding: 14px 16px; text-align: center; transition: transform 0.2s;
        }
        .stats-grid .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stats-grid .stat-card .number { font-size: 24px; font-weight: 700; color: var(--nav); }
        .stats-grid .stat-card .label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; margin-top: 4px; }
        .stats-grid .stat-card.highlight { background: #dfeeea; border-color: var(--accent); }
        .stats-grid .stat-card.highlight .number { color: #0c5d58; }
        
        .card { background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 20px; box-shadow: 0 2px 7px rgba(21,49,78,0.04); margin-bottom: 16px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #f0f4f8; }
        .card-header h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text); }
        .card-header .subtitle { font-size: 12px; color: var(--muted); margin-top: 2px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; align-items: end; }
        .form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; align-items: end; }
        .field { display: flex; flex-direction: column; gap: 5px; }
        .field label { font-size: 12px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .field input, .field select, .field textarea {
            padding: 9px 12px; border: 1px solid #bac8d6; border-radius: 6px;
            font-size: 13px; width: 100%; min-width: 0; transition: border-color 0.15s, box-shadow 0.15s; font-family: inherit;
        }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(27,127,121,0.1); }
        .field textarea { resize: vertical; min-height: 60px; }
        
        .btn { border: none; border-radius: 6px; padding: 9px 18px; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.15s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn-primary { background: var(--accent); color: #fff; } .btn-primary:hover { background: #156b66; }
        .btn-secondary { background: #60758a; color: #fff; }
        .btn-danger { background: #a94343; color: #fff; }
        .btn-light { background: #e9eff5; color: #23384d; }
        .btn-teal { background: var(--accent); color: #fff; }
        .btn-blue { background: var(--blue); color: #fff; }
        .btn-orange { background: var(--orange); color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: 11px; }
        .btn-xs { padding: 3px 8px; font-size: 10px; }
        
        .table-wrap { overflow-x: auto; margin-top: 12px; border-radius: 8px; border: 1px solid var(--line); }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--line); font-size: 13px; }
        th { background: #f0f4f8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #34495e; font-weight: 700; white-space: nowrap; }
        tr:hover td { background: #f8fbfd; }
        .num { text-align: right; font-variant-numeric: tabular-nums; font-family: 'SF Mono', 'Consolas', monospace; }
        
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .badge-success { background: #dcf5e7; color: #12643c; }
        .badge-danger { background: #ffeded; color: #a61b1b; }
        .badge-warning { background: #fff5e5; color: #a45100; }
        .badge-info { background: #dff3ff; color: #0d6289; }
        .badge-purple { background: #e6e3ff; color: #4b3bad; }
        .badge-muted { background: #f0f0f0; color: #666; }
        .badge-teal { background: #e8f3f2; color: #0c625e; }
        .badge-status.active { background: #dcf5e7; color: #12643c; }
        .badge-status.inactive { background: #eee; color: #666; }
        .badge-status.pending { background: #fff5e5; color: #a45100; }
        .badge-status.selected { background: #dcf5e7; color: #12643c; }
        .badge-status.rejected { background: #ffeded; color: #a61b1b; }
        .badge-status.shortlisted { background: #e6e3ff; color: #4b3bad; }
        .badge-status.reserve { background: #dff3ff; color: #0d6289; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin: 12px 0; font-size: 13px; }
        .alert-success { background: #e9f7ef; color: var(--success); border: 1px solid #b9e3ca; }
        .alert-error { background: #ffeded; color: var(--danger); border: 1px solid #efbbbb; }
        .alert-warning { background: #fff5e5; color: var(--warn); border: 1px solid #f1d3a3; }
        .alert-info { background: #e8f4fd; color: #0d6289; border: 1px solid #b8d8f0; }
        
        .searchable-wrapper { position: relative; width: 100%; }
        .searchable-input { width: 100% !important; padding: 9px 36px 9px 14px !important; border: 1px solid #bac8d6 !important; border-radius: 6px !important; font-size: 13px !important; }
        .searchable-input:focus { border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(27,127,121,0.1) !important; }
        .searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; background: white; border: 1px solid #bac8d6; border-top: none; border-radius: 0 0 8px 8px; z-index: 9999; box-shadow: 0 8px 20px rgba(0,0,0,0.12); display: none; }
        .searchable-dropdown.visible { display: block; border-color: var(--accent); }
        .searchable-option { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.12s; font-size: 13px; }
        .searchable-option:hover { background: #e8f3f2; }
        .searchable-option.selected { background: #d4edda !important; border-left: 3px solid var(--accent); }
        .searchable-option.clear-option { color: var(--muted); font-style: italic; background: #f8f9fa; }
        .searchable-highlight { background: #fff3cd; padding: 1px 3px; border-radius: 2px; }
        
        .applicant-list-panel { border: 2px solid var(--accent); border-radius: 10px; overflow: hidden; background: #fff; }
        .applicant-list-header { background: #f8fbfd; padding: 14px 18px; border-bottom: 2px solid #e0e8f0; }
        .applicant-list-body { max-height: 450px; overflow-y: auto; }
        .applicant-list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: all 0.12s; }
        .applicant-list-item:hover { background: #e8f3f2; }
        .applicant-list-item.selected { background: #d4edda; border-left: 4px solid var(--accent); }
        .applicant-list-item .info { flex: 1; min-width: 0; }
        .applicant-list-item .name { font-weight: 600; font-size: 14px; color: var(--text); margin-bottom: 3px; }
        .applicant-list-item .meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; color: var(--muted); }
        
        .section-gap { margin-top: 20px; }
        .text-center { text-align: center; }
        .text-sm { font-size: 12px; }
        .text-xs { font-size: 11px; }
        .flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-2 { gap: 8px; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--muted); }
        .highlight-box { background: #f8fbfd; border: 1px solid #dce5ee; border-radius: 8px; padding: 18px; }
        
        .exp-result-card { background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #dce5ee; }
        .exp-value { font-size: 20px; font-weight: 700; }
        
        @media (max-width: 1024px) { .form-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr; } .form-grid-3 { grid-template-columns: 1fr 1fr; } .applicant-nav { flex-direction: column; } }
        @media (max-width: 480px) { .form-grid { grid-template-columns: 1fr; } .form-grid-3 { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: 1fr 1fr; } }
    </style>

    <!-- PAGE HEADER -->
    <div class="flex justify-between items-center flex-wrap mb-3">
        <div>
            <h1 style="margin:0; font-size:22px; font-weight:700;">📝 Applicant Registration</h1>
            <p style="margin:4px 0 0; color:var(--muted); font-size:13px;">Manage applicants, work experience, committee reviews, and selection decisions</p>
        </div>
        <div class="flex gap-2 flex-wrap mt-2">
            @if($canEdit)
                <a href="#registration-form" class="btn btn-teal" onclick="switchSection('registration')">➕ New Applicant</a>
            @endif
            <a href="#report-parameters" class="btn btn-blue" onclick="switchSection('reports')">📊 Reports</a>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div class="stats-grid">
        <div class="stat-card"><div class="number">{{ number_format($stats['total']) }}</div><div class="label">📊 Total Applicants</div></div>
        <div class="stat-card"><div class="number">{{ number_format($stats['degree_plus']) }}</div><div class="label">🎓 Degree & Above</div></div>
        <div class="stat-card highlight"><div class="number">{{ number_format($stats['selected']) }}</div><div class="label">✅ Selected</div></div>
        <div class="stat-card"><div class="number">{{ number_format($stats['positions']) }}</div><div class="label">📌 Positions</div></div>
        <div class="stat-card"><div class="number">{{ number_format($applicants->count()) }}</div><div class="label">👥 Active</div></div>
        <div class="stat-card"><div class="number">{{ number_format($organizations->count()) }}</div><div class="label">🏢 Organizations</div></div>
    </div>

    <!-- NAVIGATION TABS -->
    <div class="applicant-nav" id="mainNav">
        <a href="#section-registration" class="active" onclick="switchSection('registration')">📝 Register <span class="badge-count">New</span></a>
        <a href="#section-experience" onclick="switchSection('experience')">💼 Experience <span class="badge-count">{{ $allWorkExperiences->sum(fn($e) => $e->count()) }}</span></a>
        <a href="#section-review" onclick="switchSection('review')">🎯 Committee Review</a>
        <a href="#section-list" onclick="switchSection('list')">📋 List <span class="badge-count">{{ $applicants->count() }}</span></a>
        <a href="#section-calculation" onclick="switchSection('calculation')">📊 Calculate</a>
        <a href="#section-positions" onclick="switchSection('positions')">📌 Positions <span class="badge-count">{{ $positions->count() }}</span></a>
        <a href="#section-criteria" onclick="switchSection('criteria')">📊 Criteria</a>
        <a href="#section-reports" onclick="switchSection('reports')">📈 Reports</a>
    </div>

    <!-- SECTION 1: REGISTRATION -->
    <div id="section-registration" class="section-card active">
        <div class="card" id="registration-form">
            <div class="card-header">
                <div>
                    <h2>{{ $editApplicant ? '✏️ Update Applicant' : '📋 Register New Applicant' }}</h2>
                    <p class="subtitle">Fields marked with <span style="color:red;">*</span> are required</p>
                </div>
            </div>
            @if($canEdit && isset($activePositions) && $activePositions->count() > 0)
                <form method="post" class="form-grid" action="{{ $editApplicant ? route('applicants.update', $editApplicant->id) : route('applicants.store') }}">
                    @csrf
                    @if($editApplicant) @method('PUT') @endif
                    <div class="field">
                        <label>Position <span style="color:red;">*</span></label>
                        <div class="searchable-wrapper">
                            <input type="text" class="searchable-input" id="position_search" placeholder="🔍 Search position..." autocomplete="off"
                                   onfocus="toggleDropdown('position', true)" onkeyup="filterDropdown('position')"
                                   value="{{ old('position_text', $editApplicant && $editApplicant->position ? $editApplicant->position->organization->name . ' — ' . $editApplicant->position->name : '') }}">
                            <div id="position_dropdown" class="searchable-dropdown">
                                <div class="searchable-option clear-option" data-value="" data-text="" onclick="selectFromDropdown('position', '', '', this)">✖ Clear</div>
                                @foreach($activePositions as $pos)
                                    <div class="searchable-option" data-value="{{ $pos->id }}" data-text="{{ $pos->organization->name ?? '' }} — {{ $pos->name }}"
                                         data-subtext="Grade: {{ $pos->grade ?? 'N/A' }} | Salary: {{ $pos->salary ?? 'N/A' }}"
                                         onclick="selectFromDropdown('position', '{{ $pos->id }}', '{{ $pos->organization->name ?? '' }} — {{ $pos->name }}', this)">
                                        <strong>{{ $pos->organization->name ?? '' }} — {{ $pos->name }}</strong>
                                        <div class="text-xs text-muted">Grade: {{ $pos->grade ?? 'N/A' }} | Salary: {{ $pos->salary ?? 'N/A' }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <input type="hidden" name="position_id" id="position" value="{{ old('position_id', $editApplicant->position_id ?? '') }}" required>
                        </div>
                    </div>
                    <div class="field"><label>First Name <span style="color:red;">*</span></label><input type="text" name="first_name" value="{{ old('first_name', $editApplicant->first_name ?? '') }}" required></div>
                    <div class="field"><label>Middle Name</label><input type="text" name="middle_name" value="{{ old('middle_name', $editApplicant->middle_name ?? '') }}"></div>
                    <div class="field"><label>Surname <span style="color:red;">*</span></label><input type="text" name="surname" value="{{ old('surname', $editApplicant->surname ?? '') }}" required></div>
                    <div class="field">
                        <label>Registration Date <span style="color:red;">*</span></label>
                        @php
                            $regDate = '';
                            if ($editApplicant && $editApplicant->registration_date) {
                                $regDate = date('Y-m-d', strtotime($editApplicant->registration_date));
                            }
                            $regDate = old('registration_date', $regDate);
                        @endphp
                        <input type="date" name="registration_date" value="{{ $regDate }}" required>
                    </div>
                    <div class="field">
                        <label>Graduation Date</label>
                        @php
                            $gradDate = '';
                            if ($editApplicant && $editApplicant->graduation_date) {
                                $gradDate = date('Y-m-d', strtotime($editApplicant->graduation_date));
                            }
                            $gradDate = old('graduation_date', $gradDate);
                        @endphp
                        <input type="date" name="graduation_date" value="{{ $gradDate }}">
                    </div>
                    <div class="field"><label>Exp. Years <span style="color:red;">*</span></label><input type="number" name="experience_years" min="0" value="{{ old('experience_years', $editApplicant->experience_years ?? 0) }}" required></div>
                    <div class="field"><label>Exp. Months <span style="color:red;">*</span></label><input type="number" name="experience_months" min="0" max="11" value="{{ old('experience_months', $editApplicant->experience_months ?? 0) }}" required></div>
                    <div class="field"><label>Academic Level <span style="color:red;">*</span></label><select name="academic_level" required>@foreach($academicLevels as $level)<option value="{{ $level }}" @if(old('academic_level', $editApplicant->academic_level ?? 'First Degree') == $level) selected @endif>{{ $level }}</option>@endforeach</select></div>
                    <div class="field"><label>Academic Field <span style="color:red;">*</span></label><input type="text" name="academic_field" value="{{ old('academic_field', $editApplicant->academic_field ?? '') }}" placeholder="e.g., Civil Engineering" required></div>
                    <div class="field" style="grid-column:span 2;"><label>Education Detail</label><input type="text" name="academic_detail" value="{{ old('academic_detail', $editApplicant->academic_detail ?? '') }}" placeholder="e.g., B.Sc. Degree"></div>
                    <div class="field"><label>Primary Phone</label><input type="text" name="phone_primary" value="{{ old('phone_primary', $editApplicant->phone_primary ?? '') }}" placeholder="09..."></div>
                    <div class="field"><label>Secondary Phone</label><input type="text" name="phone_secondary" value="{{ old('phone_secondary', $editApplicant->phone_secondary ?? '') }}" placeholder="Optional"></div>
                    <div class="flex gap-2 items-end">
                        <button type="submit" class="btn btn-primary">{{ $editApplicant ? '💾 Update' : '➕ Register' }}</button>
                        @if($editApplicant)<a href="{{ route('applicants.index') }}" class="btn btn-light">Cancel</a>@endif
                    </div>
                </form>
            @elseif($canEdit)
                <div class="alert alert-warning">⚠️ No positions available. Add an organization and position first.</div>
            @else
                <div class="alert alert-info">👁️ Viewer access: read-only.</div>
            @endif
        </div>
    </div>

<!-- SECTION 2: WORK EXPERIENCE --><!-- SECTION 2: WORK EXPERIENCE --><!-- SECTION 2: WORK EXPERIENCE -->
    <div id="section-experience" class="section-card">
        <div class="card">
            <div class="card-header"><div><h2>💼 Work Experience Encoder</h2><p class="subtitle">Add work experience records</p></div></div>
            @if($canEdit && $applicants->count() > 0)
                <form method="post" class="form-grid" action="{{ route('applicants.work-experience.store') }}">
                    @csrf
                    <div class="field">
                        <label>Applicant <span style="color:red;">*</span></label>
                        <div class="searchable-wrapper">
                            <input type="text" class="searchable-input" id="work_applicant_search" placeholder="🔍 Search applicant..." autocomplete="off"
                                   onfocus="toggleDropdown('work_applicant', true)" onkeyup="filterDropdown('work_applicant')">
                            <div id="work_applicant_dropdown" class="searchable-dropdown">
                                <div class="searchable-option clear-option" data-value="" data-text="" onclick="selectFromDropdown('work_applicant', '', '', this)">✖ Clear</div>
                                @foreach($applicants as $app)
                                    <div class="searchable-option" data-value="{{ $app->id }}" data-text="{{ $app->full_name }}"
                                         data-subtext="{{ $app->position->name ?? 'No Position' }} | {{ $app->phone_primary ?? '' }}"
                                         onclick="selectFromDropdown('work_applicant', '{{ $app->id }}', '{{ $app->full_name }}', this)">
                                        <strong>{{ $app->full_name }}</strong>
                                        <div class="text-xs text-muted">{{ $app->position->name ?? 'No Position' }} | 📱 {{ $app->phone_primary ?? 'N/A' }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <input type="hidden" name="applicant_id" id="work_applicant" required>
                        </div>
                    </div>
                    <div class="field"><label>Company <span style="color:red;">*</span></label><input type="text" name="company" required></div>
                    <div class="field"><label>Job Title <span style="color:red;">*</span></label><input type="text" name="job_title" required></div>
                    <div class="field"><label>Start Date</label><input type="date" name="start_date"></div>
                    <div class="field"><label>End Date</label><input type="date" name="end_date"></div>
                    <div class="field"><label>Relevant to Position</label><select name="specific_to_position"><option value="0">No</option><option value="1">Yes</option></select></div>
                    <div class="field"><label>Specific Title</label><input type="text" name="specific_position_title"></div>
                    <div class="field" style="grid-column:span 2;"><label>Notes</label><input type="text" name="notes"></div>
                    <div><button type="submit" class="btn btn-primary">➕ Add Experience</button></div>
                </form>
            @elseif($canEdit)
                <div class="alert alert-warning">⚠️ No applicants. <a href="#registration-form" onclick="switchSection('registration')">Register one first</a>.</div>
            @else
                <p class="text-sm text-muted">👁️ Viewer access.</p>
            @endif
            <div class="table-wrap mt-4">
                <table>
                    <thead><tr><th>Position</th><th>Applicant</th><th>Company</th><th>Job Title</th><th>Start</th><th>End</th><th>Duration</th><th>Relevant</th><th class="text-center">Action</th></tr></thead>
                    <tbody>
                        @php $hasExp = false; @endphp
                        @foreach($allWorkExperiences as $experiences)
                            @foreach($experiences as $exp)
                                @php $hasExp = true; @endphp
                                <tr>
                                    <td><span class="badge badge-teal">{{ $exp->applicant->position->name ?? 'N/A' }}</span></td>
                                    <td><strong>{{ $exp->applicant->full_name ?? '' }}</strong></td>
                                    <td>{{ $exp->company }}</td>
                                    <td>{{ $exp->job_title }}</td>
                                    <td>{{ $exp->start_date ? \Carbon\Carbon::parse($exp->start_date)->format('M Y') : '-' }}</td>
                                    <td>{{ $exp->end_date ? \Carbon\Carbon::parse($exp->end_date)->format('M Y') : '-' }}</td>
                                    <td><span class="badge badge-muted">{{ $exp->duration }}</span></td>
                                    <td>@if($exp->specific_to_position)<span class="badge badge-success">✅ {{ $exp->specific_position_title ?: 'Yes' }}</span>@else<span class="badge badge-muted">❌ No</span>@endif</td>
                                    <td class="text-center">
                                        @if($canEdit)
                                            <form method="post" style="display:inline;" action="{{ route('applicants.work-experience.destroy', $exp->id) }}" onsubmit="return confirm('Delete?');">
                                                @csrf @method('DELETE')<button class="btn btn-danger btn-xs">🗑️</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if(!$hasExp)<tr><td colspan="9"><div class="empty-state">📭 No records</div></td></tr>@endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 3: COMMITTEE REVIEW -->
    <div id="section-review" class="section-card">
        <div class="card" id="committee-review">
            <div class="card-header"><div><h2>🎯 Committee Screening & Selection</h2><p class="subtitle">Select an applicant below to review</p></div></div>
            <form method="get" action="{{ route('applicants.index') }}">
                <div class="applicant-list-panel">
                    <div class="applicant-list-header">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <div style="flex:1;"><input type="text" id="review_applicant_search" placeholder="🔍 Type to filter applicants..." autocomplete="off" style="width:100%; padding:10px 14px; border:1px solid #bac8d6; border-radius:6px; font-size:13px;" onkeyup="filterReviewList()" value="{{ $reviewApplicant ? $reviewApplicant->full_name : '' }}"></div>
                            <div style="font-size:12px; color:var(--muted); white-space:nowrap;"><span id="review_count">{{ $applicants->count() }}</span> applicants</div>
                        </div>
                    </div>
                    <div class="applicant-list-body" id="review_list">
                        @foreach($applicants as $app)
                            @php $dec = $app->selection->decision ?? 'Pending'; $dc = ['Selected'=>['bg'=>'#dcf5e7','color'=>'#18794e'],'Rejected'=>['bg'=>'#ffeded','color'=>'#a61b1b'],'Shortlisted'=>['bg'=>'#e6e3ff','color'=>'#4b3bad'],'Reserve'=>['bg'=>'#dff3ff','color'=>'#0d6289']][$dec]??['bg'=>'#fff5e5','color'=>'#a45100']; @endphp
                            <div class="applicant-list-item review-item {{ $reviewApplicant && $reviewApplicant->id == $app->id ? 'selected' : '' }}"
                                 data-id="{{ $app->id }}" data-name="{{ $app->full_name }}"
                                 data-search="{{ strtolower($app->full_name.' '.($app->position->name??'').' '.($app->phone_primary??'')) }}"
                                 onclick="selectReviewItem('{{ $app->id }}', '{{ $app->full_name }}', this)">
                                <div class="info"><div class="name">{{ $loop->iteration }}. {{ $app->full_name }}</div><div class="meta"><span>📌 {{ $app->position->name ?? 'No Position' }}</span><span>📱 {{ $app->phone_primary ?? 'N/A' }}</span><span>📅 {{ $app->registration_date }}</span></div></div>
                                <span class="badge" style="background:{{ $dc['bg'] }}; color:{{ $dc['color'] }}; flex-shrink:0;">{{ $dec }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="review_applicant" id="review_applicant" value="{{ $reviewApplicant->id ?? '' }}">
                <div class="text-right mt-3"><button type="submit" class="btn btn-primary" style="padding:12px 28px;">📂 Load Candidate Review</button></div>
            </form>
            @if($reviewApplicant)
                <div class="highlight-box mt-4" style="border:2px solid var(--accent);">
                    <h3 style="color:var(--accent);">👤 {{ $reviewApplicant->full_name }}</h3>
                    <table>
                        <tr><th>Organization</th><td>{{ $reviewApplicant->position->organization->name??'' }}</td><th>Position</th><td>{{ $reviewApplicant->position->name??'' }}</td></tr>
                        <tr><th>Academic</th><td>{{ $reviewApplicant->academic_level }} — {{ $reviewApplicant->academic_field }}</td><th>Experience</th><td>{{ $reviewApplicant->experience_years }}yr {{ $reviewApplicant->experience_months }}mo</td></tr>
                        <tr><th>Phone</th><td>{{ $reviewApplicant->phone_primary }}{{ $reviewApplicant->phone_secondary?' / '.$reviewApplicant->phone_secondary:'' }}</td><th>Registration</th><td>{{ $reviewApplicant->registration_date }}</td></tr>
                    </table>
                    <h4 class="mt-4">💼 Work Experience</h4>
                    <div class="table-wrap"><table><thead><tr><th>Company</th><th>Job Title</th><th>Start</th><th>End</th><th>Duration</th><th>Relevant</th></tr></thead><tbody>@forelse($reviewExperiences as $exp)<tr><td>{{ $exp->company }}</td><td>{{ $exp->job_title }}</td><td>{{ $exp->start_date??'-' }}</td><td>{{ $exp->end_date??'-' }}</td><td>{{ $exp->duration }}</td><td>{{ $exp->specific_to_position?'✅ Yes':'❌ No' }}</td></tr>@empty<tr><td colspan="6"><div class="empty-state">No records</div></td></tr>@endforelse</tbody></table></div>
                </div>
                <form method="post" class="section-gap" action="{{ route('applicants.review.save') }}">
                    @csrf<input type="hidden" name="applicant_id" value="{{ $reviewApplicant->id }}">
                    <div class="card"><h3>📊 Selection Criteria</h3>
                        <div class="table-wrap"><table><thead><tr><th>Criterion</th><th class="num">Weight</th><th class="num">Max</th><th class="num">Pass</th><th>Score</th><th>Comment</th></tr></thead><tbody>@forelse($reviewCriteria as $c)@php $s=$reviewScores[$c->id]??null; @endphp<tr><td>{{ $c->criterion_name }}</td><td class="num">{{ number_format($c->weight,1) }}</td><td class="num">{{ number_format($c->max_score,1) }}</td><td class="num">{{ number_format($c->pass_mark,1) }}</td><td><input style="width:100px;" type="number" name="criterion_score_{{ $c->id }}" min="0" max="{{ $c->max_score }}" step="0.01" value="{{ old('criterion_score_'.$c->id,$s->score??'') }}" @if(!$canEdit) disabled @endif></td><td><input type="text" name="criterion_comment_{{ $c->id }}" value="{{ old('criterion_comment_'.$c->id,$s->comment??'') }}" @if(!$canEdit) disabled @endif></td></tr>@empty<tr><td colspan="6"><div class="empty-state">No criteria</div></td></tr>@endforelse</tbody></table></div></div>
                    <div class="form-grid section-gap">
                        <div class="field" style="grid-column:span 2;"><label>Committee Members</label><input type="text" name="committee_members" value="{{ old('committee_members',$reviewApplicant->selection->committee_members??'') }}"></div>
                        <div class="field"><label>Review Date</label><input type="date" name="review_date" value="{{ old('review_date',$reviewApplicant->selection->review_date??now()->format('Y-m-d')) }}"></div>
                        <div class="field"><label>Rank</label><input type="number" name="rank_no" min="0" value="{{ old('rank_no',$reviewApplicant->selection->rank_no??0) }}"></div>
                        <div class="field"><label>Decision</label><select name="decision" required>@foreach($decisions as $d)<option value="{{ $d }}" @if(old('decision',$reviewApplicant->selection->decision??'Pending')==$d) selected @endif>{{ $d }}</option>@endforeach</select></div>
                        <div class="field" style="grid-column:span 3;"><label>Remarks</label><textarea name="remarks" rows="2">{{ old('remarks',$reviewApplicant->selection->remarks??'') }}</textarea></div>
                        @if($canEdit && $reviewCriteria->count()>0)<div><button type="submit" class="btn btn-primary">💾 Save Screening</button></div>@endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- SECTION 4: APPLICANT LIST -->
    <div id="section-list" class="section-card">
        <div class="card">
            <div class="card-header"><div><h2>📋 Applicant Details & Status</h2><p class="subtitle">{{ $applicants->count() }} applicants</p></div></div>
            <div class="table-wrap"><table><thead><tr><th>#</th><th>Date</th><th>Organization</th><th>Position</th><th>Full Name</th><th>Academic</th><th>Field</th><th>Phone</th><th class="num">Score</th><th class="num">Rank</th><th>Decision</th><th class="text-center">Actions</th></tr></thead><tbody>@forelse($applicants as $index => $app)@php $dec=$app->selection->decision??'Pending';$badgeMap=['Selected'=>'badge-success','Rejected'=>'badge-danger','Shortlisted'=>'badge-purple','Reserve'=>'badge-info'];$badge=$badgeMap[$dec]??'badge-warning';@endphp<tr><td>{{ $index+1 }}</td><td>{{ $app->registration_date }}</td><td>{{ $app->position->organization->name??'' }}</td><td>{{ $app->position->name??'' }}</td><td><strong>{{ $app->full_name }}</strong></td><td>{{ $app->academic_level }}</td><td>{{ $app->academic_field }}</td><td>{{ $app->phone_primary }}</td><td class="num">{{ number_format($app->selection->final_score??0,2) }}</td><td class="num">{{ $app->selection->rank_no??'-' }}</td><td><span class="badge {{ $badge }}">{{ $dec }}</span></td><td class="text-center">@if($canEdit)<div class="flex gap-2 items-center" style="justify-content:center;"><a href="{{ route('applicants.index',['edit_registration'=>$app->id]) }}#registration-form" class="btn btn-light btn-xs">✏️</a><a href="{{ route('applicants.index',['review_applicant'=>$app->id]) }}#committee-review" class="btn btn-blue btn-xs">📋</a><a href="{{ url('/applicants/profile/'.$app->id) }}" class="btn btn-orange btn-xs">📄</a><form method="post" style="display:inline;" action="{{ route('applicants.destroy',$app->id) }}" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')<button class="btn btn-danger btn-xs">🗑️</button></form></div>@endif</td></tr>@empty<tr><td colspan="12"><div class="empty-state">📭 No records</div></td></tr>@endforelse</tbody></table></div>
        </div>
    </div>

    <!-- SECTION 5: EXPERIENCE CALCULATION & RANKING (NEW) -->
    <div id="section-calculation" class="section-card">
        <!-- Experience Calculation -->
        <div class="card">
            <div class="card-header"><div><h2>📊 Experience Calculation</h2><p class="subtitle">Auto-calculate total and specific experience from work records</p></div></div>
            <div style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                <div class="field" style="min-width:250px;">
                    <label>Select Applicant</label>
                    <select id="experience_applicant" onchange="calculateExperience()">
                        <option value="">-- Select Applicant --</option>
                        @foreach($applicants as $app)
                            <option value="{{ $app->id }}">{{ $app->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn" style="background:#397a9f;" onclick="calculateExperience()">🔄 Calculate</button>
            </div>
            <div id="experience_result" style="display:none; margin-top:16px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="exp-result-card" style="border-left:4px solid #1b7f79;">
                        <h4 style="margin:0 0 8px;">📈 Total Experience</h4>
                        <div id="total_experience_display" class="exp-value" style="color:#1b7f79;">-</div>
                        <div class="text-sm text-muted">All work experience combined</div>
                    </div>
                    <div class="exp-result-card" style="border-left:4px solid #397a9f;">
                        <h4 style="margin:0 0 8px;">🎯 Specific Experience</h4>
                        <div id="specific_experience_display" class="exp-value" style="color:#397a9f;">-</div>
                        <div class="text-sm text-muted">Relevant to applied position</div>
                    </div>
                </div>
                <div style="margin-top:16px;"><h4>💼 Work Experience Details</h4><div id="experience_details" class="table-wrap"></div></div>
            </div>
        </div>

        <!-- Ranking -->
        <div class="card section-gap">
            <div class="card-header"><div><h2>🏆 Candidate Ranking</h2><p class="subtitle">Auto-ranking based on final scores</p></div></div>
            <div style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                <div class="field" style="min-width:250px;">
                    <label>Select Position</label>
                    <select id="ranking_position" onchange="getRanking()">
                        <option value="">-- Select Position --</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn" style="background:#d38b2a;" onclick="getRanking()">📊 Show Ranking</button>
                <button type="button" class="btn btn-secondary" onclick="updateRanks()">🔄 Update Ranks</button>
            </div>
            <div id="ranking_result" style="display:none; margin-top:16px;">
                <div class="table-wrap"><table><thead><tr><th>Rank</th><th>Candidate</th><th>Position</th><th class="num">Final Score</th><th>Decision</th></tr></thead><tbody id="ranking_body"></tbody></table></div>
            </div>
        </div>
    </div>

    <!-- SECTION 6: POSITIONS -->
    <div id="section-positions" class="section-card">
        @if($canEdit)
            <div class="card"><div class="card-header"><h2>🏢 Organizations</h2></div><form method="post" class="flex gap-2 items-end" action="{{ route('applicants.organizations.store') }}">@csrf<div class="field" style="flex:1;"><label>Name</label><input type="text" name="name" required></div><button type="submit" class="btn btn-primary">Add</button></form><div class="table-wrap mt-3"><table><thead><tr><th>Organization</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($organizations as $org)<tr><td><strong>{{ $org->name }}</strong></td><td><span class="badge {{ $org->active?'badge-success':'badge-muted' }}">{{ $org->active?'Active':'Inactive' }}</span></td><td><form method="post" style="display:inline;" action="{{ route('applicants.organizations.destroy',$org->id) }}">@csrf @method('DELETE')<button class="btn btn-danger btn-xs">🗑️</button></form></td></tr>@empty<tr><td colspan="3"><div class="empty-state">No organizations</div></td></tr>@endforelse</tbody></table></div></div>
            <div class="card section-gap"><div class="card-header"><h2>➕ Add Position</h2></div><form method="post" class="form-grid" action="{{ route('applicants.positions.store') }}">@csrf<div class="field"><label>Organization <span style="color:red;">*</span></label><select name="organization_id" required>@foreach($activeOrganizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></div><div class="field"><label>Name <span style="color:red;">*</span></label><input type="text" name="name" required></div><div class="field"><label>Grade</label><input type="text" name="grade"></div><div class="field"><label>Salary</label><input type="text" name="salary"></div><div class="field"><label>Type</label><input type="text" name="requirement_type"></div><div class="field" style="grid-column:span 3;"><label>Criteria</label><textarea name="criteria" rows="2"></textarea></div><div><button type="submit" class="btn btn-primary">Add</button></div></form></div>
            <div class="card section-gap" style="border:2px solid var(--blue);"><div class="card-header"><h2>✏️ Update Position</h2></div><form method="POST" action="{{ route('applicants.positions.update') }}" class="form-grid">@csrf @method('PUT')<div class="field"><label>Position <span style="color:red;">*</span></label><select name="position_id" id="update_position_select" required onchange="autoFillPosition(this)">@foreach($positions as $pos)<option value="{{ $pos->id }}" data-org="{{ $pos->organization_id }}" data-name="{{ $pos->name }}" data-grade="{{ $pos->grade }}" data-salary="{{ $pos->salary }}" data-req="{{ $pos->requirement_type }}" data-crit="{{ $pos->criteria }}">{{ $pos->organization->name??'' }} — {{ $pos->name }}</option>@endforeach</select></div><div class="field"><label>Organization</label><select name="organization_id" id="update_org_id" required>@foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></div><div class="field"><label>Name</label><input type="text" name="name" id="update_name" required></div><div class="field"><label>Grade</label><input type="text" name="grade" id="update_grade"></div><div class="field"><label>Salary</label><input type="text" name="salary" id="update_salary"></div><div class="field"><label>Type</label><input type="text" name="requirement_type" id="update_req"></div><div class="field" style="grid-column:span 3;"><label>Criteria</label><textarea name="criteria" id="update_crit" rows="2"></textarea></div><div class="flex gap-2"><button type="submit" class="btn btn-blue">Update</button><button type="reset" class="btn btn-light">Clear</button></div></form></div>
            <div class="card section-gap"><div class="card-header"><h2>📋 Positions ({{ $positions->count() }})</h2></div><div class="table-wrap"><table><thead><tr><th>Organization</th><th>Position</th><th>Grade</th><th>Salary</th><th>Type</th><th>Action</th></tr></thead><tbody>@forelse($positions as $pos)<tr><td>{{ $pos->organization->name??'' }}</td><td><strong>{{ $pos->name }}</strong></td><td>{{ $pos->grade?:'-' }}</td><td>{{ $pos->salary?:'-' }}</td><td>{{ $pos->requirement_type?:'-' }}</td><td><form method="post" style="display:inline;" action="{{ route('applicants.positions.destroy',$pos->id) }}">@csrf @method('DELETE')<button class="btn btn-danger btn-xs">🗑️</button></form></td></tr>@empty<tr><td colspan="6"><div class="empty-state">No positions</div></td></tr>@endforelse</tbody></table></div></div>
        @else
            <div class="card"><h2>🔒 Settings</h2><p class="text-sm text-muted">Only editors and admins can manage.</p></div>
        @endif
    </div>

    <!-- SECTION 7: CRITERIA -->
    <div id="section-criteria" class="section-card">
        <div class="card"><div class="card-header"><h2>📊 Selection Criteria</h2></div>@if($canEdit)<form method="post" class="form-grid" action="{{ route('applicants.criteria.store') }}">@csrf<div class="field"><label>Organization <span style="color:red;">*</span></label><select name="organization_id" required>@foreach($activeOrganizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></div><div class="field"><label>Position Scope</label><select name="criterion_position_id"><option value="">All</option>@foreach($positions as $pos)<option value="{{ $pos->id }}">{{ $pos->organization->name??'' }} — {{ $pos->name }}</option>@endforeach</select></div><div class="field"><label>Name <span style="color:red;">*</span></label><input type="text" name="criterion_name" required></div><div class="field"><label>Weight %</label><input type="number" name="weight" min="0.01" step="0.01" value="25" required></div><div class="field"><label>Max Score</label><input type="number" name="max_score" min="0.01" step="0.01" value="100" required></div><div class="field"><label>Pass Mark</label><input type="number" name="pass_mark" min="0" step="0.01" value="50"></div><div class="field"><label>Order</label><input type="number" name="display_order" min="1" value="1"></div><div><button type="submit" class="btn btn-primary">Add</button></div></form><div class="table-wrap mt-3"><table><thead><tr><th>Org</th><th>Scope</th><th>Criterion</th><th class="num">Weight</th><th class="num">Max</th><th class="num">Pass</th><th>Action</th></tr></thead><tbody>@php $allCrit=\App\Models\SelectionCriterion::with(['organization','position'])->get(); @endphp@forelse($allCrit as $c)<tr><td>{{ $c->organization->name??'' }}</td><td>{{ $c->position->name??'All' }}</td><td>{{ $c->criterion_name }}</td><td class="num">{{ number_format($c->weight,1) }}</td><td class="num">{{ number_format($c->max_score,1) }}</td><td class="num">{{ number_format($c->pass_mark,1) }}</td><td><form method="post" style="display:inline;" action="{{ route('applicants.criteria.destroy',$c->id) }}">@csrf @method('DELETE')<button class="btn btn-danger btn-xs">🗑️</button></form></td></tr>@empty<tr><td colspan="7"><div class="empty-state">No criteria</div></td></tr>@endforelse</tbody></table></div>@else<p class="text-sm text-muted">Only editors/admins can manage.</p>@endif</div>
    </div>

    <!-- SECTION 8: REPORTS -->
    <div id="section-reports" class="section-card">
        <div class="card" id="report-parameters"><div class="card-header"><h2>📊 Reports</h2></div><form method="get" action="{{ route('applicants.index') }}"><input type="hidden" name="run" value="1"><div class="form-grid-3"><div class="field"><label>Organization</label><select name="organization"><option value="0">All</option>@foreach($organizations as $org)<option value="{{ $org->id }}" @if(request('organization')==$org->id) selected @endif>{{ $org->name }}</option>@endforeach</select></div><div class="field"><label>Position</label><select name="position"><option value="0">All</option>@foreach($positions as $pos)<option value="{{ $pos->id }}" @if(request('position')==$pos->id) selected @endif>{{ $pos->organization->name??'' }} — {{ $pos->name }}</option>@endforeach</select></div><div class="field"><label>From</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div><div class="field"><label>To</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div><div class="field"><label>Academic</label><select name="academic"><option value="All">All</option>@foreach($academicLevels as $lvl)<option value="{{ $lvl }}" @if(request('academic')==$lvl) selected @endif>{{ $lvl }}</option>@endforeach</select></div><div class="field"><label>Decision</label><select name="decision"><option value="All">All</option>@foreach($decisions as $d)<option value="{{ $d }}" @if(request('decision')==$d) selected @endif>{{ $d }}</option>@endforeach</select></div></div><div class="flex gap-2 mt-3"><button type="submit" class="btn btn-primary">🔄 Update</button><a href="{{ route('applicants.index') }}" class="btn btn-light">Clear</a></div></form>@if(request('run')==1)<div class="highlight-box mt-3"><h4>📥 Download</h4><div class="flex gap-2 flex-wrap mt-2"><a href="{{ route('applicants.export',request()->all()) }}" class="btn btn-teal">📋 Register Excel</a><a href="{{ route('applicants.export-selection',request()->all()) }}" class="btn btn-blue">🎯 Selection Excel</a><a href="{{ route('applicants.export-profile',request()->all()) }}" class="btn btn-orange">📄 Profile Excel</a></div><p class="text-xs text-muted mt-2">{{ $applicants->count() }} applicants match.</p></div>@endif</div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function toggleDropdown(id, show) {
            const dd = document.getElementById(id + '_dropdown');
            if (dd) { if (show) { document.querySelectorAll('.searchable-dropdown').forEach(d => d.classList.remove('visible')); dd.classList.add('visible'); filterDropdown(id); } else { dd.classList.remove('visible'); } }
        }
        function filterDropdown(id) {
            const input = document.getElementById(id + '_search'), term = input.value.toLowerCase().trim(), dd = document.getElementById(id + '_dropdown'), opts = dd.querySelectorAll('.searchable-option:not(.clear-option)');
            opts.forEach(opt => { const text = (opt.getAttribute('data-text') + ' ' + opt.getAttribute('data-subtext')).toLowerCase(); opt.style.display = (term === '' || text.includes(term)) ? 'block' : 'none'; if (term !== '' && text.includes(term)) { const nameEl = opt.querySelector('strong'); if (nameEl) nameEl.innerHTML = highlight(opt.getAttribute('data-text'), term); } else { const nameEl = opt.querySelector('strong'); if (nameEl) nameEl.textContent = opt.getAttribute('data-text'); } });
        }
        function highlight(text, term) { const regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'); return text.replace(regex, '<span class="searchable-highlight">$1</span>'); }
        function selectFromDropdown(id, value, text, el) { document.getElementById(id).value = value; document.getElementById(id + '_search').value = text; const dd = document.getElementById(id + '_dropdown'); dd.querySelectorAll('.searchable-option').forEach(o => o.classList.remove('selected')); if (el) el.classList.add('selected'); dd.classList.remove('visible'); }
        function filterReviewList() { const input = document.getElementById('review_applicant_search'), term = input.value.toLowerCase().trim(), items = document.querySelectorAll('.review-item'), countEl = document.getElementById('review_count'); let count = 0; items.forEach(item => { if (term === '' || item.getAttribute('data-search').includes(term)) { item.style.display = 'flex'; count++; } else { item.style.display = 'none'; } }); if (countEl) countEl.textContent = count; }
        function selectReviewItem(id, name, el) { document.getElementById('review_applicant').value = id; document.getElementById('review_applicant_search').value = name; document.querySelectorAll('.review-item').forEach(item => item.classList.remove('selected')); if (el) { el.classList.add('selected'); el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } }
        function switchSection(sectionId) { document.querySelectorAll('.section-card').forEach(el => el.classList.remove('active')); const target = document.getElementById('section-' + sectionId); if (target) target.classList.add('active'); document.querySelectorAll('.applicant-nav a').forEach(el => { el.classList.remove('active'); if (el.getAttribute('href') === '#section-' + sectionId) el.classList.add('active'); }); history.pushState(null, null, '#section-' + sectionId); document.getElementById('mainNav')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        function autoFillPosition(sel) { const opt = sel.options[sel.selectedIndex]; if (opt.value) { document.getElementById('update_org_id').value = opt.dataset.org || ''; document.getElementById('update_name').value = opt.dataset.name || ''; document.getElementById('update_grade').value = opt.dataset.grade || ''; document.getElementById('update_salary').value = opt.dataset.salary || ''; document.getElementById('update_req').value = opt.dataset.req || ''; document.getElementById('update_crit').value = opt.dataset.crit || ''; } }
        
        // Close dropdowns on outside click
        document.addEventListener('click', function(e) { document.querySelectorAll('.searchable-wrapper').forEach(w => { if (!w.contains(e.target)) { const dd = w.querySelector('.searchable-dropdown'); if (dd) dd.classList.remove('visible'); } }); });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) { if (!document.activeElement?.classList.contains('searchable-input')) return; const id = document.activeElement.id.replace('_search', ''), dd = document.getElementById(id + '_dropdown'); if (!dd || !dd.classList.contains('visible')) return; const visible = Array.from(dd.querySelectorAll('.searchable-option')).filter(o => o.style.display !== 'none'), currentIdx = visible.findIndex(o => o.classList.contains('selected')); if (e.key === 'Escape') { dd.classList.remove('visible'); document.activeElement.blur(); } else if (e.key === 'Enter') { e.preventDefault(); if (currentIdx >= 0) { const sel = visible[currentIdx]; selectFromDropdown(id, sel.getAttribute('data-value'), sel.getAttribute('data-text'), sel); } } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); dd.classList.add('visible'); const next = e.key === 'ArrowDown' ? (currentIdx < visible.length - 1 ? currentIdx + 1 : 0) : (currentIdx > 0 ? currentIdx - 1 : visible.length - 1); visible.forEach(o => o.classList.remove('selected')); if (visible[next]) { visible[next].classList.add('selected'); visible[next].scrollIntoView({block:'nearest'}); } } });
        
        // Experience Calculation
        function calculateExperience() { const applicantId = document.getElementById('experience_applicant').value; if (!applicantId) { alert('Please select an applicant.'); return; } fetch('/applicants/experience/' + applicantId).then(r => r.json()).then(data => { if (data.success) { document.getElementById('experience_result').style.display = 'block'; document.getElementById('total_experience_display').textContent = data.total_experience.formatted; document.getElementById('specific_experience_display').textContent = data.specific_experience.formatted; let html = '<table><thead><tr><th>Company</th><th>Job Title</th><th>Duration</th><th>Start</th><th>End</th><th>Relevant</th></tr></thead><tbody>'; data.work_experiences.forEach(function(exp) { html += '<tr><td><strong>' + exp.company + '</strong></td><td>' + exp.job_title + '</td><td>' + exp.duration + '</td><td>' + exp.start_date + '</td><td>' + exp.end_date + '</td><td>' + (exp.specific ? '✅ Yes' : '❌ No') + '</td></tr>'; }); html += '</tbody></table>'; document.getElementById('experience_details').innerHTML = html; } }).catch(e => { alert('Failed to calculate experience.'); }); }
        
        // Ranking
        function getRanking() { const positionId = document.getElementById('ranking_position').value; if (!positionId) { alert('Please select a position.'); return; } fetch('/applicants/ranking/' + positionId).then(r => r.json()).then(data => { if (data.success) { document.getElementById('ranking_result').style.display = 'block'; const tbody = document.getElementById('ranking_body'); tbody.innerHTML = ''; data.ranking.forEach(function(c) { let rankDisplay = c.rank; if (c.rank === 1) rankDisplay = '🥇 1'; else if (c.rank === 2) rankDisplay = '🥈 2'; else if (c.rank === 3) rankDisplay = '🥉 3'; const row = document.createElement('tr'); row.innerHTML = '<td><strong>' + rankDisplay + '</strong></td><td><strong>' + c.name + '</strong></td><td>' + c.position + '</td><td class="num"><strong>' + c.final_score.toFixed(2) + '</strong></td><td><span class="badge badge-status ' + c.decision.toLowerCase() + '">' + c.decision + '</span></td>'; tbody.appendChild(row); }); } }).catch(e => { alert('Failed to get ranking.'); }); }
        function updateRanks() { const positionId = document.getElementById('ranking_position').value; if (!positionId) { alert('Please select a position.'); return; } if (!confirm('Update ranks for all candidates?')) return; fetch('/applicants/ranks/update/' + positionId, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }).then(r => r.json()).then(data => { if (data.success) { alert(data.message); getRanking(); } }).catch(e => { alert('Failed to update ranks.'); }); }
        
        // Init
        document.addEventListener('DOMContentLoaded', function() { const hash = window.location.hash; if (hash) { const sectionId = hash.replace('#section-', ''); if (sectionId) switchSection(sectionId); } @if($editApplicant && $editApplicant->position) document.getElementById('position').value = "{{ $editApplicant->position_id }}"; document.getElementById('position_search').value = "{{ $editApplicant->position->organization->name ?? '' }} — {{ $editApplicant->position->name }}"; @endif });
    </script>
@endsection
