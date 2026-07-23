<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index()
    {
        $users = User::orderBy('username')->get();
        $stats = [
            'total' => $users->count(),
            'admins' => $users->where('role', 'admin')->count(),
            'editors' => $users->where('role', 'editor')->count(),
            'viewers' => $users->where('role', 'viewer')->count(),
            'active' => $users->where('active', true)->count(),
            'inactive' => $users->where('active', false)->count(),
        ];
        
        return view('users.index', compact('users', 'stats'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Administrator access is required.');
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $newUser = User::create([
                'username' => $request->username,
                'password_hash' => Hash::make($request->password),
                'role' => $request->role,
                'active' => true,
                'must_change' => true,
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'create_user',
                'details' => "{$newUser->username} ({$newUser->role})",
            ]);

            return redirect()->route('users.index')
                ->with('success', "User '{$newUser->username}' created successfully.");

        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Administrator access is required.');
        }

        if ($user->id === $authUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot change your own status.');
        }

        $user->update(['active' => !$user->active]);

        AuditLog::create([
            'username' => $authUser->username,
            'action' => 'toggle_user',
            'details' => "{$user->username} active: " . ($user->active ? 'Enabled' : 'Disabled'),
        ]);

        $status = $user->active ? 'activated' : 'deactivated';
        return redirect()->route('users.index')
            ->with('success', "User '{$user->username}' {$status} successfully.");
    }

    /**
     * Delete a user
     */
    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Administrator access is required.');
        }

        if ($user->id === $authUser->id) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $username = $user->username;

        AuditLog::create([
            'username' => $authUser->username,
            'action' => 'delete_user',
            'details' => $username,
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User '{$username}' deleted successfully.");
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Administrator access is required.');
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.index')
                ->withErrors($validator);
        }

        $user->update([
            'password_hash' => Hash::make($request->new_password),
            'must_change' => true,
        ]);

        AuditLog::create([
            'username' => $authUser->username,
            'action' => 'reset_password',
            'details' => "Password reset for {$user->username}",
        ]);

        return redirect()->route('users.index')
            ->with('success', "Password reset for '{$user->username}' successfully.");
    }

    /**
     * Show change password form
     */
    public function showChangePasswordForm()
    {
        return view('auth.change-password');
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current' => 'required|string',
            'new' => 'required|string|min:8',
            'confirm' => 'required|string|same:new',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Verify current password
        if (!Hash::check($request->current, $user->password_hash)) {
            return back()->withErrors(['current' => 'Current password is incorrect.']);
        }

        // Update password
        $user->update([
            'password_hash' => Hash::make($request->new),
            'must_change' => false,
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'change_password',
            'details' => 'Password updated successfully',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Password changed successfully.');
    }
}