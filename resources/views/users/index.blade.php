@extends('layouts.app')

@section('title', 'Users & Permissions - TNT HR')

@section('content')
<style>
    /* ============================================
       USER MANAGEMENT STYLES
       ============================================ */
    .users-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .users-header h1 {
        margin: 0;
        font-size: 26px;
        color: #16324f;
    }
    .users-header .subtitle {
        color: #627386;
        font-size: 14px;
        margin-top: 2px;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #dce5ee;
        border-radius: 10px;
        padding: 14px 18px;
        text-align: center;
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .stat-card .number {
        font-size: 24px;
        font-weight: 700;
        color: #16324f;
    }
    .stat-card .label {
        font-size: 11px;
        color: #627386;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 600;
        margin-top: 2px;
    }
    .stat-card.highlight {
        background: #dfeeea;
        border-color: #1b7f79;
    }
    .stat-card.highlight .number {
        color: #0c5d58;
    }
    .stat-card .icon {
        font-size: 20px;
        margin-bottom: 2px;
    }

    /* Add User Card */
    .add-user-card {
        background: #fff;
        border: 1px solid #dce5ee;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(21,49,78,0.05);
    }
    .add-user-card .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #16324f;
        margin: 0 0 4px 0;
    }
    .add-user-card .card-subtitle {
        color: #627386;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .add-user-form {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 12px;
        align-items: end;
    }
    .add-user-form .field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .add-user-form .field label {
        font-size: 12px;
        color: #627386;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .add-user-form .field input,
    .add-user-form .field select {
        padding: 10px 14px;
        border: 1px solid #bac8d6;
        border-radius: 6px;
        font-size: 14px;
        background: #fff;
        width: 100%;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .add-user-form .field input:focus,
    .add-user-form .field select:focus {
        outline: none;
        border-color: #1b7f79;
        box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.08);
    }
    .add-user-form .btn-submit {
        padding: 10px 28px;
        background: #1b7f79;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.2s;
        white-space: nowrap;
        margin-bottom: 0;
    }
    .add-user-form .btn-submit:hover {
        background: #156b66;
    }

    /* Table Card */
    .table-card {
        background: #fff;
        border: 1px solid #dce5ee;
        border-radius: 12px;
        padding: 20px 24px;
        box-shadow: 0 2px 8px rgba(21,49,78,0.05);
    }
    .table-card .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .table-card .table-header h3 {
        margin: 0;
        font-size: 16px;
        color: #16324f;
        font-weight: 700;
    }
    .table-card .table-header .records-count {
        font-size: 12px;
        color: #627386;
        background: #f8fbfd;
        padding: 4px 12px;
        border-radius: 999px;
    }

    .table-wrap {
        overflow-x: auto;
    }
    .table-wrap table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .table-wrap th {
        background: #f8fbfd;
        color: #34495e;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 12px 14px;
        text-align: left;
        border-bottom: 2px solid #dce5ee;
        font-weight: 700;
    }
    .table-wrap td {
        padding: 12px 14px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }
    .table-wrap tr:hover td {
        background: #f8fbfd;
    }
    .table-wrap .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: #fff;
        flex-shrink: 0;
    }
    .table-wrap .user-avatar.admin { background: #4b3bad; }
    .table-wrap .user-avatar.editor { background: #0d6289; }
    .table-wrap .user-avatar.viewer { background: #627386; }

    .badge-role {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-role.admin { background: #e6e3ff; color: #4b3bad; }
    .badge-role.editor { background: #dff3ff; color: #0d6289; }
    .badge-role.viewer { background: #eee; color: #666; }

    .badge-status {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-status.active { background: #dcf5e7; color: #12643c; }
    .badge-status.inactive { background: #ffeded; color: #a61b1b; }

    .actions-group {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .btn-sm {
        padding: 4px 10px;
        font-size: 11px;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-sm:hover { filter: brightness(0.93); }
    .btn-sm.btn-toggle {
        background: #e8f3f2;
        color: #0c625e;
    }
    .btn-sm.btn-toggle:hover { background: #d5ebe9; }
    .btn-sm.btn-reset {
        background: #fff5e5;
        color: #a45100;
    }
    .btn-sm.btn-reset:hover { background: #ffedcc; }
    .btn-sm.btn-delete {
        background: #ffeded;
        color: #a61b1b;
    }
    .btn-sm.btn-delete:hover { background: #ffdddd; }
    .btn-sm.btn-current {
        background: #e9eff5;
        color: #23384d;
        cursor: default;
    }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.show {
        display: flex;
    }
    .modal-box {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        max-width: 420px;
        width: 92%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: modalIn 0.3s ease;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(-20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .modal-box .modal-title {
        font-size: 18px;
        font-weight: 700;
        color: #16324f;
        margin: 0 0 4px 0;
    }
    .modal-box .modal-subtitle {
        color: #627386;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .modal-box .field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 12px;
    }
    .modal-box .field label {
        font-size: 12px;
        color: #627386;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .modal-box .field input {
        padding: 10px 14px;
        border: 1px solid #bac8d6;
        border-radius: 6px;
        font-size: 14px;
        width: 100%;
    }
    .modal-box .field input:focus {
        outline: none;
        border-color: #1b7f79;
        box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.08);
    }
    .modal-box .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 16px;
        justify-content: flex-end;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .add-user-form {
            grid-template-columns: 1fr 1fr;
        }
        .add-user-form .btn-submit {
            grid-column: span 2;
        }
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        .table-card .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }
    @media (max-width: 480px) {
        .add-user-form {
            grid-template-columns: 1fr;
        }
        .add-user-form .btn-submit {
            grid-column: span 1;
        }
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .actions-group {
            flex-direction: column;
        }
        .actions-group .btn-sm {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- ============================================ -->
<!-- HEADER -->
<!-- ============================================ -->
<div class="users-header">
    <div>
        <h1>👥 Users & Permissions</h1>
        <div class="subtitle">Manage user accounts, roles, and permissions</div>
    </div>
    <div>
        <span style="font-size:12px; color:#627386; background:#f8fbfd; padding:4px 12px; border-radius:999px;">
            {{ $stats['total'] }} users
        </span>
    </div>
</div>

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

<!-- ============================================ -->
<!-- STATS CARDS -->
<!-- ============================================ -->
<div class="stats-grid">
    <div class="stat-card highlight">
        <div class="icon">👥</div>
        <div class="number">{{ $stats['total'] }}</div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="icon">🛡️</div>
        <div class="number">{{ $stats['admins'] }}</div>
        <div class="label">Admins</div>
    </div>
    <div class="stat-card">
        <div class="icon">✏️</div>
        <div class="number">{{ $stats['editors'] }}</div>
        <div class="label">Editors</div>
    </div>
    <div class="stat-card">
        <div class="icon">👀</div>
        <div class="number">{{ $stats['viewers'] }}</div>
        <div class="label">Viewers</div>
    </div>
    <div class="stat-card">
        <div class="icon">✅</div>
        <div class="number">{{ $stats['active'] }}</div>
        <div class="label">Active</div>
    </div>
    <div class="stat-card">
        <div class="icon">⛔</div>
        <div class="number">{{ $stats['inactive'] }}</div>
        <div class="label">Inactive</div>
    </div>
</div>

<!-- ============================================ -->
<!-- ADD USER -->
<!-- ============================================ -->
@if(Auth::user()->isAdmin())
<div class="add-user-card">
    <div class="card-title">➕ Add New User</div>
    <div class="card-subtitle">Create a new user account with specific role and permissions</div>

    <form method="POST" action="{{ route('users.store') }}" class="add-user-form">
        @csrf
        <div class="field">
            <label>Username <span style="color:red;">*</span></label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>
        <div class="field">
            <label>Temporary Password <span style="color:red;">*</span></label>
            <input type="password" name="password" placeholder="Min 6 characters" required>
        </div>
        <div class="field">
            <label>Role <span style="color:red;">*</span></label>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">🛡️ Admin</option>
                <option value="editor">✏️ Editor</option>
                <option value="viewer">👀 Viewer</option>
            </select>
        </div>
        <button type="submit" class="btn-submit">➕ Create User</button>
    </form>
</div>
@endif

<!-- ============================================ -->
<!-- USERS TABLE -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3>📋 User List</h3>
        <span class="records-count">{{ $users->count() }} users</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th style="min-width:180px;">User</th>
                    <th style="width:120px;">Role</th>
                    <th style="width:100px;">Status</th>
                    <th style="width:100px;">Must Change</th>
                    <th style="width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                    @php
                        $initial = strtoupper(substr($user->username, 0, 1));
                        $isCurrentUser = $user->id === Auth::id();
                        $roleClass = $user->role;
                        $statusClass = $user->active ? 'active' : 'inactive';
                        $statusText = $user->active ? 'Active' : 'Disabled';
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="user-avatar {{ $roleClass }}">
                                    {{ $initial }}
                                </div>
                                <div>
                                    <div style="font-weight:600; color:#16324f;">
                                        {{ $user->username }}
                                        @if($isCurrentUser)
                                            <span style="font-size:10px; background:#e8f3f2; color:#0c625e; padding:1px 8px; border-radius:999px; margin-left:6px;">You</span>
                                        @endif
                                    </div>
                                    <div style="font-size:11px; color:#627386;">
                                        ID: #{{ $user->id }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-role {{ $roleClass }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-status {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td>
                            @if($user->must_change)
                                <span style="color:#a45100; font-weight:600; font-size:12px;">⚠️ Yes</span>
                            @else
                                <span style="color:#627386; font-size:12px;">No</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions-group">
                                @if(Auth::user()->isAdmin())
                                    @if(!$isCurrentUser)
                                        <form method="POST" action="{{ route('users.toggle-active', $user) }}" style="display:inline;">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="btn-sm btn-toggle" onclick="return confirm('{{ $user->active ? 'Disable' : 'Enable' }} this user?')">
                                                {{ $user->active ? '🔒 Disable' : '🔓 Enable' }}
                                            </button>
                                        </form>
                                        <button class="btn-sm btn-reset" onclick="showResetModal({{ $user->id }}, '{{ $user->username }}')">
                                            🔑 Reset
                                        </button>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-sm btn-delete" onclick="return confirm('Delete this user? This cannot be undone.')">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="btn-sm btn-current">👤 Current User</span>
                                    @endif
                                @else
                                    <span style="font-size:12px; color:#627386;">Read-only</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#627386;">
                            <div style="font-size:48px; margin-bottom:8px;">📭</div>
                            <div style="font-weight:600; font-size:16px;">No users found</div>
                            <div style="font-size:13px;">Add your first user using the form above.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================ -->
<!-- RESET PASSWORD MODAL -->
<!-- ============================================ -->
<div class="modal-overlay" id="resetModal">
    <div class="modal-box">
        <div class="modal-title">🔑 Reset Password</div>
        <div class="modal-subtitle">Reset password for user: <strong id="resetUsername"></strong></div>

        <form method="POST" id="resetForm">
            @csrf
            @method('POST')
            <div class="field">
                <label>New Password <span style="color:red;">*</span></label>
                <input type="password" name="new_password" placeholder="Min 6 characters" required minlength="6">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn light" onclick="closeResetModal()">Cancel</button>
                <button type="submit" class="btn" style="background:#d38b2a;">🔑 Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
    function showResetModal(userId, username) {
        document.getElementById('resetModal').classList.add('show');
        document.getElementById('resetUsername').textContent = username;
        document.getElementById('resetForm').action = '/users/' + userId + '/reset-password';
    }

    function closeResetModal() {
        document.getElementById('resetModal').classList.remove('show');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('resetModal');
        if (event.target === modal) {
            closeResetModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeResetModal();
        }
    });
</script>
@endsection