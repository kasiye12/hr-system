@extends('layout')

@section('title', 'Apply for Leave | TNT HR')

@section('content')
<style>
    :root {
        --eth-green: #1b7f79;
        --eth-navy: #16324f;
        --eth-gold: #d38b2a;
        --eth-gray: #627386;
        --eth-light: #f4f7fb;
        --eth-border: #dce5ee;
    }
    
    .apply-container {
        max-width: 750px;
        margin: 0 auto;
    }
    
    .apply-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--eth-border);
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    
    .apply-header {
        background: linear-gradient(135deg, var(--eth-navy) 0%, #0f2235 100%);
        padding: 28px 32px;
        color: #fff;
    }
    
    .apply-header h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .apply-header p {
        margin: 6px 0 0;
        opacity: 0.85;
        font-size: 13px;
    }
    
    .apply-body {
        padding: 28px 32px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-grid .full-width {
        grid-column: 1 / -1;
    }
    
    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .field-group label {
        font-size: 12px;
        font-weight: 700;
        color: var(--eth-gray);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    
    .field-group label .required {
        color: #a61b1b;
    }
    
    .field-group input,
    .field-group select,
    .field-group textarea {
        padding: 10px 14px;
        border: 2px solid var(--eth-border);
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
        width: 100%;
        box-sizing: border-box;
    }
    
    .field-group input:focus,
    .field-group select:focus,
    .field-group textarea:focus {
        outline: none;
        border-color: var(--eth-green);
        box-shadow: 0 0 0 3px rgba(27,127,121,0.1);
    }
    
    .field-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    /* Searchable Dropdown */
    .searchable-wrapper {
        position: relative;
        width: 100%;
    }
    
    .searchable-input {
        width: 100% !important;
        padding: 10px 40px 10px 14px !important;
        border: 2px solid var(--eth-border) !important;
        border-radius: 10px !important;
        font-size: 14px !important;
        box-sizing: border-box !important;
        transition: all 0.2s !important;
    }
    
    .searchable-input:focus {
        border-color: var(--eth-green) !important;
        box-shadow: 0 0 0 3px rgba(27,127,121,0.1) !important;
    }
    
    .searchable-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--eth-gray);
        pointer-events: none;
        font-size: 16px;
    }
    
    .searchable-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 280px;
        overflow-y: auto;
        background: #fff;
        border: 2px solid var(--eth-green);
        border-top: none;
        border-radius: 0 0 10px 10px;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        display: none;
        margin-top: -2px;
    }
    
    .searchable-dropdown.show {
        display: block;
    }
    
    .searchable-option {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f0f4f8;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .searchable-option:hover {
        background: #e8f3f2;
    }
    
    .searchable-option.selected {
        background: #d4edda;
        border-left: 4px solid var(--eth-green);
    }
    
    .searchable-option.clear-option {
        color: var(--eth-gray);
        font-style: italic;
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .searchable-option .emp-name {
        font-weight: 600;
        color: #1b2635;
        font-size: 14px;
    }
    
    .searchable-option .emp-info {
        font-size: 11px;
        color: var(--eth-gray);
        margin-top: 2px;
    }
    
    .searchable-option .emp-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        background: #e8f3f2;
        color: #0c625e;
        white-space: nowrap;
    }
    
    .searchable-highlight {
        background: #fff3cd;
        padding: 1px 3px;
        border-radius: 2px;
    }
    
    .searchable-dropdown::-webkit-scrollbar {
        width: 5px;
    }
    
    .searchable-dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .searchable-dropdown::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .searchable-dropdown::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    
    .no-results {
        padding: 20px;
        text-align: center;
        color: var(--eth-gray);
        display: none;
    }
    
    .no-results .icon {
        font-size: 32px;
        margin-bottom: 8px;
    }
    
    /* Leave Info Panel */
    .info-panel {
        background: #f8fbfd;
        border: 1px solid var(--eth-border);
        border-radius: 12px;
        padding: 18px 20px;
        margin-top: 20px;
        display: none;
    }
    
    .info-panel.show {
        display: block;
    }
    
    .info-panel .info-title {
        font-weight: 700;
        color: var(--eth-navy);
        font-size: 14px;
        margin-bottom: 6px;
    }
    
    .info-panel .info-desc {
        font-size: 13px;
        color: var(--eth-gray);
        line-height: 1.5;
    }
    
    .info-panel .info-badges {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .info-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }
    
    .info-badge.law {
        background: #e8f3f2;
        color: #0c625e;
    }
    
    .info-badge.calendar {
        background: #fff5e5;
        color: #a45100;
    }
    
    .info-badge.paid {
        background: #dcf5e7;
        color: #18794e;
    }
    
    .info-badge.unpaid {
        background: #ffeded;
        color: #a61b1b;
    }
    
    /* Buttons */
    .btn-submit {
        padding: 12px 28px;
        background: var(--eth-green);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-submit:hover {
        background: #156b66;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(27,127,121,0.3);
    }
    
    .btn-cancel {
        padding: 12px 24px;
        background: #e9eff5;
        color: #23384d;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .btn-cancel:hover {
        background: #d5dde6;
    }
    
    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    
    /* Employee count badge */
    .emp-count {
        font-size: 11px;
        color: var(--eth-gray);
        margin-top: 4px;
        display: block;
    }
    
    .emp-count strong {
        color: var(--eth-navy);
    }
    
    @media (max-width: 600px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .apply-header {
            padding: 20px;
        }
        .apply-body {
            padding: 20px;
        }
        .button-group {
            flex-direction: column;
        }
        .btn-submit, .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="apply-container">
    <div class="apply-card">
        <!-- Header -->
        <div class="apply-header">
            <h2>➕ Apply for Leave</h2>
            <p>Ethiopian Labor Proclamation No. 1156/2019 Compliant</p>
        </div>
        
        <!-- Form Body -->
        <div class="apply-body">
            <form method="post" action="{{ route('leaves.store') }}" enctype="multipart/form-data" onsubmit="return validateDates()">
                @csrf
                
                <div class="form-grid">
                    
                    <!-- Searchable Employee Dropdown -->
                    <div class="field-group full-width">
                        <label>Select Employee <span class="required">*</span></label>
                        <div class="searchable-wrapper" id="employeeSearchWrapper">
                            <input type="text" 
                                   class="searchable-input" 
                                   id="employeeSearch"
                                   placeholder="🔍 Type employee name to search..." 
                                   autocomplete="off"
                                   onfocus="showEmployeeDropdown()"
                                   onkeyup="filterEmployees()"
                                   value="{{ old('employee_name') }}">
                            <span class="searchable-icon">🔍</span>
                            
                            <div class="searchable-dropdown" id="employeeDropdown">
                                <!-- Clear option -->
                                <div class="searchable-option clear-option" 
                                     data-id="" 
                                     data-name=""
                                     onclick="selectEmployee('', '', this)">
                                    ✖ Clear Selection
                                </div>
                                
                                <!-- Employee options -->
                                @foreach($applicants as $app)
                                    @php
                                        $hireDate = \Carbon\Carbon::parse($app->registration_date);
                                        $yearsOfService = $hireDate->diffInYears(now());
                                    @endphp
                                    <div class="searchable-option employee-option" 
                                         data-id="{{ $app->id }}"
                                         data-name="{{ $app->full_name }}"
                                         data-search="{{ strtolower($app->full_name . ' ' . ($app->position->name ?? '') . ' ' . ($app->phone_primary ?? '')) }}"
                                         onclick="selectEmployee('{{ $app->id }}', '{{ $app->full_name }}', this)">
                                        <div>
                                            <div class="emp-name">
                                                <span class="emp-number">{{ $loop->iteration }}.</span> 
                                                {{ $app->full_name }}
                                            </div>
                                            <div class="emp-info">
                                                📌 {{ $app->position->name ?? 'No Position' }} 
                                                | 📱 {{ $app->phone_primary ?? 'N/A' }}
                                                | 📅 Joined: {{ $app->registration_date }}
                                                | ⏳ {{ $yearsOfService }} yr(s)
                                            </div>
                                        </div>
                                        <span class="emp-badge">
                                            {{ $app->position->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                @endforeach
                                
                                <!-- No results -->
                                <div class="no-results" id="noResults">
                                    <div class="icon">🔍</div>
                                    <div style="font-weight:600;">No employees found</div>
                                    <div style="font-size:12px;">Try a different search term</div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="applicant_id" id="applicantId" value="{{ old('applicant_id') }}" required>
                        
                        <!-- Existing approved/pending leaves for overlap check -->
                        <div id="existingLeaves" style="display:none;" data-leaves='@json($existingLeaves ?? [])'></div>
                        </div>
                        <span class="emp-count">
                            <strong id="employeeCount">{{ $applicants->count() }}</strong> employees available · Type to search by name, position, or phone
                        </span>
                    </div>
                    
                    <!-- Leave Type -->
                    <div class="field-group">
                        <label>Leave Type <span class="required">*</span></label>
                        <select name="leave_type_id" id="leaveTypeSelect" required onchange="showLeaveInfo()">
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}" 
                                    data-code="{{ $type->code }}"
                                    data-name="{{ $type->name }}"
                                    data-desc="{{ $type->description }}"
                                    data-law="{{ $type->legal_reference }}"
                                    data-calendar="{{ $type->is_calendar_days }}"
                                    data-paid="{{ $type->is_paid }}"
                                    data-doc="{{ $type->requires_document }}"
                                    data-days="{{ $type->default_days }}"
                                    {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                    @switch($type->code)
                                        @case('annual') 🏖️ Annual Leave @break
                                        @case('sick') 🏥 Sick Leave @break
                                        @case('maternity') 🤰 Maternity Leave @break
                                        @case('paternity') 👨‍🍼 Paternity Leave @break
                                        @case('marriage') 💒 Marriage Leave @break
                                        @case('bereavement') 🕊️ Bereavement Leave @break
                                        @case('unpaid') 📋 Urgent Family Leave @break
                                        @default 📌 {{ $type->name }}
                                    @endswitch
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Document Required Indicator -->
                    <div class="field-group" id="docRequiredGroup" style="display:none;">
                        <label>Supporting Document <span class="required" id="docStar">*</span></label>
                        <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                        <span class="emp-count">Medical certificate, marriage certificate, etc. (Max 5MB)</span>
                    </div>
                    
                    <!-- Start Date -->
                    <div class="field-group">
                        <label>Start Date <span class="required">*</span></label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                    </div>
                    
                    <!-- End Date -->
                    <div class="field-group">
                        <label>End Date <span class="required">*</span></label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required>
                    </div>
                    
                    <!-- Reason -->
                    <div class="field-group full-width">
                        <label>Reason for Leave</label>
                        <textarea name="reason" rows="3" placeholder="Please provide a reason for the leave request...">{{ old('reason') }}</textarea>
                    </div>
                    
                </div>
                
                <!-- Leave Info Panel -->
                <div class="info-panel" id="leaveInfoPanel">
                    <div class="info-title" id="infoTitle"></div>
                    <div class="info-desc" id="infoDesc"></div>
                    <div class="info-badges">
                        <span class="info-badge law" id="infoLaw"></span>
                        <span class="info-badge calendar" id="infoCalendar" style="display:none;">📅 Calendar Days</span>
                        <span class="info-badge paid" id="infoPaid" style="display:none;">💰 Paid Leave</span>
                        <span class="info-badge unpaid" id="infoUnpaid" style="display:none;">📋 Unpaid Leave</span>
                    </div>
                </div>
                
                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="alert error" style="margin-top: 16px;">
                        @foreach ($errors->all() as $error)
                            <div>⚠️ {{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn-submit">
                        📤 Submit Leave Request
                    </button>
                    <a href="{{ route('leaves.index') }}" class="btn-cancel">
                        ← Back to Leave List
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================
// EMPLOYEE SEARCHABLE DROPDOWN
// ============================================

function showEmployeeDropdown() {
    const dropdown = document.getElementById('employeeDropdown');
    dropdown.classList.add('show');
    filterEmployees();
}

function filterEmployees() {
    const searchInput = document.getElementById('employeeSearch');
    const searchTerm = searchInput.value.toLowerCase().trim();
    const options = document.querySelectorAll('.employee-option');
    const noResults = document.getElementById('noResults');
    const countEl = document.getElementById('employeeCount');
    let visibleCount = 0;
    
    options.forEach((option, index) => {
        const searchData = option.getAttribute('data-search');
        const nameEl = option.querySelector('.emp-name');
        const originalName = option.getAttribute('data-name');
        
        if (searchTerm === '' || searchData.includes(searchTerm)) {
            option.style.display = 'flex';
            visibleCount++;
            
            // Highlight matching text
            if (searchTerm !== '' && originalName) {
                nameEl.innerHTML = '<span class="emp-number">' + (index + 1) + '.</span> ' + 
                    highlightText(originalName, searchTerm);
            } else {
                nameEl.innerHTML = '<span class="emp-number">' + (index + 1) + '.</span> ' + originalName;
            }
        } else {
            option.style.display = 'none';
        }
    });
    
    // Update count
    if (countEl) {
        countEl.textContent = visibleCount;
    }
    
    // Show/hide no results message
    if (noResults) {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function highlightText(text, searchTerm) {
    if (!searchTerm) return text;
    const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return text.replace(regex, '<span class="searchable-highlight">$1</span>');
}

function selectEmployee(id, name, element) {
    // Update hidden input
    document.getElementById('applicantId').value = id;
    
    // Update search input
    document.getElementById('employeeSearch').value = name;
    
    // Update visual selection
    document.querySelectorAll('.employee-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    if (element && id) {
        element.classList.add('selected');
    }
    
    // Hide dropdown
    document.getElementById('employeeDropdown').classList.remove('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const wrapper = document.getElementById('employeeSearchWrapper');
    if (wrapper && !wrapper.contains(event.target)) {
        document.getElementById('employeeDropdown').classList.remove('show');
    }
});

// Keyboard navigation
document.getElementById('employeeSearch').addEventListener('keydown', function(e) {
    const dropdown = document.getElementById('employeeDropdown');
    const visibleOptions = Array.from(document.querySelectorAll('.employee-option'))
        .filter(opt => opt.style.display !== 'none');
    
    if (!dropdown.classList.contains('show') && (e.key === 'ArrowDown' || e.key === 'Enter')) {
        showEmployeeDropdown();
        return;
    }
    
    if (e.key === 'Escape') {
        dropdown.classList.remove('show');
        this.blur();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const selected = dropdown.querySelector('.employee-option.selected');
        if (selected) {
            selectEmployee(
                selected.getAttribute('data-id'),
                selected.getAttribute('data-name'),
                selected
            );
        }
    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        dropdown.classList.add('show');
        
        const currentIndex = visibleOptions.findIndex(opt => opt.classList.contains('selected'));
        let nextIndex;
        
        if (e.key === 'ArrowDown') {
            nextIndex = currentIndex < visibleOptions.length - 1 ? currentIndex + 1 : 0;
        } else {
            nextIndex = currentIndex > 0 ? currentIndex - 1 : visibleOptions.length - 1;
        }
        
        visibleOptions.forEach(opt => opt.classList.remove('selected'));
        if (visibleOptions[nextIndex]) {
            visibleOptions[nextIndex].classList.add('selected');
            visibleOptions[nextIndex].scrollIntoView({ block: 'nearest' });
        }
    }
});

// ============================================
// LEAVE TYPE INFO PANEL
// ============================================

function showLeaveInfo() {
    const select = document.getElementById('leaveTypeSelect');
    const option = select.options[select.selectedIndex];
    const panel = document.getElementById('leaveInfoPanel');
    const docGroup = document.getElementById('docRequiredGroup');
    const docStar = document.getElementById('docStar');
    
    if (option.value) {
        // Show info panel
        panel.classList.add('show');
        
        document.getElementById('infoTitle').textContent = 
            option.dataset.code.toUpperCase() + ' LEAVE - ' + option.dataset.name;
        document.getElementById('infoDesc').textContent = option.dataset.desc;
        document.getElementById('infoLaw').textContent = '📜 ' + option.dataset.law;
        
        // Calendar days indicator
        const calendarBadge = document.getElementById('infoCalendar');
        calendarBadge.style.display = option.dataset.calendar === '1' ? 'inline-block' : 'none';
        
        // Paid/Unpaid indicator
        const paidBadge = document.getElementById('infoPaid');
        const unpaidBadge = document.getElementById('infoUnpaid');
        
        if (option.dataset.paid === '1') {
            paidBadge.style.display = 'inline-block';
            unpaidBadge.style.display = 'none';
        } else {
            paidBadge.style.display = 'none';
            unpaidBadge.style.display = 'inline-block';
        }
        
        // Document requirement
        if (option.dataset.doc === '1') {
            docGroup.style.display = 'flex';
            docStar.style.display = 'inline';
        } else {
            docGroup.style.display = 'none';
            docStar.style.display = 'none';
        }
    } else {
        panel.classList.remove('show');
        docGroup.style.display = 'none';
        docStar.style.display = 'none';
    }
}

// Pre-select if editing
document.addEventListener('DOMContentLoaded', function() {
    @if(old('applicant_id'))
        const searchInput = document.getElementById('employeeSearch');
        const option = document.querySelector('.employee-option[data-id="{{ old('applicant_id') }}"]');
        if (option) {
            selectEmployee('{{ old('applicant_id') }}', option.getAttribute('data-name'), option);
        }
    @endif
    
    // Show leave info if type is selected
    if (document.getElementById('leaveTypeSelect').value) {
        showLeaveInfo();
    }
});
</script>
@endsection
