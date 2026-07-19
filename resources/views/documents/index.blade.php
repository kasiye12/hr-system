@extends('layout')

@section('title', 'Employee Documents')

@section('content')
<h1>Employee Documents</h1>
<p class="subtitle">Upload and manage employee documents linked to applicants</p>

<div class="card">
    <h3>Upload New Document</h3>
    <form action="{{ route('documents.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid two" style="margin-bottom: 16px;">
            <!-- Searchable Employee Select -->
            <div class="field">
                <label for="employee_search">Search & Select Employee</label>
                <div style="position: relative;">
                    <input type="text" 
                           id="employee_search" 
                           placeholder="Type employee name to search..." 
                           autocomplete="off"
                           style="width: 100%; padding-right: 30px;"
                           onkeyup="filterEmployees()"
                           onfocus="showDropdown()">
                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #627386; pointer-events: none;">🔍</span>
                    <div id="employee_dropdown" 
                         style="display: none; position: absolute; top: 100%; left: 0; right: 0; 
                                max-height: 250px; overflow-y: auto; background: white; 
                                border: 1px solid #bac8d6; border-radius: 6px; z-index: 1000;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-top: 2px;">
                        <div style="padding: 8px 12px; color: #627386; font-style: italic; cursor: pointer;" 
                             onclick="clearEmployeeSelection()">
                            -- Clear Selection (Manual Entry) --
                        </div>
                        @foreach($applicants as $applicant)
                            <div class="employee-option" 
                                 data-id="{{ $applicant->id }}"
                                 data-name="{{ $applicant->first_name }} {{ $applicant->surname }}"
                                 data-position="{{ $applicant->position->name ?? 'N/A' }}"
                                 style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f0f0;
                                        transition: background 0.15s;"
                                 onmouseover="this.style.background='#e8f3f2'"
                                 onmouseout="this.style.background='white'"
                                 onclick="selectEmployee(this)">
                                <div style="font-weight: 600; color: #1b2635;">
                                    {{ $applicant->first_name }} {{ $applicant->surname }}
                                </div>
                                <div style="font-size: 12px; color: #627386;">
                                    {{ $applicant->position->name ?? 'No Position' }}
                                    @if($applicant->phone_primary)
                                        · 📱 {{ $applicant->phone_primary }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(count($applicants) == 0)
                            <div style="padding: 15px; text-align: center; color: #627386;">
                                No employees found
                            </div>
                        @endif
                    </div>
                </div>
                <input type="hidden" id="applicant_id" name="applicant_id" value="{{ old('applicant_id') }}">
                <small style="color: #627386; margin-top: 4px; display: block;">
                    <span id="selected_count">{{ count($applicants) }}</span> employees available · Start typing to search
                </small>
            </div>
            
            <div class="field">
                <label for="employee_name">Employee Name *</label>
                <input type="text" id="employee_name" name="employee_name" 
                       value="{{ old('employee_name') }}" required maxlength="200"
                       placeholder="Auto-filled from selection or type manually">
            </div>
            
            <div class="field">
                <label for="document_type">Document Type *</label>
                <select id="document_type" name="document_type" required>
                    <option value="">Select Type</option>
                    <option value="Contract" {{ old('document_type') == 'Contract' ? 'selected' : '' }}>📝 Contract</option>
                    <option value="ID Card" {{ old('document_type') == 'ID Card' ? 'selected' : '' }}>🪪 ID Card</option>
                    <option value="Passport" {{ old('document_type') == 'Passport' ? 'selected' : '' }}>🛂 Passport</option>
                    <option value="Visa" {{ old('document_type') == 'Visa' ? 'selected' : '' }}>🛂 Visa</option>
                    <option value="Certificate" {{ old('document_type') == 'Certificate' ? 'selected' : '' }}>📜 Certificate</option>
                    <option value="CV" {{ old('document_type') == 'CV' ? 'selected' : '' }}>📄 CV/Resume</option>
                    <option value="Medical" {{ old('document_type') == 'Medical' ? 'selected' : '' }}>🏥 Medical Report</option>
                    <option value="Insurance" {{ old('document_type') == 'Insurance' ? 'selected' : '' }}>🛡️ Insurance</option>
                    <option value="Other" {{ old('document_type') == 'Other' ? 'selected' : '' }}>📎 Other</option>
                </select>
            </div>
            
            <div class="field">
                <label for="document">File * (Max 10MB)</label>
                <input type="file" id="document" name="document" required 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
            </div>
            
            <div class="field">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" 
                       value="{{ old('description') }}" maxlength="500" 
                       placeholder="Optional description">
            </div>
        </div>
        <button type="submit" class="btn">📤 Upload Document</button>
    </form>
    
    @if ($errors->any())
        <div class="alert error" style="margin-top: 16px;">
            <strong>⚠️ Please fix the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<!-- Filters -->
<div class="card">
    <h3>🔍 Filter Documents</h3>
    <form action="{{ route('documents.index') }}" method="GET" class="inline-form">
        <div class="field">
            <label for="search">Search Employee</label>
            <input type="text" id="search" name="search" value="{{ request('search') }}" 
                   placeholder="Search by name...">
        </div>
        <div class="field">
            <label for="type">Document Type</label>
            <select id="type" name="type">
                <option value="">All Types</option>
                <option value="Contract" {{ request('type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                <option value="ID Card" {{ request('type') == 'ID Card' ? 'selected' : '' }}>ID Card</option>
                <option value="Passport" {{ request('type') == 'Passport' ? 'selected' : '' }}>Passport</option>
                <option value="Visa" {{ request('type') == 'Visa' ? 'selected' : '' }}>Visa</option>
                <option value="Certificate" {{ request('type') == 'Certificate' ? 'selected' : '' }}>Certificate</option>
                <option value="CV" {{ request('type') == 'CV' ? 'selected' : '' }}>CV/Resume</option>
                <option value="Medical" {{ request('type') == 'Medical' ? 'selected' : '' }}>Medical</option>
                <option value="Insurance" {{ request('type') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
                <option value="Other" {{ request('type') == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div class="field">
            <label for="filter_applicant">Linked Employee</label>
            <select id="filter_applicant" name="applicant_id">
                <option value="">All Employees</option>
                @foreach($applicants as $applicant)
                    <option value="{{ $applicant->id }}" {{ request('applicant_id') == $applicant->id ? 'selected' : '' }}>
                        {{ $applicant->first_name }} {{ $applicant->surname }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <button type="submit" class="btn">🔍 Filter</button>
            <a href="{{ route('documents.index') }}" class="btn secondary">✖ Clear</a>
        </div>
    </form>
</div>

<!-- Statistics -->
<div class="card">
    <h3>📊 Document Statistics</h3>
    <div class="grid kpis">
        <div class="kpi">
            <div class="label">Total Documents</div>
            <div class="value">{{ $stats['total'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Total Size</div>
            <div class="value">
                @if($stats['total_size'] >= 1048576)
                    {{ number_format($stats['total_size'] / 1048576, 2) }} MB
                @elseif($stats['total_size'] >= 1024)
                    {{ number_format($stats['total_size'] / 1024, 2) }} KB
                @else
                    {{ $stats['total_size'] ?? 0 }} bytes
                @endif
            </div>
        </div>
        @foreach($stats['by_type'] as $type)
        <div class="kpi">
            <div class="label">{{ $type->document_type }}</div>
            <div class="value">{{ $type->count }}</div>
        </div>
        @endforeach
    </div>
</div>

<!-- Documents Table -->
<div class="card">
    <h3>📁 Uploaded Documents</h3>
    
    @if($documents->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Document</th>
                <th>Employee</th>
                <th>Linked Applicant</th>
                <th>Type</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th>By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
            <tr>
                <td>
                    <span style="font-size: 16px;">{{ $doc->file_icon }}</span>
                    <strong>{{ $doc->document_name }}</strong>
                    @if($doc->description)
                        <br><span class="small">{{ $doc->description }}</span>
                    @endif
                </td>
                <td>{{ $doc->employee_name }}</td>
                <td>
                    @if($doc->applicant)
                        <span class="badge active">
                            👤 {{ $doc->applicant->first_name }} {{ $doc->applicant->surname }}
                        </span>
                    @else
                        <span class="badge inactive">Not linked</span>
                    @endif
                </td>
                <td><span class="badge active">{{ $doc->file_icon }} {{ $doc->document_type }}</span></td>
                <td class="num">{{ $doc->file_size_formatted }}</td>
                <td>{{ $doc->uploaded_at->format('Y-m-d H:i') }}</td>
                <td>{{ $doc->uploaded_by }}</td>
                <td class="actions">
                    <a href="{{ route('documents.download', $doc->id) }}" class="btn light" title="Download">⬇️</a>
                    @if(auth()->user()->canEdit())
                    <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn danger" title="Delete" 
                                onclick="return confirm('Delete document: {{ $doc->document_name }}?')">🗑️</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 16px;">
        {{ $documents->links() }}
    </div>
    @else
    <div class="empty">
        <p style="font-size: 48px;">📁</p>
        <p>No documents found</p>
        @if(request()->has('search') || request()->has('type') || request()->has('applicant_id'))
            <a href="{{ route('documents.index') }}" class="btn light">Clear Filters</a>
        @endif
    </div>
    @endif
</div>

<style>
    /* Custom scrollbar for dropdown */
    #employee_dropdown::-webkit-scrollbar {
        width: 6px;
    }
    #employee_dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    #employee_dropdown::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    #employee_dropdown::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    
    /* Highlight matching text */
    .highlight {
        background: #fff3cd;
        padding: 1px 3px;
        border-radius: 2px;
    }
    
    /* Selected employee */
    .employee-option.selected {
        background: #e8f3f2 !important;
        border-left: 3px solid var(--accent);
    }
</style>

<script>
    // Store all employees for filtering
    const allEmployees = [
        @foreach($applicants as $applicant)
        {
            id: {{ $applicant->id }},
            name: "{{ $applicant->first_name }} {{ $applicant->surname }}",
            position: "{{ $applicant->position->name ?? 'N/A' }}",
            phone: "{{ $applicant->phone_primary ?? '' }}"
        },
        @endforeach
    ];
    
    let selectedEmployeeId = {{ old('applicant_id', 'null') }};
    
    // Show dropdown on focus
    function showDropdown() {
        document.getElementById('employee_dropdown').style.display = 'block';
        filterEmployees();
    }
    
    // Filter employees based on search input
    function filterEmployees() {
        const searchInput = document.getElementById('employee_search');
        const searchTerm = searchInput.value.toLowerCase().trim();
        const dropdown = document.getElementById('employee_dropdown');
        const employeeOptions = dropdown.getElementsByClassName('employee-option');
        const clearOption = dropdown.querySelector('div:first-child');
        
        let visibleCount = 0;
        
        // Filter employee options
        Array.from(employeeOptions).forEach(option => {
            const name = option.getAttribute('data-name').toLowerCase();
            const position = option.getAttribute('data-position').toLowerCase();
            
            if (searchTerm === '' || name.includes(searchTerm) || position.includes(searchTerm)) {
                option.style.display = 'block';
                visibleCount++;
                
                // Highlight matching text if searching
                if (searchTerm !== '') {
                    const nameEl = option.querySelector('div:first-child');
                    const originalName = option.getAttribute('data-name');
                    nameEl.innerHTML = highlightText(originalName, searchTerm);
                }
            } else {
                option.style.display = 'none';
            }
        });
        
        // Update count
        document.getElementById('selected_count').textContent = visibleCount;
        
        // Show no results message
        if (visibleCount === 0 && searchTerm !== '') {
            let noResults = dropdown.querySelector('.no-results');
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.style.cssText = 'padding: 15px; text-align: center; color: #627386;';
                noResults.textContent = 'No employees found matching "' + searchTerm + '"';
                dropdown.appendChild(noResults);
            }
        } else {
            const noResults = dropdown.querySelector('.no-results');
            if (noResults) noResults.remove();
        }
    }
    
    // Highlight matching text
    function highlightText(text, searchTerm) {
        if (!searchTerm) return text;
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }
    
    // Select employee from dropdown
    function selectEmployee(element) {
        const id = element.getAttribute('data-id');
        const name = element.getAttribute('data-name');
        
        // Update hidden input
        document.getElementById('applicant_id').value = id;
        selectedEmployeeId = id;
        
        // Update search input
        document.getElementById('employee_search').value = name;
        
        // Update employee name field
        document.getElementById('employee_name').value = name;
        document.getElementById('employee_name').readOnly = true;
        
        // Update visual selection
        const dropdown = document.getElementById('employee_dropdown');
        Array.from(dropdown.getElementsByClassName('employee-option')).forEach(opt => {
            opt.classList.remove('selected');
        });
        element.classList.add('selected');
        
        // Hide dropdown
        dropdown.style.display = 'none';
    }
    
    // Clear employee selection
    function clearEmployeeSelection() {
        document.getElementById('applicant_id').value = '';
        selectedEmployeeId = null;
        document.getElementById('employee_search').value = '';
        document.getElementById('employee_name').value = '';
        document.getElementById('employee_name').readOnly = false;
        
        // Remove all selections
        const dropdown = document.getElementById('employee_dropdown');
        Array.from(dropdown.getElementsByClassName('employee-option')).forEach(opt => {
            opt.classList.remove('selected');
        });
        
        dropdown.style.display = 'none';
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const searchInput = document.getElementById('employee_search');
        const dropdown = document.getElementById('employee_dropdown');
        
        if (!searchInput.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Keyboard navigation
    document.getElementById('employee_search').addEventListener('keydown', function(e) {
        const dropdown = document.getElementById('employee_dropdown');
        const visibleOptions = Array.from(dropdown.getElementsByClassName('employee-option'))
            .filter(opt => opt.style.display !== 'none');
        
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            this.blur();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const selected = dropdown.querySelector('.employee-option.selected');
            if (selected) {
                selectEmployee(selected);
            }
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            dropdown.style.display = 'block';
            
            const currentIndex = visibleOptions.findIndex(opt => opt.classList.contains('selected'));
            let nextIndex;
            
            if (e.key === 'ArrowDown') {
                nextIndex = currentIndex < visibleOptions.length - 1 ? currentIndex + 1 : 0;
            } else {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : visibleOptions.length - 1;
            }
            
            visibleOptions.forEach(opt => opt.classList.remove('selected'));
            if (visibleOptions[nextIndex]) {
                visibleOptions[nextIndex].classList.add('selected');
                visibleOptions[nextIndex].scrollIntoView({ block: 'nearest' });
            }
        }
    });
    
    // Pre-select employee if editing
    @if(old('applicant_id'))
        window.addEventListener('DOMContentLoaded', function() {
            const option = document.querySelector(`.employee-option[data-id="{{ old('applicant_id') }}"]`);
            if (option) {
                selectEmployee(option);
            }
        });
    @endif
</script>
@endsection
