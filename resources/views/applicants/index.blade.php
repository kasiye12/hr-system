@extends('layouts.app')

@section('title', 'Applicant Registration - TNT HR')

@section('content')
    <style>
        /* ============================================
           CUSTOM STYLES FOR APPLICANT PAGE
           ============================================ */
        .applicant-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
            background: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #dce5ee;
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
        .applicant-nav a:hover {
            background: #e8f3f2;
            color: #0c625e;
        }
        .applicant-nav a.active {
            background: #1b7f79;
            color: #fff;
        }
        .applicant-nav a .badge-count {
            background: #e8f3f2;
            color: #0c625e;
            font-size: 10px;
            padding: 1px 8px;
            border-radius: 999px;
            font-weight: 700;
        }
        .applicant-nav a.active .badge-count {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }
        .section-card {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .section-card.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .quick-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .quick-actions .btn {
            font-size: 12px;
            padding: 6px 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .stats-grid .stat-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: center;
        }
        .stats-grid .stat-card .number {
            font-size: 22px;
            font-weight: 700;
            color: #16324f;
        }
        .stats-grid .stat-card .label {
            font-size: 11px;
            color: #627386;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-top: 2px;
        }
        .stats-grid .stat-card.highlight {
            background: #dfeeea;
            border-color: #1b7f79;
        }
        .stats-grid .stat-card.highlight .number {
            color: #0c5d58;
        }
        .form-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            align-items: end;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            align-items: end;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            align-items: end;
        }
        @media (max-width: 768px) {
            .form-grid-4 { grid-template-columns: 1fr 1fr; }
            .form-grid-3 { grid-template-columns: 1fr 1fr; }
            .form-grid-2 { grid-template-columns: 1fr; }
            .applicant-nav { flex-direction: column; }
            .applicant-nav a { width: 100%; justify-content: center; }
        }
        @media (max-width: 480px) {
            .form-grid-4 { grid-template-columns: 1fr; }
            .form-grid-3 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        .table-wrap { overflow-x: auto; margin-top: 12px; }
        .table-wrap table { min-width: 700px; }
        .badge-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-status.active { background: #dcf5e7; color: #12643c; }
        .badge-status.inactive { background: #eee; color: #666; }
        .badge-status.pending { background: #fff5e5; color: #a45100; }
        .badge-status.selected { background: #dcf5e7; color: #12643c; }
        .badge-status.rejected { background: #ffeded; color: #a61b1b; }
        .badge-status.shortlisted { background: #e6e3ff; color: #4b3bad; }
        .badge-status.reserve { background: #dff3ff; color: #0d6289; }
        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .section-title h2 {
            margin: 0;
            font-size: 18px;
        }
        .section-title .subtitle {
            font-size: 13px;
            color: #627386;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #627386;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state h4 { margin: 0 0 4px; color: #24384c; }
        .empty-state p { margin: 0; font-size: 13px; }
        .inline-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        .inline-form .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .inline-form .field label {
            font-size: 12px;
            color: #627386;
            font-weight: 700;
        }
        .inline-form .field input,
        .inline-form .field select {
            padding: 8px 12px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 13px;
            min-width: 140px;
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 11px;
        }
        .btn-orange { background: #d38b2a; color: #fff; }
        .btn-orange:hover { background: #b87820; color: #fff; }
        .btn-teal { background: #1b7f79; color: #fff; }
        .btn-teal:hover { background: #156b66; color: #fff; }
        .btn-blue { background: #397a9f; color: #fff; }
        .btn-blue:hover { background: #2d6383; color: #fff; }
        .highlight-box {
            background: #f8fbfd;
            border: 1px solid #dce5ee;
            border-radius: 8px;
            padding: 16px;
        }
        .applicant-form .field input,
        .applicant-form .field select,
        .applicant-form .field textarea {
            width: 100%;
            min-width: 0;
        }
        .applicant-form .field textarea {
            padding: 8px 10px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            min-height: 50px;
        }
        .applicant-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            align-items: end;
        }
        @media (max-width: 768px) {
            .applicant-form { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .applicant-form { grid-template-columns: 1fr; }
        }
        .section-gap { margin-top: 16px; }
    </style>

    <!-- ============================================ -->
    <!-- PAGE HEADER -->
    <!-- ============================================ -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:8px;">
        <div>
            <h1 style="margin:0;">📝 Applicant Registration</h1>
            <div class="subtitle" style="margin-top:2px;">Manage applicants, work experience, committee reviews, and selection decisions</div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @if($canEdit)
                <a href="#registration-form" class="btn btn-teal" onclick="switchSection('registration')">➕ New Applicant</a>
            @endif
            <a href="#report-parameters" class="btn btn-blue" onclick="switchSection('reports')">📊 Reports</a>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- QUICK STATS -->
    <!-- ============================================ -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number">{{ number_format($stats['total']) }}</div>
            <div class="label">📊 Total Applicants</div>
        </div>
        <div class="stat-card">
            <div class="number">{{ number_format($stats['degree_plus']) }}</div>
            <div class="label">🎓 Degree & Above</div>
        </div>
        <div class="stat-card highlight">
            <div class="number">{{ number_format($stats['selected']) }}</div>
            <div class="label">✅ Selected</div>
        </div>
        <div class="stat-card">
            <div class="number">{{ number_format($stats['positions']) }}</div>
            <div class="label">📌 Positions</div>
        </div>
        <div class="stat-card">
            <div class="number">{{ number_format($applicants->count()) }}</div>
            <div class="label">👥 Active Applicants</div>
        </div>
        <div class="stat-card">
            <div class="number">{{ number_format($organizations->count()) }}</div>
            <div class="label">🏢 Organizations</div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- NAVIGATION TABS -->
    <!-- ============================================ -->
    <div class="applicant-nav" id="mainNav">
        <a href="#section-registration" class="active" onclick="switchSection('registration')">
            📝 Register
            <span class="badge-count">New</span>
        </a>
        <a href="#section-experience" onclick="switchSection('experience')">
            💼 Experience
            <span class="badge-count">{{ $allWorkExperiences->sum(function($e) { return $e->count(); }) }}</span>
        </a>
        <a href="#section-review" onclick="switchSection('review')">
            🎯 Committee Review
        </a>
        <a href="#section-list" onclick="switchSection('list')">
            📋 Applicant List
            <span class="badge-count">{{ $applicants->count() }}</span>
        </a>
        <a href="#section-positions" onclick="switchSection('positions')">
            📌 Positions
            <span class="badge-count">{{ $positions->count() }}</span>
        </a>
        <a href="#section-criteria" onclick="switchSection('criteria')">
            📊 Criteria
            <span class="badge-count">{{ \App\Models\SelectionCriterion::count() }}</span>
        </a>
        <a href="#section-reports" onclick="switchSection('reports')">
            📈 Reports
        </a>
    </div>

    <!-- ============================================ -->
    <!-- SECTION: REGISTRATION -->
    <!-- ============================================ -->
    <div id="section-registration" class="section-card active">
        <div class="card" id="registration-form">
            <div class="section-title">
                <h2>{{ $editApplicant ? '✏️ Update Applicant Registration' : '📋 Register New Applicant' }}</h2>
                <span class="subtitle">Fill in the applicant details below</span>
            </div>
            
            @if($canEdit && isset($activePositions) && $activePositions->count() > 0)
                <form method="post" class="applicant-form" action="{{ $editApplicant ? route('applicants.update', $editApplicant) : route('applicants.store') }}">
                    @csrf
                    @if($editApplicant) @method('PUT') @endif
                    
                    <div class="field">
                        <label>Application Position <span style="color:red;">*</span></label>
                        <select name="position_id" required>
                            <option value="">Select Position</option>
                            @foreach($activePositions as $position)
                                <option value="{{ $position->id }}" 
                                    @if($editApplicant && $editApplicant->position_id == $position->id) selected @endif
                                    @if(old('position_id') == $position->id) selected @endif>
                                    {{ $position->organization->name ?? '' }} — {{ $position->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>First Name <span style="color:red;">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $editApplicant->first_name ?? '') }}" required>
                    </div>
                    <div class="field">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $editApplicant->middle_name ?? '') }}">
                    </div>
                    <div class="field">
                        <label>Surname <span style="color:red;">*</span></label>
                        <input type="text" name="surname" value="{{ old('surname', $editApplicant->surname ?? '') }}" required>
                    </div>
                    <div class="field">
                        <label>Registration Date <span style="color:red;">*</span></label>
                        <input type="date" name="registration_date" value="{{ old('registration_date', $editApplicant->registration_date ?? now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="field">
                        <label>Graduation Date</label>
                        <input type="date" name="graduation_date" value="{{ old('graduation_date', $editApplicant->graduation_date ?? '') }}">
                    </div>
                    <div class="field">
                        <label>Experience - Years <span style="color:red;">*</span></label>
                        <input type="number" name="experience_years" min="0" step="1" value="{{ old('experience_years', $editApplicant->experience_years ?? 0) }}" required>
                    </div>
                    <div class="field">
                        <label>Experience - Months <span style="color:red;">*</span></label>
                        <input type="number" name="experience_months" min="0" max="11" step="1" value="{{ old('experience_months', $editApplicant->experience_months ?? 0) }}" required>
                    </div>
                    <div class="field">
                        <label>Academic Level <span style="color:red;">*</span></label>
                        <select name="academic_level" required>
                            @foreach($academicLevels as $level)
                                <option value="{{ $level }}" @if(old('academic_level', $editApplicant->academic_level ?? 'First Degree') == $level) selected @endif>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Academic Field <span style="color:red;">*</span></label>
                        <input type="text" name="academic_field" value="{{ old('academic_field', $editApplicant->academic_field ?? '') }}" placeholder="Example: Accounting or Civil Engineering" required>
                    </div>
                    <div class="field" style="grid-column:span 2;">
                        <label>Qualification / Education Detail</label>
                        <input type="text" name="academic_detail" value="{{ old('academic_detail', $editApplicant->academic_detail ?? '') }}" placeholder="Example: B.Sc. Degree, University name or specialization">
                    </div>
                    <div class="field">
                        <label>Primary Phone</label>
                        <input type="text" name="phone_primary" value="{{ old('phone_primary', $editApplicant->phone_primary ?? '') }}" placeholder="09... or +251...">
                    </div>
                    <div class="field">
                        <label>Secondary Phone</label>
                        <input type="text" name="phone_secondary" value="{{ old('phone_secondary', $editApplicant->phone_secondary ?? '') }}" placeholder="Optional">
                    </div>
                    <div>
                        <button type="submit">{{ $editApplicant ? 'Update Applicant' : 'Register Applicant' }}</button>
                    </div>
                    @if($editApplicant)
                        <div>
                            <a class="btn light" href="{{ route('applicants.index') }}">Cancel Edit</a>
                        </div>
                    @endif
                </form>
            @elseif($canEdit)
                <div class="alert warning">
                    <strong>⚠️ No positions available.</strong><br>
                    Please add an organization and position using the forms below before registering applicants.
                </div>
            @else
                <div class="alert warning">👁️ Viewer access: applicant registration is read-only.</div>
            @endif
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECTION: WORK EXPERIENCE -->
    <!-- ============================================ -->
    <div id="section-experience" class="section-card">
        <div class="card">
            <div class="section-title">
                <h2>💼 Applicant Work Experience Encoder</h2>
                <span class="subtitle">Add work experience records for registered applicants</span>
            </div>
            
            @if($canEdit && $applicants->count() > 0)
                <form method="post" class="applicant-form" action="{{ route('applicants.work-experience.store') }}">
                    @csrf
                    <div class="field">
                        <label>Registered Applicant <span style="color:red;">*</span></label>
                        <select name="applicant_id" required>
                            <option value="">Select Applicant</option>
                            @foreach($applicants as $applicant)
                                <option value="{{ $applicant->id }}">{{ $applicant->full_name }} ({{ $applicant->position->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Company <span style="color:red;">*</span></label>
                        <input type="text" name="company" placeholder="e.g., ABC Construction" required>
                    </div>
                    <div class="field">
                        <label>Job Title <span style="color:red;">*</span></label>
                        <input type="text" name="job_title" placeholder="e.g., Site Engineer" required>
                    </div>
                    <div class="field">
                        <label>Start Date</label>
                        <input type="date" name="start_date">
                    </div>
                    <div class="field">
                        <label>End Date</label>
                        <input type="date" name="end_date">
                    </div>
                    <div class="field">
                        <label>Specific to Position</label>
                        <select name="specific_to_position">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Specific Position Title</label>
                        <input type="text" name="specific_position_title" placeholder="Example: Office Engineer">
                    </div>
                    <div class="field" style="grid-column:span 2;">
                        <label>Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="Additional details about this experience">
                    </div>
                    <div>
                        <button type="submit">➕ Add Work Experience</button>
                    </div>
                </form>
            @elseif($canEdit && $applicants->count() == 0)
                <div class="alert warning">
                    <strong>⚠️ No applicants registered.</strong><br>
                    Please <a href="#registration-form" style="color:#1b7f79; font-weight:600;">register an applicant</a> first before adding work experience.
                </div>
            @else
                <div class="small">👁️ Viewer access: work experience is read-only.</div>
            @endif

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Applied Position</th>
                            <th>Applicant</th>
                            <th>Company</th>
                            <th>Work Experience</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Duration</th>
                            <th>Specific Position</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasExperience = false; @endphp
                        @foreach($allWorkExperiences as $applicantId => $experiences)
                            @foreach($experiences as $exp)
                                @php $hasExperience = true; @endphp
                                <tr>
                                    <td><span class="badge" style="background:#e8f3f2; color:#0c625e;">{{ $exp->applicant->position->name ?? 'N/A' }}</span></td>
                                    <td><strong>{{ $exp->applicant->full_name ?? '' }}</strong></td>
                                    <td><strong>{{ $exp->company }}</strong></td>
                                    <td>{{ $exp->job_title }}</td>
                                    <td>{{ $exp->start_date ? \Carbon\Carbon::parse($exp->start_date)->format('M d, Y') : '-' }}</td>
                                    <td>{{ $exp->end_date ? \Carbon\Carbon::parse($exp->end_date)->format('M d, Y') : '-' }}</td>
                                    <td><span class="badge" style="background:#dce5ee; color:#253b50;">{{ $exp->duration }}</span></td>
                                    <td>@if($exp->specific_to_position)<span class="badge" style="background:#dcf5e7; color:#12643c;">✅ {{ $exp->specific_position_title ?: 'Yes' }}</span>@else<span class="badge" style="background:#eee; color:#666;">❌ No</span>@endif</td>
                                    <td style="text-align:center;">
                                        @if($canEdit)
                                            <form method="post" style="display:inline;" action="{{ route('applicants.work-experience.destroy', $exp->id) }}" onsubmit="return confirm('Delete this work experience record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn danger btn-sm" type="submit">🗑️</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if(!$hasExperience)
                            <tr><td colspan="9"><div class="empty-state"><div class="icon">📭</div><h4>No work experience records</h4><p>Add work experience using the form above.</p></div></td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECTION: COMMITTEE REVIEW -->
    <!-- ============================================ -->
    <div id="section-review" class="section-card">
        <div class="card" id="committee-review">
            <div class="section-title">
                <h2>🎯 Recruitment Committee Screening / Selection Form</h2>
                <span class="subtitle">Pull applicant information from registration record</span>
            </div>

            <form method="get" action="{{ route('applicants.index') }}" class="inline-form">
                <div class="field" style="min-width:300px;">
                    <label>Select an Existing Registered Applicant</label>
                    <select name="review_applicant" required>
                        <option value="">Select Applicant</option>
                        @foreach($applicants as $applicant)
                            <option value="{{ $applicant->id }}" @if($reviewApplicant && $reviewApplicant->id == $applicant->id) selected @endif>
                                {{ $applicant->full_name }} ({{ $applicant->position->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit">📂 Load Candidate Review</button>
            </form>

            @if($reviewApplicant)
                <div class="highlight-box" style="margin-top:14px;">
                    <h3>👤 Candidate Profile</h3>
                    <table>
                        <tbody>
                            <tr><th>Organization</th><td>{{ $reviewApplicant->position->organization->name ?? '' }}</td><th>Application Position</th><td>{{ $reviewApplicant->position->name ?? '' }}</td><th>Registration Date</th><td>{{ $reviewApplicant->registration_date }}</td></tr>
                            <tr><th>Applicant Name</th><td><strong>{{ $reviewApplicant->full_name }}</strong></td><th>Academic Background</th><td>{{ $reviewApplicant->academic_level }} — {{ $reviewApplicant->academic_field }}</td><th>Graduation Date</th><td>{{ $reviewApplicant->graduation_date ?? '-' }}</td></tr>
                            <tr><th>Total Experience</th><td>{{ $reviewApplicant->experience_years }} year(s), {{ $reviewApplicant->experience_months }} month(s)</td><th>Cell Phone</th><td colspan="3">{{ $reviewApplicant->phone_primary }} {{ $reviewApplicant->phone_secondary ? '/ ' . $reviewApplicant->phone_secondary : '' }}</td></tr>
                            <tr><th>Grade</th><td>{{ $reviewApplicant->position->grade ?? '' }}</td><th>Salary</th><td>{{ $reviewApplicant->position->salary ?? '' }}</td><th>Requirement Type</th><td>{{ $reviewApplicant->position->requirement_type ?? '' }}</td></tr>
                            <tr><th>Position Requirements</th><td colspan="5">{{ $reviewApplicant->position->criteria ?? '' }}</td></tr>
                        </tbody>
                    </table>

                    <h3 style="margin-top:16px;">💼 Work Experience</h3>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Company</th><th>Job Title</th><th>Start</th><th>End</th><th>Duration</th><th>Relevant</th></tr></thead>
                            <tbody>
                                @forelse($reviewExperiences as $exp)
                                    <tr><td>{{ $exp->company }}</td><td>{{ $exp->job_title }}</td><td>{{ $exp->start_date ?? '-' }}</td><td>{{ $exp->end_date ?? '-' }}</td><td>{{ $exp->duration }}</td><td>{{ $exp->specific_to_position ? 'Yes' : 'No' }}</td></tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty-state"><p>No work experience records</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <form method="post" class="section-gap" action="{{ route('applicants.review.save') }}" style="margin-top:16px;">
                    @csrf
                    <input type="hidden" name="applicant_id" value="{{ $reviewApplicant->id }}">

                    <div class="card">
                        <h3>📊 Selection Criteria</h3>
                        <div class="small">Criteria and weights come from Recruitment Settings. Final score is calculated automatically.</div>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Criterion</th><th class="num">Weight %</th><th class="num">Maximum</th><th class="num">Pass Mark</th><th>Score</th><th>Comment</th></tr></thead>
                                <tbody>
                                    @forelse($reviewCriteria as $criterion)
                                        @php $saved = $reviewScores[$criterion->id] ?? null; @endphp
                                        <tr>
                                            <td>{{ $criterion->criterion_name }}</td>
                                            <td class="num">{{ number_format($criterion->weight, 1) }}</td>
                                            <td class="num">{{ number_format($criterion->max_score, 1) }}</td>
                                            <td class="num">{{ number_format($criterion->pass_mark, 1) }}</td>
                                            <td><input style="width:110px;" type="number" name="criterion_score_{{ $criterion->id }}" min="0" max="{{ $criterion->max_score }}" step="0.01" value="{{ old('criterion_score_' . $criterion->id, $saved->score ?? '') }}" @if(!$canEdit) disabled @endif></td>
                                            <td><input type="text" name="criterion_comment_{{ $criterion->id }}" value="{{ old('criterion_comment_' . $criterion->id, $saved->comment ?? '') }}" placeholder="Comment" @if(!$canEdit) disabled @endif></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6"><div class="empty-state"><p>No selection criteria configured</p></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="applicant-form section-gap">
                        <div class="field" style="grid-column:span 2;"><label>👥 Committee Members</label><input type="text" name="committee_members" value="{{ old('committee_members', $reviewApplicant->selection->committee_members ?? '') }}" placeholder="Names separated by commas"></div>
                        <div class="field"><label>📅 Review Date</label><input type="date" name="review_date" value="{{ old('review_date', $reviewApplicant->selection->review_date ?? now()->format('Y-m-d')) }}"></div>
                        <div class="field"><label>🏆 Rank</label><input type="number" name="rank_no" min="0" step="1" value="{{ old('rank_no', $reviewApplicant->selection->rank_no ?? 0) }}"></div>
                        <div class="field"><label>🎯 Decision</label><select name="decision" required>@foreach($decisions as $decision)<option value="{{ $decision }}" @if(old('decision', $reviewApplicant->selection->decision ?? 'Pending') == $decision) selected @endif>{{ $decision }}</option>@endforeach</select></div>
                        <div class="field" style="grid-column:span 3;"><label>📝 Final Remarks</label><textarea name="remarks" rows="2">{{ old('remarks', $reviewApplicant->selection->remarks ?? '') }}</textarea></div>
                        @if($canEdit && $reviewCriteria->count() > 0)<div><button type="submit">💾 Save Committee Screening</button></div>@endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECTION: APPLICANT LIST -->
    <!-- ============================================ -->
    <div id="section-list" class="section-card">
        <div class="card">
            <div class="section-title">
                <h2>📋 Applicant Details and Selection Status</h2>
                <span class="subtitle">{{ $applicants->count() }} applicants found</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Registration Date</th>
                            <th>Organization</th>
                            <th>Position</th>
                            <th>Full Name</th>
                            <th>Academic Level</th>
                            <th>Academic Field</th>
                            <th>Graduation Date</th>
                            <th>Phone</th>
                            <th class="num">Final Score</th>
                            <th class="num">Rank</th>
                            <th>Decision</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applicants as $index => $applicant)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $applicant->registration_date }}</td>
                                <td>{{ $applicant->position->organization->name ?? '' }}</td>
                                <td>{{ $applicant->position->name ?? '' }}</td>
                                <td><strong>{{ $applicant->full_name }}</strong></td>
                                <td>{{ $applicant->academic_level }}</td>
                                <td>{{ $applicant->academic_field }}</td>
                                <td>{{ $applicant->graduation_date ?? '-' }}</td>
                                <td>{{ $applicant->phone_primary }}</td>
                                <td class="num">{{ number_format($applicant->selection->final_score ?? 0, 2) }}</td>
                                <td class="num">{{ $applicant->selection->rank_no ?? '-' }}</td>
                                <td>
                                    @php
                                        $decision = $applicant->selection->decision ?? 'Pending';
                                        $badgeClass = $decision == 'Selected' ? 'selected' : ($decision == 'Rejected' ? 'rejected' : ($decision == 'Shortlisted' ? 'shortlisted' : ($decision == 'Reserve' ? 'reserve' : 'pending')));
                                    @endphp
                                    <span class="badge-status {{ $badgeClass }}">{{ $decision }}</span>
                                </td>
                                <td style="text-align:center;">
                                    @if($canEdit)
                                        <div style="display:flex; gap:4px; justify-content:center; flex-wrap:wrap;">
                                            <a class="btn light btn-sm" href="{{ route('applicants.index', ['edit_registration' => $applicant->id]) }}#registration-form">✏️</a>
                                            <a class="btn btn-blue btn-sm" href="{{ route('applicants.index', ['review_applicant' => $applicant->id]) }}#committee-review">📋</a>
                                            <a class="btn btn-orange btn-sm" href="{{ url('/applicants/profile/' . $applicant->id) }}">📄</a>
                                            <form method="post" style="display:inline;" action="{{ route('applicants.destroy', $applicant->id) }}" onsubmit="return confirm('Delete this applicant?');">
                                                @csrf @method('DELETE')
                                                <button class="btn danger btn-sm" type="submit">🗑️</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="13"><div class="empty-state"><div class="icon">📭</div><h4>No applicant records found</h4><p>Register an applicant using the form above.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECTION: POSITIONS & ORGANIZATIONS -->
    <!-- ============================================ -->
    <div id="section-positions" class="section-card">
        @if($canEdit)
            <!-- Organization Settings -->
            <div class="card">
                <div class="section-title">
                    <h2>🏢 Recruitment Organization Settings</h2>
                    <span class="subtitle">Add organizations before creating positions</span>
                </div>
                <form method="post" class="inline-form" action="{{ route('applicants.organizations.store') }}">
                    @csrf
                    <div class="field"><label>Organization Name</label><input type="text" name="name" placeholder="Example: TNT or another company" required></div>
                    <button type="submit">Add Organization</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Organization</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($organizations as $org)
                                <tr><td><strong>{{ $org->name }}</strong></td><td><span class="badge-status {{ $org->active ? 'active' : 'inactive' }}">{{ $org->active ? 'Active' : 'Inactive' }}</span></td>
                                    <td><form method="post" style="display:inline;" action="{{ route('applicants.organizations.destroy', $org->id) }}">@csrf @method('DELETE')<button class="btn danger btn-sm" onclick="return confirm('Delete this organization?')">Delete</button></form></td></tr>
                            @empty
                                <tr><td colspan="3"><div class="empty-state"><p>No organizations configured</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Position -->
            <div class="card section-gap">
                <div class="section-title">
                    <h2>➕ Add Application Position</h2>
                    <span class="subtitle">Create a new position under an organization</span>
                </div>
                <form method="post" class="applicant-form" action="{{ route('applicants.positions.store') }}">
                    @csrf
                    <div class="field"><label>Organization <span style="color:red;">*</span></label><select name="organization_id" required><option value="">-- Select --</option>@foreach($activeOrganizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Position Name <span style="color:red;">*</span></label><input type="text" name="name" placeholder="e.g., Project Manager" required></div>
                    <div class="field"><label>Grade</label><input type="text" name="grade" placeholder="e.g., Grade I"></div>
                    <div class="field"><label>Salary</label><input type="text" name="salary" placeholder="e.g., 50,000 - 70,000"></div>
                    <div class="field"><label>Requirement Type</label><input type="text" name="requirement_type" placeholder="e.g., Full-time"></div>
                    <div class="field" style="grid-column:span 3;"><label>Position Requirements / Criteria</label><textarea name="criteria" rows="2" placeholder="Describe the position requirements"></textarea></div>
                    <div><button type="submit">Add Position</button></div>
                </form>
            </div>

            <!-- Update Position -->
            <div class="card section-gap" style="border: 2px solid #397a9f; background: #f8fbfd;">
                <div class="section-title">
                    <h2 style="color: #16324f;">✏️ Update Application Position</h2>
                    <span class="subtitle">Select a position to update its details</span>
                </div>
                <form method="POST" action="{{ route('applicants.positions.update') }}" class="applicant-form">
                    @csrf @method('PUT')
                    <div class="field"><label>Position to Update <span style="color:red;">*</span></label><select name="position_id" id="update_position_select" required><option value="">-- Select --</option>@foreach($positions as $pos)<option value="{{ $pos->id }}" data-org="{{ $pos->organization_id }}" data-name="{{ $pos->name }}" data-grade="{{ $pos->grade }}" data-salary="{{ $pos->salary }}" data-requirement="{{ $pos->requirement_type }}" data-criteria="{{ $pos->criteria }}">{{ $pos->organization->name ?? 'No Org' }} — {{ $pos->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Organization <span style="color:red;">*</span></label><select name="organization_id" id="update_org_id" required><option value="">-- Select --</option>@foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Position Name <span style="color:red;">*</span></label><input type="text" name="name" id="update_name" placeholder="Position name" required></div>
                    <div class="field"><label>Grade</label><input type="text" name="grade" id="update_grade" placeholder="e.g., Grade I"></div>
                    <div class="field"><label>Salary</label><input type="text" name="salary" id="update_salary" placeholder="e.g., 50,000 - 70,000"></div>
                    <div class="field"><label>Requirement Type</label><input type="text" name="requirement_type" id="update_requirement" placeholder="e.g., Full-time"></div>
                    <div class="field" style="grid-column:span 3;"><label>Position Requirements / Criteria</label><textarea name="criteria" id="update_criteria" rows="2" placeholder="Describe the position requirements"></textarea></div>
                    <div><button type="submit" class="btn" style="background:#397a9f;">Update Position</button><button type="reset" class="btn light" onclick="document.getElementById('update_position_select').value='';">Clear</button></div>
                </form>
            </div>

            <!-- Position List -->
            <div class="card section-gap">
                <div class="section-title">
                    <h2>📋 Application Positions List</h2>
                    <span class="subtitle">{{ $positions->count() }} positions configured</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Organization</th><th>Position</th><th>Grade</th><th>Salary</th><th>Requirement</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($positions as $pos)
                                <tr><td>{{ $pos->organization->name ?? '' }}</td><td><strong>{{ $pos->name }}</strong></td><td>{{ $pos->grade ?: '-' }}</td><td>{{ $pos->salary ?: '-' }}</td><td>{{ $pos->requirement_type ?: '-' }}</td>
                                    <td><form method="post" style="display:inline;" action="{{ route('applicants.positions.destroy', $pos->id) }}">@csrf @method('DELETE')<button class="btn danger btn-sm" onclick="return confirm('Delete this position?')">Delete</button></form></td></tr>
                            @empty
                                <tr><td colspan="6"><div class="empty-state"><p>No positions configured</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card"><h2>Recruitment Settings</h2><div class="small">Only HR editors and administrators can manage organizations, positions and criteria.</div></div>
        @endif
    </div>

    <!-- ============================================ -->
    <!-- SECTION: CRITERIA -->
    <!-- ============================================ -->
    <div id="section-criteria" class="section-card">
        <div class="card">
            <div class="section-title">
                <h2>📊 Selection Criteria Settings</h2>
                <span class="subtitle">Set criteria, weights and pass marks for each organization</span>
            </div>

            @if($canEdit)
                <form method="post" class="applicant-form" action="{{ route('applicants.criteria.store') }}">
                    @csrf
                    <div class="field"><label>Organization <span style="color:red;">*</span></label><select name="organization_id" required>@foreach($activeOrganizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Position Scope</label><select name="criterion_position_id"><option value="">All Positions</option>@foreach($positions as $pos)<option value="{{ $pos->id }}">{{ $pos->organization->name ?? '' }} — {{ $pos->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Criterion Name <span style="color:red;">*</span></label><input type="text" name="criterion_name" placeholder="Document Screening / Interview" required></div>
                    <div class="field"><label>Weight % <span style="color:red;">*</span></label><input type="number" name="weight" min="0.01" step="0.01" value="25" required></div>
                    <div class="field"><label>Maximum Score <span style="color:red;">*</span></label><input type="number" name="max_score" min="0.01" step="0.01" value="100" required></div>
                    <div class="field"><label>Pass Mark</label><input type="number" name="pass_mark" min="0" step="0.01" value="50"></div>
                    <div class="field"><label>Display Order</label><input type="number" name="display_order" min="1" step="1" value="1"></div>
                    <div><button type="submit">Add Criterion</button></div>
                </form>

                <div class="table-wrap" style="margin-top:16px;">
                    <table>
                        <thead><tr><th>Organization</th><th>Position Scope</th><th>Criterion</th><th class="num">Weight %</th><th class="num">Maximum</th><th class="num">Pass Mark</th><th>Action</th></tr></thead>
                        <tbody>
                            @php $allCriteria = \App\Models\SelectionCriterion::with(['organization', 'position'])->get(); @endphp
                            @forelse($allCriteria as $criterion)
                                <tr><td>{{ $criterion->organization->name ?? '' }}</td><td>{{ $criterion->position->name ?? 'All Positions' }}</td><td>{{ $criterion->criterion_name }}</td><td class="num">{{ number_format($criterion->weight, 1) }}</td><td class="num">{{ number_format($criterion->max_score, 1) }}</td><td class="num">{{ number_format($criterion->pass_mark, 1) }}</td>
                                    <td><form method="post" style="display:inline;" action="{{ route('applicants.criteria.destroy', $criterion->id) }}">@csrf @method('DELETE')<button class="btn danger btn-sm" onclick="return confirm('Delete this criterion?')">Delete</button></form></td></tr>
                            @empty
                                <tr><td colspan="7"><div class="empty-state"><p>No selection criteria configured</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="small">Only HR editors and administrators can manage criteria.</div>
            @endif
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECTION: REPORTS -->
    <!-- ============================================ -->
    <div id="section-reports" class="section-card">
        <div class="card" id="report-parameters">
            <div class="section-title">
                <h2>📊 Applicant Report Parameters</h2>
                <span class="subtitle">Select filters to generate professional Excel reports</span>
            </div>

            <form method="get" action="{{ route('applicants.index') }}" class="report-form">
                <input type="hidden" name="run" value="1">
                <div class="form-grid-3">
                    <div class="field"><label>Organization</label><select name="organization"><option value="0">All Organizations</option>@foreach($organizations as $org)<option value="{{ $org->id }}" @if(request('organization') == $org->id) selected @endif>{{ $org->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Application Position</label><select name="position"><option value="0">All Positions</option>@foreach($positions as $pos)<option value="{{ $pos->id }}" @if(request('position') == $pos->id) selected @endif>{{ $pos->organization->name ?? '' }} — {{ $pos->name }}</option>@endforeach</select></div>
                    <div class="field"><label>From Registration Date</label><input type="date" name="date_from" value="{{ request('date_from', '') }}"></div>
                    <div class="field"><label>To Registration Date</label><input type="date" name="date_to" value="{{ request('date_to', '') }}"></div>
                    <div class="field"><label>Academic Background</label><select name="academic"><option value="All" @if(request('academic', 'All') == 'All') selected @endif>All Academic Levels</option>@foreach($academicLevels as $level)<option value="{{ $level }}" @if(request('academic') == $level) selected @endif>{{ $level }}</option>@endforeach</select></div>
                    <div class="field"><label>Selection Decision</label><select name="decision"><option value="All" @if(request('decision', 'All') == 'All') selected @endif>All Selection Decisions</option>@foreach($decisions as $decision)<option value="{{ $decision }}" @if(request('decision') == $decision) selected @endif>{{ $decision }}</option>@endforeach</select></div>
                </div>
                <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="btn" style="background:#1b7f79;">🔄 Update Report</button>
                    <a href="{{ route('applicants.index') }}" class="btn light">Clear All</a>
                </div>
            </form>

            @if(request('run') == 1)
                <div style="margin-top:16px; padding:16px; background:#f8fbfd; border-radius:8px; border:1px solid #dce5ee;">
                    <h3 style="margin:0 0 12px 0; font-size:14px;">📥 Download Excel Reports</h3>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="{{ route('applicants.export', request()->all()) }}" class="btn" style="background:#1b7f79;">📋 Applicant Register Excel</a>
                        <a href="{{ route('applicants.export-selection', request()->all()) }}" class="btn" style="background:#397a9f;">🎯 Selection Report Excel</a>
                        <a href="{{ route('applicants.export-profile', request()->all()) }}" class="btn" style="background:#d38b2a;">📄 Detailed Profile Excel</a>
                    </div>
                    <div class="small" style="margin-top:8px; color:var(--muted);">{{ $applicants->count() }} applicants found matching the selected criteria.</div>
                </div>
            @endif
        </div>
    </div>

    <!-- ============================================ -->
    <!-- JAVASCRIPT -->
    <!-- ============================================ -->
    <script>
        function switchSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section-card').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected section
            const target = document.getElementById('section-' + sectionId);
            if (target) {
                target.classList.add('active');
            }
            
            // Update nav links
            document.querySelectorAll('.applicant-nav a').forEach(el => {
                el.classList.remove('active');
                if (el.getAttribute('href') === '#section-' + sectionId) {
                    el.classList.add('active');
                }
            });
            
            // Update URL hash
            history.pushState(null, null, '#section-' + sectionId);
            
            // Scroll to top of section
            const nav = document.getElementById('mainNav');
            if (nav) {
                nav.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Check for hash on load
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const sectionId = hash.replace('#section-', '');
                if (sectionId) {
                    switchSection(sectionId);
                }
            }
            
            // Auto-fill update position form
            const positionSelect = document.getElementById('update_position_select');
            if (positionSelect) {
                positionSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        document.getElementById('update_org_id').value = selectedOption.dataset.org || '';
                        document.getElementById('update_name').value = selectedOption.dataset.name || '';
                        document.getElementById('update_grade').value = selectedOption.dataset.grade || '';
                        document.getElementById('update_salary').value = selectedOption.dataset.salary || '';
                        document.getElementById('update_requirement').value = selectedOption.dataset.requirement || '';
                        document.getElementById('update_criteria').value = selectedOption.dataset.criteria || '';
                    }
                });
            }
        });
    </script>
@endsection