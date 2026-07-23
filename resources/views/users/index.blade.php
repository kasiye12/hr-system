@extends('layout')

@section('title', 'User Management | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-header h1 { font-size:20px; font-weight:800; color:#111827; margin:0; }
    
    .card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:20px; }
    .card-header { padding:16px 20px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; }
    .card-header h3 { font-size:15px; font-weight:700; color:#111827; margin:0; }
    .card-body { padding:20px; }
    
    .form-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:16px; }
    .form-group { display:flex; flex-direction:column; gap:4px; }
    .form-group label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .form-group input, .form-group select { padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; }
    .form-group input:focus, .form-group select:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,0.1); }
    
    .btn { display:inline-flex; align-items:center; gap:5px; padding:9px 16px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; border:none; text-decoration:none; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-danger { background:#dc2626; color:#fff; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    .btn-sm { padding:5px 10px; font-size:11px; }
    
    table { width:100%; border-collapse:collapse; font-size:13px; }
    table th { background:#f9fafb; padding:10px 14px; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; text-align:left; border-bottom:1px solid #e5e7eb; }
    table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
    table tbody tr:hover td { background:#f9fafb; }
    
    .badge { display:inline-block; padding:3px 10px; border-radius:100px; font-size:10px; font-weight:600; }
    .badge-success { background:#ecfdf5; color:#059669; }
    .badge-danger { background:#fef2f2; color:#dc2626; }
    .badge-info { background:#eef2ff; color:#4f46e5; }
    .badge-warning { background:#fffbeb; color:#d97706; }
    
    .alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
    .alert-success { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
    .alert-error { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
    
    /* Modal */
    .modal-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center; }
    .modal-overlay.show { display:flex; }
    .modal-box { background:#fff; border-radius:14px; width:90%; max-width:500px; box-shadow:0 20px 50px rgba(0,0,0,0.3); }
    .modal-header { padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
    .modal-header h4 { margin:0; font-size:16px; font-weight:700; }
    .modal-header .close { font-size:22px; cursor:pointer; color:#6b7280; background:none; border:none; }
    .modal-body { padding:20px; }
    .modal-footer { padding:12px 20px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:8px; }
    .btn-cancel { background:#fff; color:#374151; border:1px solid #d1d5db; padding:8px 16px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; }
    .btn-save { background:#4f46e5; color:#fff; border:none; padding:8px 16px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; }
    
    @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div>
        <h1>👥 User Management</h1>
        <p style="color:#6b7280; font-size:12px;">Manage system users and their roles</p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">➕ Add User</button>
</div>

@if(session('success'))<div class="alert alert-success">✅ {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error">❌ {{ session('error') }}</div>@endif

<!-- Users Table -->
<div class="card" style="padding:0;">
    <div class="card-header"><h3>📋 Users ({{ $users->count() }})</h3></div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>#</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th style="text-align:center;">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $user->username }}</strong></td>
                    <td>{{ $user->email ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $user->role === 'admin' ? 'badge-info' : ($user->role === 'editor' ? 'badge-warning' : 'badge-success') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $user->active ? 'badge-success' : 'badge-danger' }}">
                            {{ $user->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td style="font-size:11px;">{{ $user->created_at->format('M d, Y') }}</td>
                    <td style="text-align:center;">
                        <div style="display:flex; gap:4px; justify-content:center;">
                            <button class="btn btn-outline btn-sm" onclick="openEditModal({{ $user->id }}, '{{ $user->username }}', '{{ $user->email }}', '{{ $user->role }}')">
                                ✏️
                            </button>
                            <form method="POST" action="{{ route('users.toggle-active', $user->id) }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-sm {{ $user->active ? 'btn-danger' : 'btn-primary' }}" style="{{ $user->active ? '' : 'background:#059669;' }}">
                                    {{ $user->active ? '🔒' : '🔓' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.reset-password', $user->id) }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-outline btn-sm" onclick="return confirm('Reset password for {{ $user->username }}?')">🔑</button>
                            </form>
                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm" style="background:#dc2626; color:#fff;" onclick="return confirm('Delete {{ $user->username }}?')">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:30px;color:#6b7280;">No users found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ADD USER MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-header">
            <h4>➕ Add New User</h4>
            <button class="close" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                    <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
                    <div class="form-group"><label>Role *</label>
                        <select name="role" required>
                            <option value="admin">Admin</option>
                            <option value="editor">Editor</option>
                            <option value="viewer" selected>Viewer</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn-save">💾 Save User</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <h4>✏️ Edit User</h4>
            <button class="close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>Username *</label><input type="text" name="username" id="editUsername" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail"></div>
                    <div class="form-group"><label>New Password (leave blank to keep)</label><input type="password" name="password"></div>
                    <div class="form-group"><label>Role *</label>
                        <select name="role" id="editRole" required>
                            <option value="admin">Admin</option>
                            <option value="editor">Editor</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save">💾 Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() { document.getElementById('addModal').classList.add('show'); }
function closeAddModal() { document.getElementById('addModal').classList.remove('show'); }
function openEditModal(id, username, email, role) {
    document.getElementById('editForm').action = '/users/' + id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() { document.getElementById('editModal').classList.remove('show'); }
</script>
@endsection
