@extends('layout')

@section('title', 'Import Leave Data | TNT HR')

@section('content')
<style>
    .import-card { background: #fff; border-radius: 14px; border: 1px solid #dce5ee; overflow: hidden; margin-bottom: 20px; }
    .import-header { background: linear-gradient(135deg, #16324f, #1b7f79); padding: 18px 24px; color: #fff; }
    .import-header h3 { margin: 0; font-size: 16px; }
    .import-body { padding: 20px 24px; }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px; }
    .form-row .field { display: flex; flex-direction: column; gap: 4px; }
    .field label { font-size: 11px; font-weight: 700; color: #627386; text-transform: uppercase; }
    .field input, .field select, .field textarea { padding: 9px 12px; border: 2px solid #dce5ee; border-radius: 8px; font-size: 13px; }
    .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: #1b7f79; }
    .field textarea { font-family: 'Courier New', monospace; font-size: 12px; min-height: 150px; resize: vertical; }
    .btn-submit { padding: 10px 20px; background: #1b7f79; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; }
    .btn-submit:hover { background: #156b66; }
    .btn-template { padding: 10px 20px; background: #d38b2a; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
    .btn-template:hover { background: #b87820; }
    .format-info { background: #f8fbfd; border: 1px solid #dce5ee; border-radius: 8px; padding: 14px 16px; margin-top: 12px; font-size: 12px; }
    .format-info code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
    table.mini { width: 100%; border-collapse: collapse; font-size: 12px; }
    table.mini th { background: #f0f4f8; padding: 8px 12px; font-size: 10px; text-transform: uppercase; color: #627386; }
    table.mini td { padding: 8px 12px; border-bottom: 1px solid #f0f4f8; }
    .alert-info { padding: 10px 14px; border-radius: 8px; background: #e8f4fd; color: #0d6289; border: 1px solid #b8d8f0; font-size: 13px; margin-bottom: 16px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
    <div>
        <h1 style="margin:0;">📥 Import Leave Data</h1>
        <p style="color:#627386; margin:4px 0 0; font-size:13px;">Bulk import previous/backdated leave records</p>
    </div>
    <a href="{{ route('leaves.index') }}" class="btn-template" style="background:#60758a;">← Back to Leaves</a>
</div>

<!-- Method 1: Single Record Import -->
<div class="import-card">
    <div class="import-header">
        <h3>📝 Import Single Leave Record</h3>
    </div>
    <div class="import-body">
        <form method="post" action="{{ route('leaves.import.store-single') }}">
            @csrf
            <div class="form-row">
                <div class="field">
                    <label>Employee <span style="color:red;">*</span></label>
                    <select name="applicant_id" required>
                        <option value="">Select Employee</option>
                        @foreach($applicants as $app)
                            <option value="{{ $app->id }}">{{ $app->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Leave Type <span style="color:red;">*</span></label>
                    <select name="leave_type_id" required>
                        <option value="">Select Type</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Start Date <span style="color:red;">*</span></label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="field">
                    <label>End Date <span style="color:red;">*</span></label>
                    <input type="date" name="end_date" required>
                </div>
                <div class="field">
                    <label>Total Days <span style="color:red;">*</span></label>
                    <input type="number" name="total_days" step="0.5" min="0.5" value="1" required>
                </div>
                <div class="field">
                    <label>Status <span style="color:red;">*</span></label>
                    <select name="status" required>
                        <option value="approved">✅ Approved</option>
                        <option value="rejected">❌ Rejected</option>
                        <option value="cancelled">🚫 Cancelled</option>
                        <option value="pending">⏳ Pending</option>
                    </select>
                </div>
                <div class="field" style="grid-column: 1/-1;">
                    <label>Reason</label>
                    <input type="text" name="reason" placeholder="e.g., Previous year leave record">
                </div>
            </div>
            <button type="submit" class="btn-submit">📥 Import Single Record</button>
        </form>
    </div>
</div>

<!-- Method 2: Bulk Import -->
<div class="import-card">
    <div class="import-header">
        <h3>📋 Bulk Import (CSV Format)</h3>
    </div>
    <div class="import-body">
        <div class="alert-info">
            <strong>💡 Format:</strong> Each line should be: <code>LeaveCode,StartDate,EndDate,Days,Status,Reason</code>
        </div>
        
        <form method="post" action="{{ route('leaves.import.store-multiple') }}">
            @csrf
            <div class="form-row">
                <div class="field">
                    <label>Employee <span style="color:red;">*</span></label>
                    <select name="applicant_id" required>
                        <option value="">Select Employee</option>
                        @foreach($applicants as $app)
                            <option value="{{ $app->id }}">{{ $app->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="field" style="margin-bottom:14px;">
                <label>Paste Leave Data (one per line) <span style="color:red;">*</span></label>
                <textarea name="import_data" placeholder="annual,2024-01-15,2024-01-30,12,approved,Annual leave
sick,2024-03-10,2024-03-12,3,approved,Medical leave
annual,2024-06-01,2024-06-05,5,approved,Summer break"></textarea>
            </div>
            
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="btn-submit">📥 Bulk Import</button>
                <a href="{{ route('leaves.import.template') }}" class="btn-template">📄 Download CSV Template</a>
            </div>
        </form>
        
        <div class="format-info" style="margin-top:16px;">
            <strong>📋 Format Guide:</strong><br>
            <code>LeaveCode</code> = annual | sick | maternity | paternity | marriage | bereavement | unpaid<br>
            <code>StartDate</code> = YYYY-MM-DD (e.g., 2024-01-15)<br>
            <code>EndDate</code> = YYYY-MM-DD<br>
            <code>Days</code> = Number (e.g., 12 or 3.5)<br>
            <code>Status</code> = approved | rejected | cancelled | pending<br>
            <code>Reason</code> = Optional text
        </div>
    </div>
</div>

<!-- Recent Imports -->
@if($recentImports->count() > 0)
<div class="import-card">
    <div class="import-header">
        <h3>📋 Recently Imported Records ({{ $recentImports->count() }})</h3>
    </div>
    <div style="overflow-x:auto;">
        <table class="mini">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Imported</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentImports as $lr)
                <tr>
                    <td>{{ $lr->applicant->full_name ?? 'N/A' }}</td>
                    <td>{{ $lr->leaveType->name ?? 'N/A' }}</td>
                    <td>{{ $lr->start_date->format('M d, Y') }}</td>
                    <td>{{ $lr->end_date->format('M d, Y') }}</td>
                    <td>{{ $lr->total_days }}</td>
                    <td>{{ ucfirst($lr->status) }}</td>
                    <td>{{ $lr->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        <form method="post" action="{{ route('leaves.import.destroy', $lr->id) }}" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm danger" style="padding:3px 8px; font-size:10px;">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
