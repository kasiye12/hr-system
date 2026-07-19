@extends('layout')

@section('title', 'Leave Settings | TNT HR')

@section('content')
<style>
    :root {
        --eth-green: #1b7f79;
        --eth-navy: #16324f;
        --eth-gray: #627386;
        --eth-light: #f4f7fb;
        --eth-border: #dce5ee;
        --eth-danger: #a61b1b;
        --eth-success: #18794e;
    }
    
    .settings-container {
        max-width: 1100px;
    }
    
    .settings-nav {
        display: flex;
        gap: 6px;
        margin-bottom: 20px;
        background: #fff;
        padding: 6px 10px;
        border-radius: 12px;
        border: 1px solid var(--eth-border);
        flex-wrap: wrap;
    }
    
    .settings-nav a {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #24384c;
        text-decoration: none;
        transition: all 0.15s;
    }
    
    .settings-nav a:hover { background: #e8f3f2; }
    .settings-nav a.active { background: var(--eth-green); color: #fff; }
    
    .settings-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid var(--eth-border);
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    
    .settings-card-header {
        background: linear-gradient(135deg, var(--eth-navy), #0f2235);
        padding: 18px 24px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .settings-card-header h3 { margin: 0; font-size: 16px; }
    
    .settings-card-body {
        padding: 20px 24px;
    }
    
    .settings-section {
        display: none;
    }
    
    .settings-section.active {
        display: block;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        align-items: end;
        margin-bottom: 16px;
    }
    
    .form-row .field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .field label {
        font-size: 11px;
        font-weight: 700;
        color: var(--eth-gray);
        text-transform: uppercase;
    }
    
    .field input, .field select, .field textarea {
        padding: 9px 12px;
        border: 2px solid var(--eth-border);
        border-radius: 8px;
        font-size: 13px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .field input:focus, .field select:focus {
        outline: none;
        border-color: var(--eth-green);
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--eth-green);
    }
    
    .btn-sm {
        padding: 6px 14px;
        font-size: 12px;
        border-radius: 6px;
    }
    
    .badge-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    
    .badge-active { background: #dcf5e7; color: #18794e; }
    .badge-inactive { background: #f0f0f0; color: #666; }
    
    .table-mini {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    
    .table-mini th {
        background: #f0f4f8;
        padding: 10px 14px;
        font-size: 11px;
        text-transform: uppercase;
        color: var(--eth-gray);
        text-align: left;
    }
    
    .table-mini td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f4f8;
    }
    
    .table-mini tr:hover td {
        background: #f8fbfd;
    }
    
    .alert-mini {
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12px;
        margin-top: 12px;
    }
    
    .alert-mini.info { background: #e8f4fd; color: #0d6289; border: 1px solid #b8d8f0; }
</style>

<div class="settings-container">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
        <div>
            <h1 style="margin:0;">⚙️ Leave Settings</h1>
            <p style="color:var(--eth-gray); margin:4px 0 0; font-size:13px;">
                Manage leave types, public holidays, and system configuration
            </p>
        </div>
        <a href="{{ route('leaves.index') }}" class="btn secondary" style="font-size:13px;">← Back to Leaves</a>
    </div>
    
    <!-- Settings Navigation -->
    <div class="settings-nav" id="settingsNav">
        <a href="#section-types" class="active" onclick="switchSettingsTab('types')">📋 Leave Types</a>
        <a href="#section-holidays" onclick="switchSettingsTab('holidays')">📅 Holidays</a>
        <a href="#section-config" onclick="switchSettingsTab('config')">🔧 Configuration</a>
    </div>
    
    <!-- Section 1: Leave Types -->
    <div id="section-types" class="settings-section active">
        <!-- Add Leave Type -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h3>➕ Add New Leave Type</h3>
            </div>
            <div class="settings-card-body">
                <form method="post" action="{{ route('leaves.settings.store-type') }}">
                    @csrf
                    <div class="form-row">
                        <div class="field">
                            <label>Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" placeholder="e.g., Study Leave" required>
                        </div>
                        <div class="field">
                            <label>Code <span style="color:red;">*</span></label>
                            <input type="text" name="code" placeholder="e.g., study" required>
                        </div>
                        <div class="field">
                            <label>Default Days <span style="color:red;">*</span></label>
                            <input type="number" name="default_days" value="5" min="1" required>
                        </div>
                        <div class="field">
                            <label>Legal Reference</label>
                            <input type="text" name="legal_reference" placeholder="e.g., Art. 30">
                        </div>
                        <div class="field">
                            <label>Description</label>
                            <input type="text" name="description" placeholder="Brief description">
                        </div>
                    </div>
                    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:16px;">
                        <label class="checkbox-group">
                            <input type="checkbox" name="is_paid" value="1" checked> Paid Leave
                        </label>
                        <label class="checkbox-group">
                            <input type="checkbox" name="is_calendar_days" value="1"> Calendar Days
                        </label>
                        <label class="checkbox-group">
                            <input type="checkbox" name="requires_document" value="1"> Requires Document
                        </label>
                        <label class="checkbox-group">
                            <input type="checkbox" name="accrues_over_time" value="1"> Accrues Over Time
                        </label>
                    </div>
                    <button type="submit" class="btn" style="background:var(--eth-green);">➕ Add Leave Type</button>
                </form>
            </div>
        </div>
        
        <!-- Leave Types List -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h3>📋 Leave Types ({{ $leaveTypes->count() }})</h3>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Days</th>
                            <th>Paid</th>
                            <th>Calendar</th>
                            <th>Document</th>
                            <th>Law Ref</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaveTypes as $type)
                        <tr>
                            <td><strong>{{ $type->name }}</strong></td>
                            <td><code>{{ $type->code }}</code></td>
                            <td>{{ $type->default_days }}</td>
                            <td>{{ $type->is_paid ? '✅' : '❌' }}</td>
                            <td>{{ $type->is_calendar_days ? '📅' : '📋' }}</td>
                            <td>{{ $type->requires_document ? '📎' : '-' }}</td>
                            <td>{{ $type->legal_reference ?? '-' }}</td>
                            <td>
                                <span class="badge-status {{ $type->active ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $type->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <form method="post" action="{{ route('leaves.settings.toggle-type', $type->id) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-sm {{ $type->active ? 'secondary' : '' }}" 
                                            style="{{ $type->active ? 'background:#60758a;' : 'background:#18794e;' }} color:#fff;"
                                            title="{{ $type->active ? 'Deactivate' : 'Activate' }}">
                                        {{ $type->active ? '🔒' : '🔓' }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('leaves.settings.delete-type', $type->id) }}" style="display:inline;" onsubmit="return confirm('Delete {{ $type->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm danger" title="Delete">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Section 2: Public Holidays -->
    <div id="section-holidays" class="settings-section">
        <div class="settings-card">
            <div class="settings-card-header">
                <h3>➕ Add Public Holiday</h3>
            </div>
            <div class="settings-card-body">
                <form method="post" action="{{ route('leaves.settings.store-holiday') }}">
                    @csrf
                    <div class="form-row">
                        <div class="field">
                            <label>Holiday Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" placeholder="e.g., Ethiopian New Year" required>
                        </div>
                        <div class="field">
                            <label>Date <span style="color:red;">*</span></label>
                            <input type="date" name="date" required>
                        </div>
                        <div class="field">
                            <label>Description</label>
                            <input type="text" name="description" placeholder="Optional description">
                        </div>
                        <div class="field">
                            <label>&nbsp;</label>
                            <label class="checkbox-group">
                                <input type="checkbox" name="recurring_yearly" value="1" checked> Recurring Yearly
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="background:var(--eth-green);">➕ Add Holiday</button>
                </form>
            </div>
        </div>
        
        <div class="settings-card">
            <div class="settings-card-header">
                <h3>📅 Public Holidays ({{ $holidays->count() }})</h3>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Holiday</th>
                            <th>Description</th>
                            <th>Recurring</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($holidays as $h)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($h->date)->format('M d, Y') }}</td>
                            <td><strong>{{ $h->name }}</strong></td>
                            <td>{{ $h->description ?? '-' }}</td>
                            <td>{{ $h->recurring_yearly ? '✅' : '❌' }}</td>
                            <td style="text-align:center;">
                                <form method="post" action="{{ route('leaves.settings.delete-holiday', $h->id) }}" style="display:inline;" onsubmit="return confirm('Delete this holiday?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Section 3: Configuration -->
    <div id="section-config" class="settings-section">
        <div class="settings-card">
            <div class="settings-card-header">
                <h3>🔧 System Configuration</h3>
            </div>
            <div class="settings-card-body">
                <form method="post" action="{{ route('leaves.settings.update-config') }}">
                    @csrf
                    <div class="form-row">
                        <div class="field">
                            <label>Working Days Per Week</label>
                            <select name="working_days_per_week">
                                <option value="5" {{ ($settings['working_days_per_week'] ?? 6) == 5 ? 'selected' : '' }}>5 Days (Mon-Fri)</option>
                                <option value="6" {{ ($settings['working_days_per_week'] ?? 6) == 6 ? 'selected' : '' }}>6 Days (Mon-Sat) - Ethiopian Default</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Max Annual Leave Blocks/Year</label>
                            <input type="number" name="annual_leave_blocks_max" value="{{ $settings['annual_leave_blocks_max'] ?? 2 }}" min="1" max="10">
                        </div>
                        <div class="field">
                            <label>Carry Forward Max Years</label>
                            <input type="number" name="carry_forward_max_years" value="{{ $settings['carry_forward_max_years'] ?? 2 }}" min="0" max="5">
                        </div>
                        <div class="field">
                            <label>Sick Leave Probation Days</label>
                            <input type="number" name="sick_leave_probation_days" value="{{ $settings['sick_leave_probation_days'] ?? 45 }}" min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn" style="background:var(--eth-green);">💾 Save Configuration</button>
                </form>
                
                <div class="alert-mini info" style="margin-top:20px;">
                    <strong>📜 Ethiopian Labor Proclamation 1156/2019 Defaults:</strong><br>
                    • Working Days: 6 days/week (48 hours max)<br>
                    • Annual Leave Blocks: Maximum 2 per year<br>
                    • Carry Forward: Maximum 2 years<br>
                    • Sick Leave Probation: 45 days
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchSettingsTab(tab) {
    document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
    document.getElementById('section-' + tab).classList.add('active');
    
    document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
    document.querySelector('.settings-nav a[href="#section-' + tab + '"]').classList.add('active');
    
    history.pushState(null, null, '#section-' + tab);
}

document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tab = hash.replace('#section-', '');
        if (tab) switchSettingsTab(tab);
    }
});
</script>
@endsection
