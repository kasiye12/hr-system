@extends('layout')

@section('title', 'Leave Breakdown | TNT HR')

@section('content')
<style>
    .bd-card { background:#fff; border-radius:14px; border:1px solid #dce5ee; overflow:hidden; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.04); }
    .bd-header { background:linear-gradient(135deg,#16324f,#1b7f79); padding:18px 24px; color:#fff; }
    .bd-header h3 { margin:0; font-size:16px; }
    .bd-body { padding:20px 24px; }
    .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:16px; }
    .info-item { background:#f8fbfd; padding:14px; border-radius:10px; border:1px solid #e5e7eb; text-align:center; }
    .info-item .val { font-size:22px; font-weight:700; }
    .info-item .lbl { font-size:11px; color:#627386; text-transform:uppercase; margin-top:3px; }
    .c-green { color:#18794e; } .c-red { color:#a61b1b; } .c-blue { color:#397a9f; } .c-orange { color:#d38b2a; }
    table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
    table th { background:#f0f4f8; padding:10px 12px; font-size:10px; text-transform:uppercase; color:#627386; border-bottom:2px solid #dce5ee; }
    table td { padding:10px 12px; border-bottom:1px solid #f0f4f8; }
    .num { text-align:center; font-weight:600; }
    .total-row { background:#dfeeea; font-weight:700; }
    .total-row td { color:#0c5d58; }
    .law-note { background:#fffdf5; border:1px solid #f1d3a3; border-radius:8px; padding:12px 16px; margin-top:16px; font-size:12px; color:#a45100; }

    /* Searchable Dropdown - Fixed for visibility */
    .search-select-wrapper {
        position: relative;
        width: 100%;
        max-width: 550px;
    }
    .search-select-wrapper .selected-display {
        padding: 12px 16px;
        border: 2px solid #dce5ee;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 50px;
        user-select: none;
        transition: border-color 0.2s;
    }
    .search-select-wrapper .selected-display:hover {
        border-color: #1b7f79;
    }
    .search-select-wrapper .selected-display.open {
        border-color: #1b7f79;
        border-radius: 8px 8px 0 0;
    }
    .search-select-wrapper .selected-display .arrow {
        transition: transform 0.2s;
        font-size: 14px;
        color: #627386;
    }
    .search-select-wrapper .selected-display.open .arrow {
        transform: rotate(180deg);
    }
    .search-select-wrapper .selected-display .selected-value {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .search-select-wrapper .selected-display .selected-value .badge-id {
        background: #e8f3f2;
        color: #0c625e;
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 999px;
        font-weight: 600;
    }
    .search-select-wrapper .selected-display .selected-value .employee-position {
        font-size: 12px;
        color: #627386;
        font-weight: 400;
        background: #f0f4f8;
        padding: 2px 10px;
        border-radius: 999px;
    }
    .search-select-wrapper .selected-display .selected-value .employee-name {
        font-weight: 600;
        color: #16324f;
    }
    .search-select-wrapper .selected-display .selected-value .placeholder {
        color: #627386;
    }

    /* Dropdown List - Made visible with proper height */
    .dropdown-list {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 2px solid #1b7f79;
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 350px;
        overflow: hidden;
        z-index: 9999;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    .dropdown-list.show {
        display: block !important;
    }
    .dropdown-list .search-input {
        padding: 10px 14px;
        border-bottom: 1px solid #dce5ee;
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 5;
    }
    .dropdown-list .search-input input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #dce5ee;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }
    .dropdown-list .search-input input:focus {
        border-color: #1b7f79;
        box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.1);
    }
    .dropdown-list .search-input input::placeholder {
        color: #bac8d6;
    }

    .dropdown-list .options-container {
        max-height: 280px;
        overflow-y: auto;
        padding: 4px 0;
    }
    .dropdown-list .options-container .option-item {
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.15s;
        border-bottom: 1px solid #f0f4f8;
        gap: 10px;
        flex-wrap: wrap;
    }
    .dropdown-list .options-container .option-item:hover {
        background: #f0f7f6;
    }
    .dropdown-list .options-container .option-item.active {
        background: #dfeeea;
    }
    .dropdown-list .options-container .option-item .option-name {
        font-weight: 600;
        color: #16324f;
        flex: 1;
        min-width: 120px;
    }
    .dropdown-list .options-container .option-item .option-position {
        font-size: 12px;
        color: #627386;
        background: #f0f4f8;
        padding: 2px 12px;
        border-radius: 999px;
    }
    .dropdown-list .options-container .option-item .option-id {
        font-size: 11px;
        color: #1b7f79;
        background: #e8f3f2;
        padding: 2px 10px;
        border-radius: 999px;
        font-weight: 600;
    }
    .dropdown-list .options-container .option-item.hidden {
        display: none !important;
    }
    .dropdown-list .options-container .no-results {
        padding: 30px 20px;
        text-align: center;
        color: #627386;
        font-size: 14px;
    }
    .dropdown-list .options-container .option-item .option-check {
        color: #1b7f79;
        font-weight: 700;
        font-size: 18px;
    }
    .dropdown-list .options-container .option-item .option-avatar {
        width: 32px;
        height: 32px;
        background: #e8f3f2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0c625e;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
    }

    /* Scrollbar styling */
    .dropdown-list .options-container::-webkit-scrollbar {
        width: 6px;
    }
    .dropdown-list .options-container::-webkit-scrollbar-track {
        background: #f0f4f8;
    }
    .dropdown-list .options-container::-webkit-scrollbar-thumb {
        background: #dce5ee;
        border-radius: 3px;
    }
    .dropdown-list .options-container::-webkit-scrollbar-thumb:hover {
        background: #bac8d6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .search-select-wrapper {
            max-width: 100%;
        }
        .info-grid {
            grid-template-columns: 1fr 1fr;
        }
        .dropdown-list .options-container .option-item {
            flex-wrap: wrap;
            gap: 6px;
        }
        .dropdown-list .options-container .option-item .option-name {
            min-width: 80px;
            font-size: 13px;
        }
    }
    @media (max-width: 480px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        .dropdown-list .options-container .option-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .dropdown-list .options-container .option-item .option-check {
            align-self: flex-end;
        }
        .search-select-wrapper .selected-display .selected-value {
            font-size: 13px;
        }
        .search-select-wrapper .selected-display .selected-value .employee-position {
            font-size: 11px;
        }
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
    <div>
        <h1 style="margin:0;">📊 Leave Breakdown</h1>
        <p style="color:#627386; margin:4px 0 0; font-size:13px;">Pro-rata Accrual · Carry Forward · Expired · Ethiopian Proclamation 1156/2011</p>
    </div>
    <a href="{{ route('leaves.balances') }}" style="text-decoration:none; padding:8px 16px; background:#e9eff5; border-radius:8px; color:#23384d; font-weight:600;">← Back</a>
</div>

@php
    use App\Services\LeaveCalculationService;
    use App\Services\LeaveExpiryService;
    use App\Models\LeaveType;
    use App\Models\LeaveRequest;
    use App\Models\CarryForwardRequest;
    
    $calc = new LeaveCalculationService();
    $expiryService = new LeaveExpiryService();
    $annualType = LeaveType::where('code', 'annual')->first();
    $selectedId = request('applicant_id', $applicants->first()->id ?? null);
    $selectedApplicant = $selectedId ? \App\Models\Applicant::find($selectedId) : null;
    $today = \Carbon\Carbon::now();
@endphp

<!-- Searchable Dropdown -->
<div class="bd-card">
    <div class="bd-body">
        <form method="GET" id="employeeSelectForm" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:280px;">
                <label style="font-size:12px; color:#627386; font-weight:700; text-transform:uppercase; display:block; margin-bottom:4px;">Select Employee</label>
                <div class="search-select-wrapper">
                    <div class="selected-display" id="selectedDisplay" onclick="toggleDropdown()">
                        <span class="selected-value" id="selectedValue">
                            @if($selectedApplicant)
                                <span class="employee-name">{{ $selectedApplicant->full_name }}</span>
                                <span class="employee-position">{{ $selectedApplicant->position->name ?? 'No Position' }}</span>
                                <span class="badge-id">#{{ $selectedApplicant->id }}</span>
                            @else
                                <span class="placeholder">-- Select Employee --</span>
                            @endif
                        </span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="dropdown-list" id="dropdownList">
                        <div class="search-input">
                            <input type="text" id="searchInput" placeholder="Search by name or ID..." onkeyup="filterOptions()">
                        </div>
                        <div class="options-container" id="optionsContainer">
                            @foreach($applicants as $app)
                                @php
                                    $initial = strtoupper(substr($app->full_name, 0, 1));
                                @endphp
                                <div class="option-item {{ request('applicant_id') == $app->id ? 'active' : '' }}" 
                                     data-id="{{ $app->id }}"
                                     data-name="{{ $app->full_name }}"
                                     data-position="{{ $app->position->name ?? 'No Position' }}"
                                     onclick="selectEmployee({{ $app->id }}, '{{ addslashes($app->full_name) }}', '{{ addslashes($app->position->name ?? 'No Position') }}')">
                                    <div style="display:flex; align-items:center; gap:12px; flex:1; flex-wrap:wrap;">
                                        <span class="option-avatar">{{ $initial }}</span>
                                        <span class="option-name">{{ $app->full_name }}</span>
                                        <span class="option-position">{{ $app->position->name ?? 'No Position' }}</span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span class="option-id">#{{ $app->id }}</span>
                                        @if(request('applicant_id') == $app->id)
                                            <span class="option-check">✓</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="no-results" id="noResults" style="display:none;">
                                No employees found
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($selectedApplicant)
    @php
        $hireDate = $selectedApplicant->registration_date;
        $totalAccrued = $calc->calculateAccruedLeave($hireDate);
        
        // Used days
        $usedDays = LeaveRequest::where('applicant_id', $selectedApplicant->id)
            ->where('leave_type_id', $annualType->id)
            ->where('status', 'approved')
            ->sum('total_days');
        
        $pendingDays = LeaveRequest::where('applicant_id', $selectedApplicant->id)
            ->where('leave_type_id', $annualType->id)
            ->where('status', 'pending')
            ->sum('total_days');
        
        // Carry Forward
        $carryForward = CarryForwardRequest::where('applicant_id', $selectedApplicant->id)
            ->where('status', 'approved')
            ->sum('days');
        
        // Expired
        $expiredData = $expiryService->calculateExpiredLeave($selectedApplicant);
        $totalExpired = $expiredData['total'];
        
        // Eligible for carry forward
        $eligibleCF = $expiryService->calculateEligibleCarryForward($selectedApplicant);
        
        // Final available
        $available = max(0, $totalAccrued - $usedDays - $pendingDays - $totalExpired + $carryForward);
        
        $hireCarbon = \Carbon\Carbon::parse($hireDate);
        $yearsOfService = $hireCarbon->diffInYears($today);
    @endphp

    <div class="bd-card">
        <div class="bd-header">
            <h3>👤 {{ $selectedApplicant->full_name }}</h3>
            <p style="margin:4px 0 0; opacity:0.8; font-size:12px;">
                📅 Hired: {{ $hireDate }} | ⏳ {{ number_format($yearsOfService, 2) }} years | 🏖️ Current Annual: {{ $calc->calculateAnnualEntitlement($yearsOfService) }} days
            </p>
        </div>
    </div>

    <!-- Summary -->
    <div class="info-grid">
        <div class="info-item">
            <div class="val c-blue">{{ number_format($totalAccrued, 2) }}</div>
            <div class="lbl">📐 Total Accrued</div>
        </div>
        <div class="info-item">
            <div class="val c-red">{{ number_format($usedDays, 1) }}</div>
            <div class="lbl">➖ Used</div>
        </div>
        <div class="info-item">
            <div class="val c-orange">{{ number_format($pendingDays, 1) }}</div>
            <div class="lbl">⏳ Pending</div>
        </div>
        <div class="info-item">
            <div class="val c-green">{{ number_format($carryForward, 1) }}</div>
            <div class="lbl">📦 Carry Forward</div>
        </div>
        <div class="info-item">
            <div class="val" style="color:#666;">{{ number_format($totalExpired, 1) }}</div>
            <div class="lbl">⏰ Expired</div>
        </div>
        <div class="info-item" style="background:#dfeeea; border:2px solid #1b7f79;">
            <div class="val c-green" style="font-size:26px;">{{ number_format($available, 2) }}</div>
            <div class="lbl">✅ AVAILABLE</div>
        </div>
    </div>

    <!-- Formula -->
    <div class="bd-card">
        <div class="bd-header"><h3>📐 Calculation Formula</h3></div>
        <div class="bd-body">
            <p style="font-size:14px; text-align:center;">
                <strong>Available = </strong>
                <span style="color:#397a9f;">Accrued ({{ number_format($totalAccrued,1) }})</span>
                <strong> - </strong>
                <span style="color:#a61b1b;">Used ({{ number_format($usedDays,1) }})</span>
                <strong> - </strong>
                <span style="color:#d38b2a;">Pending ({{ number_format($pendingDays,1) }})</span>
                <strong> - </strong>
                <span style="color:#666;">Expired ({{ number_format($totalExpired,1) }})</span>
                <strong> + </strong>
                <span style="color:#18794e;">Carry Forward ({{ number_format($carryForward,1) }})</span>
                <strong> = </strong>
                <span style="color:#18794e; font-size:18px;">{{ number_format($available,2) }} days</span>
            </p>
        </div>
    </div>

    <!-- Expired Details -->
    @if(count($expiredData['details']) > 0)
    <div class="bd-card">
        <div class="bd-header" style="background:linear-gradient(135deg,#a61b1b,#666);"><h3>⏰ Expired Leave Details</h3></div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>Leave Year</th><th class="num">Accrued</th><th class="num">Carry In</th><th class="num">Available</th><th class="num">Used</th><th class="num">Unused</th><th class="num">Expired</th><th>Expiry Date</th></tr>
                </thead>
                <tbody>
                    @foreach($expiredData['details'] as $ed)
                    <tr style="background:#fff5f5;">
                        <td>{{ $ed['leave_year'] }}</td>
                        <td class="num">{{ number_format($ed['accrued'],1) }}</td>
                        <td class="num">{{ number_format($ed['carry_received'],1) }}</td>
                        <td class="num">{{ number_format($ed['total_available'],1) }}</td>
                        <td class="num">{{ number_format($ed['used'],1) }}</td>
                        <td class="num">{{ number_format($ed['unused'],1) }}</td>
                        <td class="num" style="color:#a61b1b; font-weight:700;">{{ number_format($ed['expired'],1) }}</td>
                        <td style="font-size:11px;">{{ $ed['expiry_date'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Carry Forward Eligibility -->
    <div class="bd-card">
        <div class="bd-header" style="background:linear-gradient(135deg,#d38b2a,#b87820);"><h3>📦 Carry Forward Eligibility</h3></div>
        <div class="bd-body">
            <div class="info-grid">
                <div class="info-item"><div class="val c-blue">{{ number_format($eligibleCF['accrued'],1) }}</div><div class="lbl">Previous Year Accrued</div></div>
                <div class="info-item"><div class="val c-green">{{ number_format($eligibleCF['carry_received'],1) }}</div><div class="lbl">Carry Received</div></div>
                <div class="info-item"><div class="val c-red">{{ number_format($eligibleCF['used'],1) }}</div><div class="lbl">Used</div></div>
                <div class="info-item" style="{{ $eligibleCF['unused'] > 0 ? 'background:#fffdf5; border:2px solid #d38b2a;' : '' }}">
                    <div class="val c-orange">{{ number_format($eligibleCF['unused'],1) }}</div>
                    <div class="lbl">{{ $eligibleCF['already_forwarded'] ? '✅ Already Forwarded' : '📦 Eligible to Forward' }}</div>
                </div>
            </div>
            @if($eligibleCF['unused'] > 0 && !$eligibleCF['already_forwarded'])
            <p style="text-align:center; margin-top:12px; font-size:12px; color:#627386;">
                ⚠️ If forwarded, expires: <strong>{{ $eligibleCF['expiry_if_forwarded'] }}</strong>
                <br><a href="{{ route('leaves.carryforward.index') }}" style="color:#d38b2a; font-weight:600;">Go to Carry Forward →</a>
            </p>
            @endif
        </div>
    </div>

    <!-- Law Reference -->
    <div class="law-note">
        <strong>📜 Ethiopian Labor Proclamation 1156/2011:</strong><br>
        • Annual leave accrues daily on pro-rata basis<br>
        • Unused leave can be carried forward for <strong>max 2 years</strong><br>
        • After 2 years from leave year end → <strong>EXPIRED permanently</strong><br>
        • Expired leave <strong>CANNOT</strong> be used or paid out on termination
    </div>

@else
    <div class="bd-card">
        <div class="bd-body" style="text-align:center; padding:60px; color:#627386;">
            <div style="font-size:48px;">📊</div>
            <div style="font-weight:600;">Select an employee</div>
        </div>
    </div>
@endif

<!-- JavaScript for Searchable Dropdown -->
<script>
    let dropdownOpen = false;

    function toggleDropdown() {
        const dropdown = document.getElementById('dropdownList');
        const display = document.getElementById('selectedDisplay');
        dropdownOpen = !dropdownOpen;
        dropdown.classList.toggle('show', dropdownOpen);
        display.classList.toggle('open', dropdownOpen);
        if (dropdownOpen) {
            setTimeout(function() {
                document.getElementById('searchInput').focus();
            }, 100);
        }
    }

    function filterOptions() {
        const searchInput = document.getElementById('searchInput');
        const filter = searchInput.value.toLowerCase();
        const options = document.querySelectorAll('.option-item');
        let hasResults = false;

        options.forEach(option => {
            const name = option.dataset.name.toLowerCase();
            const id = option.dataset.id.toString();
            if (name.includes(filter) || id.includes(filter)) {
                option.classList.remove('hidden');
                hasResults = true;
            } else {
                option.classList.add('hidden');
            }
        });

        document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
    }

    function selectEmployee(id, name, position) {
        // Update the selected display
        document.getElementById('selectedValue').innerHTML = `
            <span class="employee-name">${name}</span>
            <span class="employee-position">${position}</span>
            <span class="badge-id">#${id}</span>
        `;
        
        // Close dropdown
        document.getElementById('dropdownList').classList.remove('show');
        document.getElementById('selectedDisplay').classList.remove('open');
        dropdownOpen = false;
        
        // Submit the form
        const form = document.getElementById('employeeSelectForm');
        // Remove any existing hidden input
        const existingInput = form.querySelector('input[name="applicant_id"]');
        if (existingInput) {
            existingInput.remove();
        }
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'applicant_id';
        input.value = id;
        form.appendChild(input);
        form.submit();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const wrapper = document.querySelector('.search-select-wrapper');
        if (wrapper && !wrapper.contains(event.target)) {
            document.getElementById('dropdownList').classList.remove('show');
            document.getElementById('selectedDisplay').classList.remove('open');
            dropdownOpen = false;
        }
    });

    // Handle keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('dropdownList').classList.remove('show');
            document.getElementById('selectedDisplay').classList.remove('open');
            dropdownOpen = false;
        }
    });

    // Handle Enter key to select first visible option
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && dropdownOpen) {
            const firstVisible = document.querySelector('.option-item:not(.hidden)');
            if (firstVisible) {
                const id = firstVisible.dataset.id;
                const name = firstVisible.dataset.name;
                const position = firstVisible.dataset.position;
                selectEmployee(id, name, position);
            }
        }
    });

    // Auto-open dropdown if no employee is selected
    document.addEventListener('DOMContentLoaded', function() {
        const selectedValue = document.getElementById('selectedValue');
        if (selectedValue && selectedValue.querySelector('.placeholder')) {
            setTimeout(function() {
                toggleDropdown();
            }, 500);
        }
    });
</script>
@endsection