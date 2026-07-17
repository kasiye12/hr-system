@extends('layouts.app')

@section('title', 'Project Setup - TNT HR')

@section('content')
    <style>
        .setup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .setup-header h1 {
            margin: 0;
        }
        .setup-header .subtitle {
            color: var(--muted);
            font-size: 14px;
        }

        .section-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 10px;
            padding: 20px 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 7px rgba(21,49,78,0.04);
        }
        .section-card .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .section-card .section-title h2 {
            margin: 0;
            font-size: 18px;
            color: #16324f;
        }
        .section-card .section-title .count {
            font-size: 13px;
            color: var(--muted);
            background: #f8fbfd;
            padding: 2px 12px;
            border-radius: 999px;
        }

        .form-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            align-items: end;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            align-items: end;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            align-items: end;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .field label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .field input,
        .field select,
        .field textarea {
            padding: 8px 12px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 13px;
            width: 100%;
            min-width: 0;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: #1b7f79;
            box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.1);
        }
        .field textarea {
            resize: vertical;
            min-height: 50px;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 12px;
        }
        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table-wrap th {
            background: #eff4f8;
            color: #34495e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 2px solid #dce5ee;
            position: sticky;
            top: 0;
        }
        .table-wrap td {
            padding: 10px 14px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .table-wrap tr:hover td {
            background: #f8fbfd;
        }
        .table-wrap .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-status.active {
            background: #dcf5e7;
            color: #12643c;
        }
        .badge-status.inactive {
            background: #eee;
            color: #666;
        }
        .badge-status.permanent {
            background: #dcf5e7;
            color: #12643c;
        }
        .badge-status.contract {
            background: #e6e3ff;
            color: #4b3bad;
        }
        .badge-status.daily {
            background: #fff5e5;
            color: #a45100;
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
            display: inline-block;
        }
        .btn-sm:hover {
            filter: brightness(0.93);
        }
        .btn-sm.btn-edit {
            background: #e9eff5;
            color: #23384d;
        }
        .btn-sm.btn-edit:hover {
            background: #dce5ee;
        }
        .btn-sm.btn-delete {
            background: #ffeded;
            color: #a61b1b;
        }
        .btn-sm.btn-delete:hover {
            background: #ffdddd;
        }
        .btn-sm.btn-toggle {
            background: #e8f3f2;
            color: #0c625e;
        }
        .btn-sm.btn-toggle:hover {
            background: #d5ebe9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #627386;
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .empty-state h4 {
            margin: 0 0 4px;
            color: #24384c;
        }
        .empty-state p {
            margin: 0;
            font-size: 13px;
        }

        .inline-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }

        .update-box {
            background: #f8fbfd;
            border: 2px solid #397a9f;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .update-box .update-title {
            color: #16324f;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 15px;
        }

        @media (max-width: 768px) {
            .form-grid-4 { grid-template-columns: 1fr 1fr; }
            .form-grid-3 { grid-template-columns: 1fr 1fr; }
            .form-grid-2 { grid-template-columns: 1fr; }
            .inline-form { flex-direction: column; align-items: stretch; }
            .inline-form .field { width: 100%; }
            .section-card .section-title { flex-direction: column; align-items: start; gap: 8px; }
        }
        @media (max-width: 480px) {
            .form-grid-4 { grid-template-columns: 1fr; }
            .form-grid-3 { grid-template-columns: 1fr; }
            .table-wrap table { font-size: 12px; }
            .table-wrap th, .table-wrap td { padding: 6px 8px; }
        }
    </style>

    <!-- ============================================ -->
    <!-- HEADER -->
    <!-- ============================================ -->
    <div class="setup-header">
        <div>
            <h1>⚙️ Project and Job Title Setup</h1>
            <div class="subtitle">Manage projects, departments, and job titles for daily manpower input</div>
        </div>
        <div>
            <span class="badge" style="background:#e8f3f2; color:#0c625e; font-size:12px; padding:4px 12px;">
                📌 {{ $projects->count() }} Projects · {{ $categories->count() }} Job Titles
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    @if(!$canEdit)
        <div class="alert warning">👁️ Viewer access: project and job title setup is read-only.</div>
    @endif

    <!-- ============================================ -->
    <!-- PROJECTS SECTION -->
    <!-- ============================================ -->
    <div class="section-card">
        <div class="section-title">
            <h2>🏗️ Projects</h2>
            <span class="count">{{ $projects->count() }} projects</span>
        </div>

        @if($canEdit)
            <form class="inline-form" method="post" action="{{ route('projects.store') }}">
                @csrf
                <div class="field">
                    <label>Project Name <span style="color:red;">*</span></label>
                    <input type="text" name="name" placeholder="Enter project name" required>
                </div>
                <div class="field">
                    <label>Display Order</label>
                    <input type="number" min="1" step="1" name="slot" placeholder="Order">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit">➕ Add Project</button>
            </form>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:100px;">Order</th>
                        <th>Project Name</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            @if($canEdit)
                                <form method="post" action="{{ route('projects.update', $project) }}" style="display:contents;">
                                    @csrf
                                    @method('PUT')
                                    <td><input type="number" min="1" step="1" name="slot" value="{{ $project->slot ?? '' }}" style="width:70px; padding:4px 6px; border:1px solid #dce5ee; border-radius:4px;"></td>
                                    <td><input type="text" name="name" value="{{ $project->name }}" required style="width:100%; padding:4px 6px; border:1px solid #dce5ee; border-radius:4px;"></td>
                                    <td>
                                        <select name="status" style="width:100%; padding:4px 6px; border:1px solid #dce5ee; border-radius:4px;">
                                            <option value="Active" @if($project->status == 'Active') selected @endif>Active</option>
                                            <option value="Inactive" @if($project->status != 'Active') selected @endif>Inactive</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button type="submit" class="btn-sm btn-edit">💾 Save</button>
                                            <button type="button" class="btn-sm btn-delete" onclick="if(confirm('Delete this project and all related data?')) { this.closest('tr').querySelector('form').action = '{{ route('projects.destroy', $project) }}'; this.closest('tr').querySelector('form').querySelector('input[name=_method]').value = 'DELETE'; this.closest('tr').querySelector('form').submit(); }">🗑️ Delete</button>
                                        </div>
                                    </td>
                                </form>
                            @else
                                <td>{{ $project->slot ?? '' }}</td>
                                <td><strong>{{ $project->name }}</strong></td>
                                <td><span class="badge-status {{ strtolower($project->status) }}">{{ $project->status }}</span></td>
                                <td><span class="small" style="color:var(--muted);">Read-only</span></td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="icon">📭</div>
                                    <h4>No projects configured</h4>
                                    <p>Add a project using the form above.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- JOB TITLES SECTION -->
    <!-- ============================================ -->
    <div class="section-card">
        <div class="section-title">
            <h2>💼 Job Titles</h2>
            <span class="count">{{ $categories->count() }} job titles</span>
        </div>

        @if($canEdit)
            <!-- Add Job Title -->
            <form method="post" class="form-grid-4" action="{{ route('projects.categories.store') }}">
                @csrf
                <div class="field">
                    <label>Department <span style="color:red;">*</span></label>
                    <input type="text" name="department" placeholder="e.g., Engineering" required>
                </div>
                <div class="field">
                    <label>Code <span style="color:red;">*</span></label>
                    <input type="text" name="code" placeholder="e.g., ENG-001" required>
                </div>
                <div class="field">
                    <label>Job Title <span style="color:red;">*</span></label>
                    <input type="text" name="job_title" placeholder="e.g., Senior Engineer" required>
                </div>
                <div class="field">
                    <label>Employment Type <span style="color:red;">*</span></label>
                    <select name="employment_type" required>
                        <option value="Permanent">Permanent</option>
                        <option value="Contract">Contract</option>
                        <option value="Daily">Daily</option>
                    </select>
                </div>
                <div class="field">
                    <label>Display Order</label>
                    <input type="number" min="1" step="1" name="row_order" placeholder="Order">
                </div>
                <div>
                    <button type="submit">➕ Add Job Title</button>
                </div>
            </form>

            <!-- Update Job Title -->
            <div class="update-box">
                <div class="update-title">✏️ Update Existing Job Title</div>
                <form method="post" class="form-grid-4" id="updateCategoryForm" action="{{ route('projects.categories.update', 0) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="field">
                        <label>Select Job Title <span style="color:red;">*</span></label>
                        <select name="category_id" id="categorySelect" required onchange="loadCategoryData(this)">
                            <option value="">-- Select Job Title --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" 
                                    data-code="{{ $cat->code }}"
                                    data-name="{{ $cat->name }}"
                                    data-department="{{ $cat->department }}"
                                    data-type="{{ $cat->employment_type }}"
                                    data-order="{{ $cat->row_order }}">
                                    {{ $cat->department }} - {{ $cat->code }} ({{ $cat->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Department <span style="color:red;">*</span></label>
                        <input type="text" name="department" id="updateDepartment" required>
                    </div>
                    <div class="field">
                        <label>Code <span style="color:red;">*</span></label>
                        <input type="text" name="code" id="updateCode" required>
                    </div>
                    <div class="field">
                        <label>Job Title <span style="color:red;">*</span></label>
                        <input type="text" name="job_title" id="updateName" required>
                    </div>
                    <div class="field">
                        <label>Employment Type <span style="color:red;">*</span></label>
                        <select name="employment_type" id="updateType" required>
                            <option value="Permanent">Permanent</option>
                            <option value="Contract">Contract</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Display Order</label>
                        <input type="number" min="1" step="1" name="row_order" id="updateOrder">
                    </div>
                    <div>
                        <button type="submit" class="btn" style="background:#397a9f;">💾 Update Job Title</button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Job Titles Table -->
        <div class="table-wrap" style="margin-top:16px;">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px;">Order</th>
                        <th>Department</th>
                        <th style="width:100px;">Code</th>
                        <th>Job Title</th>
                        <th style="width:120px;">Employment Type</th>
                        <th style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr id="category-row-{{ $category->id }}">
                            <td>{{ $category->row_order }}</td>
                            <td><strong>{{ $category->department }}</strong></td>
                            <td><span class="badge" style="background:#e8f3f2; color:#0c625e;">{{ $category->code }}</span></td>
                            <td>{{ $category->name }}</td>
                            <td>
                                <span class="badge-status {{ strtolower($category->employment_type) }}">
                                    {{ $category->employment_type }}
                                </span>
                            </td>
                            <td>
                                @if($canEdit)
                                    <div class="actions">
                                        <button class="btn-sm btn-edit" onclick="loadCategoryForEdit({{ $category->id }})">✏️ Edit</button>
                                        <form method="post" style="display:inline;" action="{{ route('projects.categories.destroy', $category->id) }}" onsubmit="return confirm('Delete this job title? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-sm btn-delete">🗑️ Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="small" style="color:var(--muted);">Read-only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="icon">📭</div>
                                    <h4>No job titles configured</h4>
                                    <p>Add job titles using the form above.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

  <script>
    // ============================================
    // LOAD CATEGORY DATA FOR EDIT
    // ============================================
    function loadCategoryData(select) {
        const option = select.options[select.selectedIndex];
        if (!option.value) {
            document.getElementById('updateDepartment').value = '';
            document.getElementById('updateCode').value = '';
            document.getElementById('updateName').value = '';
            document.getElementById('updateType').value = 'Permanent';
            document.getElementById('updateOrder').value = '';
            return;
        }

        document.getElementById('updateDepartment').value = option.dataset.department || '';
        document.getElementById('updateCode').value = option.dataset.code || '';
        document.getElementById('updateName').value = option.dataset.name || '';
        document.getElementById('updateType').value = option.dataset.type || 'Permanent';
        document.getElementById('updateOrder').value = option.dataset.order || '';
    }

    function loadCategoryForEdit(categoryId) {
        // Find the select and set its value
        const select = document.getElementById('categorySelect');
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value == categoryId) {
                select.selectedIndex = i;
                loadCategoryData(select);
                break;
            }
        }
        // Scroll to update section
        document.querySelector('.update-box').scrollIntoView({ behavior: 'smooth' });
    }

    // ============================================
    // UPDATE FORM ACTION - FIXED
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('categorySelect');
        const updateForm = document.getElementById('updateCategoryForm');

        // Set initial action when page loads
        if (categorySelect && categorySelect.value) {
            const categoryId = categorySelect.value;
            const baseUrl = '/projects/categories/';
            updateForm.action = baseUrl + categoryId;
        }

        categorySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const baseUrl = '/projects/categories/';
                updateForm.action = baseUrl + selectedOption.value;
            }
        });
    });
</script>
@endsection