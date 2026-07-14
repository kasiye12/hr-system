@extends('layouts.app')

@section('title', 'Users - TNT HR')

@section('content')
    <h1>Users & Permissions</h1>
    <div class="subtitle">Viewer users cannot edit. Editors can enter HR data. Administrators can also manage projects and users.</div>

    <div class="card">
        <h2>Create New User</h2>
        <form class="inline-form" method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="field">
                <label>Temporary Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Role</label>
                <select name="role">
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit">Create User</button>
        </form>
    </div>

    <div class="card">
        <h2>Users</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Must Change</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->username }}</td>
                        <td><span class="badge {{ $user->role }}">{{ $user->role }}</span></td>
                        <td>
                            <span class="badge {{ $user->active ? 'active' : 'inactive' }}">
                                {{ $user->active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td>{{ $user->must_change ? 'Yes' : 'No' }}</td>
                        <td>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn danger" onclick="return confirm('Delete this user?')">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:20px;">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection