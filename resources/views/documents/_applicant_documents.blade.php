<div class="card section-gap">
    <h3>📄 Employee Documents</h3>
    
    @php
        $applicantDocuments = \App\Models\EmployeeDocument::where('applicant_id', $applicant->id)
            ->orderBy('uploaded_at', 'desc')
            ->get();
    @endphp
    
    @if($applicantDocuments->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Document</th>
                <th>Type</th>
                <th>Size</th>
                <th>Uploaded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($applicantDocuments as $doc)
            <tr>
                <td>
                    <span style="font-size: 16px;">{{ $doc->file_icon }}</span>
                    <strong>{{ $doc->document_name }}</strong>
                    @if($doc->description)
                        <br><span class="small">{{ $doc->description }}</span>
                    @endif
                </td>
                <td><span class="badge active">{{ $doc->document_type }}</span></td>
                <td class="num">{{ $doc->file_size_formatted }}</td>
                <td>{{ $doc->uploaded_at->format('Y-m-d') }}</td>
                <td class="actions">
                    <a href="{{ route('documents.download', $doc->id) }}" class="btn light">Download</a>
                    @if(auth()->user()->canEdit())
                    <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="empty">No documents uploaded for this employee yet.</div>
    @endif
    
    <div style="margin-top: 12px;">
        <a href="{{ route('documents.index') }}?applicant_id={{ $applicant->id }}" class="btn">
            + Upload Document for {{ $applicant->first_name }}
        </a>
    </div>
</div>
