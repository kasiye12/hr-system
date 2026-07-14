<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display the user management page (admin only)
     */
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $users = User::orderBy('username')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Create a new user (admin only)
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $request->validate([
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password_hash' => Hash::make($request->password),
            'role' => $request->role,
            'active' => true,
            'must_change' => true,
        ]);

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'create_user',
            'details' => "{$user->username} ({$user->role})",
        ]);

        return redirect()->route('users.index')
            ->with('success', "User '{$user->username}' created successfully.");
    }

    /**
     * Show the change password form
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
        $request->validate([
            'current' => 'required|string',
            'new' => 'required|string|min:8',
            'confirm' => 'required|string|same:new',
        ]);

        $user = Auth::user();

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

    /**
     * Toggle user active status (admin only)
     */
    public function toggleActive(User $user)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $user->update(['active' => !$user->active]);

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'toggle_user',
            'details' => "{$user->username} active: {$user->active}",
        ]);

        return redirect()->route('users.index')
            ->with('success', "User '{$user->username}' status updated.");
    }

    /**
     * Delete a user (admin only)
     */
    public function destroy(User $user)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $username = $user->username;

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'delete_user',
            'details' => $username,
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User '{$username}' deleted successfully.");
    }

    /**
     * Reset user password (admin only)
     */
    public function resetPassword(Request $request, User $user)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')
                ->with('error', 'Administrator access is required.');
        }

        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user->update([
            'password_hash' => Hash::make($request->new_password),
            'must_change' => true,
        ]);

        AuditLog::create([
            'username' => Auth::user()->username,
            'action' => 'reset_password',
            'details' => "Password reset for {$user->username}",
        ]);

        return redirect()->route('users.index')
            ->with('success', "Password reset for '{$user->username}' successfully.");
    }
}