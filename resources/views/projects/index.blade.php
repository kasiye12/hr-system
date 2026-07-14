@extends('layouts.app')

@section('title', 'Project Setup - TNT HR Reporting')

@section('content')
    <h1>Project and Job Title Setup</h1>
    <div class="subtitle">Add, update or delete projects and job titles. Dashboard and reports update automatically from the saved setup and daily entries.</div>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert danger">{{ session('error') }}</div>
    @endif

    @if($canEdit)
        <div class="card">
            <h2>Add Project</h2>
            <form class="inline" method="post" action="{{ route('projects.store') }}">
                @csrf
                <div class="field">
                    <label>Project Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="field">
                    <label>Display Order</label>
                    <input type="number" min="1" step="1" name="slot">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit">Add Project</button>
            </form>
        </div>
    @else
        <div class="alert warn">Viewer access: project setup is read-only.</div>
    @endif

    <div class="card" style="margin-top:16px">
        <h2>Projects</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Display Order</th>
                        <th>Project Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            @if($canEdit)
                                <form method="post" action="{{ route('projects.update', $project) }}">
                                    @csrf
                                    @method('PUT')
                                    <td><input type="number" min="1" step="1" name="slot" value="{{ $project->slot ?? '' }}"></td>
                                    <td><input type="text" name="name" value="{{ $project->name }}" required></td>
                                    <td>
                                        <select name="status">
                                            <option value="Active" @if($project->status == 'Active') selected @endif>Active</option>
                                            <option value="Inactive" @if($project->status != 'Active') selected @endif>Inactive</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit">Save</button>
                                        <button class="btn danger" type="button" onclick="if(confirm('Delete this project and all of its daily records? This cannot be undone.')) { this.closest('form').action = '{{ route('projects.destroy', $project) }}'; this.closest('form').method = 'POST'; this.closest('form').querySelector('input[name=_method]').value = 'DELETE'; this.closest('form').submit(); }">Delete</button>
                                    </td>
                                </form>
                            @else
                                <td>{{ $project->slot ?? '' }}</td>
                                <td>{{ $project->name }}</td>
                                <td><span class="badge {{ strtolower($project->status) }}">{{ $project->status }}</span></td>
                                <td></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No projects configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Add Job Title</h2>
        @if($canEdit)
            <form class="applicant-form" method="post" action="{{ route('projects.categories.store') }}">
                @csrf
                <div class="field">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
                <div class="field">
                    <label>Code</label>
                    <input type="text" name="code" required>
                </div>
                <div class="field">
                    <label>Job Title</label>
                    <input type="text" name="job_title" required>
                </div>
                <div class="field">
                    <label>Employment Type</label>
                    <select name="employment_type">
                        <option value="Permanent">Permanent</option>
                        <option value="Contract">Contract</option>
                        <option value="Daily">Daily</option>
                    </select>
                </div>
                <div class="field">
                    <label>Display Order</label>
                    <input type="number" min="1" step="1" name="row_order">
                </div>
                <div>
                    <button type="submit">Add Job Title</button>
                </div>
            </form>
        @else
            <div class="alert warn">Viewer access: job-title setup is read-only.</div>
        @endif
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Job Titles</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Department</th>
                        <th>Code</th>
                        <th>Job Title</th>
                        <th>Employment Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            @if($canEdit)
                                <form method="post" action="{{ route('projects.categories.update', $category) }}">
                                    @csrf
                                    @method('PUT')
                                    <td><input type="number" min="1" step="1" name="row_order" value="{{ $category->row_order }}"></td>
                                    <td><input type="text" name="department" value="{{ $category->department }}" required></td>
                                    <td><input type="text" name="code" value="{{ $category->code }}" required></td>
                                    <td><input type="text" name="job_title" value="{{ $category->name }}" required></td>
                                    <td>
                                        <select name="employment_type">
                                            <option value="Permanent" @if($category->employment_type == 'Permanent') selected @endif>Permanent</option>
                                            <option value="Contract" @if($category->employment_type == 'Contract') selected @endif>Contract</option>
                                            <option value="Daily" @if($category->employment_type == 'Daily') selected @endif>Daily</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit">Save</button>
                                        <button class="btn danger" type="button" onclick="if(confirm('Delete this job title and all linked daily entries? This cannot be undone.')) { this.closest('form').action = '{{ route('projects.categories.destroy', $category) }}'; this.closest('form').method = 'POST'; this.closest('form').querySelector('input[name=_method]').value = 'DELETE'; this.closest('form').submit(); }">Delete</button>
                                    </td>
                                </form>
                            @else
                                <td>{{ $category->row_order }}</td>
                                <td>{{ $category->department }}</td>
                                <td>{{ $category->code }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->employment_type }}</td>
                                <td></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No job titles configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection