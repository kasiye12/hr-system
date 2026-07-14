@extends('layouts.app')

@section('title', 'Applicant Registration - TNT HR')

@section('content')
    <h1>Applicant Registration</h1>
    <div class="subtitle">Register each applicant once. Work experience, recruitment committee screening, selection decisions and Excel reports are linked to the first registration record.</div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert error">
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
    @endif

    @php
        $activeOrganizations = $organizations->filter(function($o) { return $o->active; });
        $activePositions = $positions->filter(function($p) { return $p->active; });
    @endphp

    <!-- ============================================ -->
    <!-- REGISTRATION FORM -->
    <!-- ============================================ -->
    <div class="card" id="registration-form">
        <h2>{{ $editApplicant ? 'Update Applicant Registration' : 'Register New Applicant' }}</h2>
        
        @if($canEdit && $activePositions->count() > 0)
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
            <div class="alert warning">Viewer access: applicant registration is read-only.</div>
        @endif
    </div>

    <!-- ============================================ -->
    <!-- WORK EXPERIENCE SECTION -->
    <!-- ============================================ -->
    <div class="card section-gap">
        <h2>Applicant Work Experience Encoder</h2>
        
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
                    <input type="text" name="company" required>
                </div>
                <div class="field">
                    <label>Job Title <span style="color:red;">*</span></label>
                    <input type="text" name="job_title" required>
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
                    <label>Notes</label>
                    <input type="text" name="notes">
                </div>
                <div>
                    <button type="submit">Add Work Experience</button>
                </div>
            </form>
        @elseif($canEdit && $applicants->count() == 0)
            <div class="alert warning">
                <strong>⚠️ No applicants registered.</strong><br>
                Please register an applicant first before adding work experience.
            </div>
        @else
            <div class="small">Viewer access: work experience is read-only.</div>
        @endif

        <div style="overflow-x:auto;margin-top:16px;">
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $hasExperience = false; @endphp
                    @foreach($allWorkExperiences as $applicantId => $experiences)
                        @foreach($experiences as $exp)
                            @php $hasExperience = true; @endphp
                            <tr>
                                <td>{{ $exp->applicant->position->name ?? '' }}</td>
                                <td>{{ $exp->applicant->full_name ?? '' }}</td>
                                <td>{{ $exp->company }}</td>
                                <td>{{ $exp->job_title }}</td>
                                <td>{{ $exp->start_date ?? '-' }}</td>
                                <td>{{ $exp->end_date ?? '-' }}</td>
                                <td>{{ $exp->duration }}</td>
                                <td>{{ $exp->specific_to_position ? ($exp->specific_position_title ?: 'Yes') : '-' }}</td>
                                <td>
                                    @if($canEdit)
                                        <form method="post" style="display:inline;" action="{{ route('applicants.work-experience.destroy', $exp->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit" onclick="return confirm('Delete this work experience record?')">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    @if(!$hasExperience)
                        <tr><td colspan="9" class="empty">No work experience records entered.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- COMMITTEE REVIEW SECTION -->
    <!-- ============================================ -->
    <div class="card section-gap" id="committee-review">
        <h2>Recruitment Committee Screening / Selection Form</h2>
        <div class="subtitle">This form pulls all applicant information from the registration record. The committee enters only scores, comments, rank and decision.</div>

        <form method="get" action="{{ route('applicants.index') }}" class="report-toolbar">
            <div class="field" style="min-width:420px;">
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
            <button type="submit">Load Candidate Review</button>
        </form>

        @if($reviewApplicant)
            <div class="card" style="background:#f8fbfd;margin-top:14px;">
                <h3>Candidate Profile Loaded from Applicant Registration</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>Organization</th>
                            <td>{{ $reviewApplicant->position->organization->name ?? '' }}</td>
                            <th>Application Position</th>
                            <td>{{ $reviewApplicant->position->name ?? '' }}</td>
                            <th>Registration Date</th>
                            <td>{{ $reviewApplicant->registration_date }}</td>
                        </tr>
                        <tr>
                            <th>Applicant Name</th>
                            <td>{{ $reviewApplicant->full_name }}</td>
                            <th>Academic Background</th>
                            <td>{{ $reviewApplicant->academic_level }} — {{ $reviewApplicant->academic_field }}</td>
                            <th>Graduation Date</th>
                            <td>{{ $reviewApplicant->graduation_date ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Total Experience</th>
                            <td>{{ $reviewApplicant->experience_years }} year(s), {{ $reviewApplicant->experience_months }} month(s)</td>
                            <th>Cell Phone</th>
                            <td colspan="3">{{ $reviewApplicant->phone_primary }} {{ $reviewApplicant->phone_secondary ? '/ ' . $reviewApplicant->phone_secondary : '' }}</td>
                        </tr>
                        <tr>
                            <th>Grade</th>
                            <td>{{ $reviewApplicant->position->grade ?? '' }}</td>
                            <th>Salary</th>
                            <td>{{ $reviewApplicant->position->salary ?? '' }}</td>
                            <th>Requirement Type</th>
                            <td>{{ $reviewApplicant->position->requirement_type ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Position Requirements</th>
                            <td colspan="5">{{ $reviewApplicant->position->criteria ?? '' }}</td>
                        </tr>
                    </tbody>
                </table>

                <h3 style="margin-top:16px;">Work Experience</h3>
                <table>
                    <thead>
                        <tr><th>Company</th><th>Job Title</th><th>Start</th><th>End</th><th>Duration</th><th>Relevant</th></tr>
                    </thead>
                    <tbody>
                        @forelse($reviewExperiences as $exp)
                            <tr>
                                <td>{{ $exp->company }}</td>
                                <td>{{ $exp->job_title }}</td>
                                <td>{{ $exp->start_date ?? '-' }}</td>
                                <td>{{ $exp->end_date ?? '-' }}</td>
                                <td>{{ $exp->duration }}</td>
                                <td>{{ $exp->specific_to_position ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">No work experience records entered.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="post" class="section-gap" action="{{ route('applicants.review.save') }}">
                @csrf
                <input type="hidden" name="applicant_id" value="{{ $reviewApplicant->id }}">

                <div class="card">
                    <h3>Organization / Position Selection Criteria</h3>
                    <div class="small">Criteria and weights come from Recruitment Settings. The final score is calculated automatically when the committee saves the review.</div>
                    
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Criterion</th>
                                    <th class="num">Weight %</th>
                                    <th class="num">Maximum</th>
                                    <th class="num">Pass Mark</th>
                                    <th>Candidate Score</th>
                                    <th>Committee Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reviewCriteria as $criterion)
                                    @php
                                        $saved = $reviewScores[$criterion->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $criterion->criterion_name }}</td>
                                        <td class="num">{{ number_format($criterion->weight, 1) }}</td>
                                        <td class="num">{{ number_format($criterion->max_score, 1) }}</td>
                                        <td class="num">{{ number_format($criterion->pass_mark, 1) }}</td>
                                        <td>
                                            <input style="width:110px;" type="number" 
                                                   name="criterion_score_{{ $criterion->id }}" 
                                                   min="0" max="{{ $criterion->max_score }}" 
                                                   step="0.01" 
                                                   value="{{ old('criterion_score_' . $criterion->id, $saved->score ?? '') }}" 
                                                   @if(!$canEdit) disabled @endif>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="criterion_comment_{{ $criterion->id }}" 
                                                   value="{{ old('criterion_comment_' . $criterion->id, $saved->comment ?? '') }}" 
                                                   placeholder="Committee comment"
                                                   @if(!$canEdit) disabled @endif>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty">No selection criteria configured. Add criteria in settings below.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="applicant-form section-gap">
                    <div class="field" style="grid-column:span 2;">
                        <label>Committee Members</label>
                        <input type="text" name="committee_members" value="{{ old('committee_members', $reviewApplicant->selection->committee_members ?? '') }}" placeholder="Names separated by commas">
                    </div>
                    <div class="field">
                        <label>Review Date</label>
                        <input type="date" name="review_date" value="{{ old('review_date', $reviewApplicant->selection->review_date ?? now()->format('Y-m-d')) }}">
                    </div>
                    <div class="field">
                        <label>Rank</label>
                        <input type="number" name="rank_no" min="0" step="1" value="{{ old('rank_no', $reviewApplicant->selection->rank_no ?? 0) }}">
                    </div>
                    <div class="field">
                        <label>Decision</label>
                        <select name="decision" required>
                            @foreach($decisions as $decision)
                                <option value="{{ $decision }}" @if(old('decision', $reviewApplicant->selection->decision ?? 'Pending') == $decision) selected @endif>{{ $decision }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="grid-column:span 3;">
                        <label>Final Remarks</label>
                        <textarea name="remarks" rows="2">{{ old('remarks', $reviewApplicant->selection->remarks ?? '') }}</textarea>
                    </div>
                    @if($canEdit && $reviewCriteria->count() > 0)
                        <div>
                            <button type="submit">Save Committee Screening / Decision</button>
                        </div>
                    @endif
                </div>
            </form>
        @endif
    </div>

    <!-- ============================================ -->
    <!-- SETTINGS SECTION -->
    <!-- ============================================ -->
    <div class="section-gap">
        @if($canEdit)
            <!-- Organization Settings -->
            <div class="card">
                <h2>🏢 Recruitment Organization Settings</h2>
                <div class="subtitle">Add organizations before creating positions.</div>
                
                <form method="post" class="inline-form" action="{{ route('applicants.organizations.store') }}">
                    @csrf
                    <div class="field">
                        <label>Organization Name</label>
                        <input type="text" name="name" placeholder="Example: TNT or another company" required>
                    </div>
                    <button type="submit">Add Organization</button>
                </form>
                
                <div style="overflow-x:auto;margin-top:12px;">
                    <table>
                        <thead><tr><th>Organization</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($organizations as $org)
                                <tr>
                                    <td><strong>{{ $org->name }}</strong></td>
                                    <td><span class="badge {{ $org->active ? 'active' : 'inactive' }}">{{ $org->active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <form method="post" style="display:inline;" action="{{ route('applicants.organizations.destroy', $org->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit" onclick="return confirm('Delete this organization?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">No organizations configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Position Settings - ADD -->
            <div class="card section-gap">
                <h2>➕ Add Application Position</h2>
                <div class="subtitle">Create a new position under an organization.</div>
                
                <form method="post" class="applicant-form" action="{{ route('applicants.positions.store') }}">
                    @csrf
                    <div class="field">
                        <label>Organization <span style="color:red;">*</span></label>
                        <select name="organization_id" required>
                            <option value="">-- Select Organization --</option>
                            @foreach($activeOrganizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Position Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" placeholder="e.g., Project Manager" required>
                    </div>
                    <div class="field">
                        <label>Grade</label>
                        <input type="text" name="grade" placeholder="e.g., Grade I">
                    </div>
                    <div class="field">
                        <label>Salary</label>
                        <input type="text" name="salary" placeholder="e.g., 50,000 - 70,000">
                    </div>
                    <div class="field">
                        <label>Requirement Type</label>
                        <input type="text" name="requirement_type" placeholder="e.g., Full-time">
                    </div>
                    <div class="field" style="grid-column:span 3;">
                        <label>Position Requirements / Criteria</label>
                        <textarea name="criteria" rows="2" placeholder="Describe the position requirements"></textarea>
                    </div>
                    <div>
                        <button type="submit">Add Position</button>
                    </div>
                </form>
            </div>

            <!-- Position Settings - UPDATE -->
            <div class="card section-gap" style="border: 2px solid #397a9f; background: #f8fbfd;">
                <h2 style="color: #16324f;">✏️ Update Application Position</h2>
                <div class="subtitle">Select a position from the dropdown to update its details</div>
                
                <form method="POST" action="{{ url('/applicants/positions') }}" class="applicant-form">
                    @csrf
                    @method('PUT')
                    
                    <div class="field">
                        <label>Position to Update <span style="color:red;">*</span></label>
                        <select name="position_id" id="update_position_select" required>
                            <option value="">-- Select Position --</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}" 
                                    data-org="{{ $pos->organization_id }}"
                                    data-name="{{ $pos->name }}"
                                    data-grade="{{ $pos->grade }}"
                                    data-salary="{{ $pos->salary }}"
                                    data-requirement="{{ $pos->requirement_type }}"
                                    data-criteria="{{ $pos->criteria }}">
                                    {{ $pos->organization->name ?? 'No Org' }} — {{ $pos->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Organization <span style="color:red;">*</span></label>
                        <select name="organization_id" id="update_org_id" required>
                            <option value="">-- Select Organization --</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Position Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" id="update_name" placeholder="Position name" required>
                    </div>
                    
                    <div class="field">
                        <label>Grade</label>
                        <input type="text" name="grade" id="update_grade" placeholder="e.g., Grade I">
                    </div>
                    
                    <div class="field">
                        <label>Salary</label>
                        <input type="text" name="salary" id="update_salary" placeholder="e.g., 50,000 - 70,000">
                    </div>
                    
                    <div class="field">
                        <label>Requirement Type</label>
                        <input type="text" name="requirement_type" id="update_requirement" placeholder="e.g., Full-time">
                    </div>
                    
                    <div class="field" style="grid-column:span 3;">
                        <label>Position Requirements / Criteria</label>
                        <textarea name="criteria" id="update_criteria" rows="2" placeholder="Describe the position requirements"></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn" style="background:#397a9f;">Update Position</button>
                        <button type="reset" class="btn light" onclick="document.getElementById('update_position_select').value='';">Clear</button>
                    </div>
                </form>
            </div>

            <!-- Position List -->
            <div class="card section-gap">
                <h2>📋 Application Positions List</h2>
                <div style="overflow-x:auto;margin-top:12px;">
                    <table>
                        <thead>
                            <tr><th>Organization</th><th>Position</th><th>Grade</th><th>Salary</th><th>Requirement</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse($positions as $pos)
                                <tr>
                                    <td>{{ $pos->organization->name ?? '' }}</td>
                                    <td><strong>{{ $pos->name }}</strong></td>
                                    <td>{{ $pos->grade ?: '-' }}</td>
                                    <td>{{ $pos->salary ?: '-' }}</td>
                                    <td>{{ $pos->requirement_type ?: '-' }}</td>
                                    <td>
                                        <form method="post" style="display:inline;" action="{{ route('applicants.positions.destroy', $pos->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit" onclick="return confirm('Delete this position?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">No positions configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Criteria Settings -->
            <div class="card section-gap">
                <h2>Selection Criteria Settings</h2>
                <div class="subtitle">Set different criteria, weights and pass marks for each organization. Select a position for position-specific criteria.</div>

                <form method="post" class="applicant-form section-gap" action="{{ route('applicants.criteria.store') }}">
                    @csrf
                    <div class="field">
                        <label>Organization <span style="color:red;">*</span></label>
                        <select name="organization_id" required>
                            @foreach($activeOrganizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Position Scope</label>
                        <select name="criterion_position_id">
                            <option value="">All Positions</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}">{{ $pos->organization->name ?? '' }} — {{ $pos->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Criterion Name <span style="color:red;">*</span></label>
                        <input type="text" name="criterion_name" placeholder="Document Screening / Interview" required>
                    </div>
                    <div class="field">
                        <label>Weight % <span style="color:red;">*</span></label>
                        <input type="number" name="weight" min="0.01" step="0.01" value="25" required>
                    </div>
                    <div class="field">
                        <label>Maximum Score <span style="color:red;">*</span></label>
                        <input type="number" name="max_score" min="0.01" step="0.01" value="100" required>
                    </div>
                    <div class="field">
                        <label>Pass Mark</label>
                        <input type="number" name="pass_mark" min="0" step="0.01" value="50">
                    </div>
                    <div class="field">
                        <label>Display Order</label>
                        <input type="number" name="display_order" min="1" step="1" value="1">
                    </div>
                    <div>
                        <button type="submit">Add Criterion</button>
                    </div>
                </form>

                <div style="overflow-x:auto;margin-top:12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Organization</th>
                                <th>Position Scope</th>
                                <th>Criterion</th>
                                <th class="num">Weight %</th>
                                <th class="num">Maximum</th>
                                <th class="num">Pass Mark</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $allCriteria = \App\Models\SelectionCriterion::with(['organization', 'position'])->get();
                            @endphp
                            @forelse($allCriteria as $criterion)
                                <tr>
                                    <td>{{ $criterion->organization->name ?? '' }}</td>
                                    <td>{{ $criterion->position->name ?? 'All Positions' }}</td>
                                    <td>{{ $criterion->criterion_name }}</td>
                                    <td class="num">{{ number_format($criterion->weight, 1) }}</td>
                                    <td class="num">{{ number_format($criterion->max_score, 1) }}</td>
                                    <td class="num">{{ number_format($criterion->pass_mark, 1) }}</td>
                                    <td>
                                        <form method="post" style="display:inline;" action="{{ route('applicants.criteria.destroy', $criterion->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit" onclick="return confirm('Delete this criterion?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty">No selection criteria configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card">
                <h2>Recruitment Settings</h2>
                <div class="small">Only HR editors and administrators can manage organizations, positions and criteria.</div>
            </div>
        @endif
    </div>

    <!-- ============================================ -->
    <!-- JAVASCRIPT FOR AUTO-FILL UPDATE FORM -->
    <!-- ============================================ -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const positionSelect = document.getElementById('update_position_select');
        
        if (positionSelect) {
            positionSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (selectedOption.value) {
                    // Auto-fill the form with selected position data
                    document.getElementById('update_org_id').value = selectedOption.dataset.org || '';
                    document.getElementById('update_name').value = selectedOption.dataset.name || '';
                    document.getElementById('update_grade').value = selectedOption.dataset.grade || '';
                    document.getElementById('update_salary').value = selectedOption.dataset.salary || '';
                    document.getElementById('update_requirement').value = selectedOption.dataset.requirement || '';
                    document.getElementById('update_criteria').value = selectedOption.dataset.criteria || '';
                } else {
                    // Clear fields when "Select Position" is chosen
                    document.getElementById('update_org_id').value = '';
                    document.getElementById('update_name').value = '';
                    document.getElementById('update_grade').value = '';
                    document.getElementById('update_salary').value = '';
                    document.getElementById('update_requirement').value = '';
                    document.getElementById('update_criteria').value = '';
                }
            });
        }
    });
    </script>

    <!-- ============================================ -->
    <!-- STYLES -->
    <!-- ============================================ -->
    <style>
        .applicant-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            align-items: end;
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
        .report-toolbar {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        .inline-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .applicant-form { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .applicant-form { grid-template-columns: 1fr; }
            .report-toolbar { flex-direction: column; align-items: stretch; }
            .report-toolbar .field { min-width: auto !important; }
        }
    </style>
@endsection