<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::orderByRaw('COALESCE(slot, 999), name')->get();
        $categories = Category::where('is_reconciliation', false)
            ->orderBy('row_order')
            ->get();
        $canEdit = Auth::user()->canEdit();

        return view('projects.index', compact('projects', 'categories', 'canEdit'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('projects.index')
                ->with('error', 'You do not have permission to add projects.');
        }

        $request->validate([
            'name' => 'required|string|max:200|unique:projects',
            'slot' => 'nullable|integer|min:1',
            'status' => 'required|in:Active,Inactive',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'slot' => $request->slot,
            'status' => $request->status,
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'add_project',
            'details' => $project->name,
        ]);

        return redirect()->route('projects.index')
            ->with('success', "Project '{$project->name}' created successfully.");
    }

    public function update(Request $request, Project $project)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('projects.index')
                ->with('error', 'You do not have permission to update projects.');
        }

        $request->validate([
            'name' => 'required|string|max:200|unique:projects,name,' . $project->id,
            'slot' => 'nullable|integer|min:1',
            'status' => 'required|in:Active,Inactive',
        ]);

        $oldName = $project->name;
        $project->update([
            'name' => $request->name,
            'slot' => $request->slot,
            'status' => $request->status,
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'update_project',
            'details' => "{$oldName} -> {$project->name}",
        ]);

        return redirect()->route('projects.index')
            ->with('success', "Project '{$project->name}' updated successfully.");
    }

    public function destroy(Project $project)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('projects.index')
                ->with('error', 'You do not have permission to delete projects.');
        }

        if (Project::count() <= 1) {
            return redirect()->route('projects.index')
                ->with('error', 'At least one project must remain in the application.');
        }

        $name = $project->name;

        // Delete related daily entries
        $project->dailyEntries()->delete();
        $project->delete();

        AuditLog::create([
            'username' => $user->username,
            'action' => 'delete_project',
            'details' => $name,
        ]);

        return redirect()->route('projects.index')
            ->with('success', "Project '{$name}' deleted successfully.");
    }

    public function storeCategory(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('projects.index')
                ->with('error', 'You do not have permission to add job titles.');
        }

        $request->validate([
            'code' => 'required|string|max:50|unique:categories',
            'job_title' => 'required|string|max:200',
            'department' => 'required|string|max:200',
            'employment_type' => 'required|in:Permanent,Contract,Daily',
            'row_order' => 'nullable|integer|min:1',
        ]);

        $rowOrder = $request->row_order ?? Category::max('row_order') + 1;

        $category = Category::create([
            'code' => $request->code,
            'name' => $request->job_title,
            'department' => $request->department,
            'employment_type' => $request->employment_type,
            'row_order' => $rowOrder,
            'is_reconciliation' => false,
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'add_category',
            'details' => "{$category->code} | {$category->name}",
        ]);

        return redirect()->route('projects.index')
            ->with('success', "Job title '{$category->name}' created successfully.");
    }

    public function updateCategory(Request $request, Category $category)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('projects.index')
                ->with('error', 'You do not have permission to update job titles.');
        }

        $request->validate([
            'code' => 'required|string|max:50|unique:categories,code,' . $category->id,
            'job_title' => 'required|string|max:200',
            'department' => 'required|string|max:200',
            'employment_type' => 'required|in:Permanent,Contract,Daily',
            'row_order' => 'nullable|integer|min:1',
        ]);

        $oldName = $category->name;
        $category->update([
            'code' => $request->code,
            'name' => $request->job_title,
            'department' => $request->department,
            'employment_type' => $request->employment_type,
            'row_order' => $request->row_order ?? $category->row_order,
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'update_category',
            'details' => "{$oldName} -> {$category->name}",
        ]);

        return redirect()->route('projects.index')
            ->with('success', "Job title '{$category->name}' updated successfully.");
    }

    public function destroyCategory(Category $category)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('projects.index')
                ->with('error', 'You do not have permission to delete job titles.');
        }

        if ($category->is_reconciliation) {
            return redirect()->route('projects.index')
                ->with('error', 'Cannot delete reconciliation category.');
        }

        $name = $category->name;
        $code = $category->code;

        // Delete related daily entries
        $category->dailyEntries()->delete();
        $category->delete();

        AuditLog::create([
            'username' => $user->username,
            'action' => 'delete_category',
            'details' => "{$code} | {$name}",
        ]);

        return redirect()->route('projects.index')
            ->with('success', "Job title '{$name}' deleted successfully.");
    }
}