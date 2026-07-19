<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Display documents page
     */
    public function index(Request $request)
    {
        $query = EmployeeDocument::with('applicant')->orderBy('uploaded_at', 'desc');
        
        // Filter by applicant if provided
        if ($request->has('applicant_id') && $request->applicant_id) {
            $query->where('applicant_id', $request->applicant_id);
        }
        
        // Filter by document type
        if ($request->has('type') && $request->type) {
            $query->where('document_type', $request->type);
        }
        
        // Search by employee name
        if ($request->has('search') && $request->search) {
            $query->where('employee_name', 'like', '%' . $request->search . '%');
        }
        
        $documents = $query->paginate(20)->withQueryString();
        $applicants = Applicant::orderBy('first_name')->get();
        
        $stats = [
            'total' => EmployeeDocument::count(),
            'total_size' => EmployeeDocument::sum('file_size'),
            'by_type' => EmployeeDocument::select('document_type', \DB::raw('count(*) as count'))
                ->groupBy('document_type')
                ->get(),
        ];

        return view('documents.index', compact('documents', 'applicants', 'stats'));
    }

    /**
     * Upload a document
     */
    public function upload(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('documents.index')
                ->with('error', 'You do not have permission to upload documents.');
        }

        $validator = Validator::make($request->all(), [
            'applicant_id' => 'nullable|exists:applicants,id',
            'employee_name' => 'required|string|max:200',
            'document_type' => 'required|string|max:50',
            'document' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,txt,zip,rar',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->route('documents.index')
                ->withErrors($validator)
                ->withInput();
        }

        // If applicant_id is provided, get employee name from applicant
        $employeeName = $request->employee_name;
        if ($request->applicant_id) {
            $applicant = Applicant::find($request->applicant_id);
            if ($applicant) {
                $employeeName = $applicant->first_name . ' ' . $applicant->surname;
            }
        }

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileType = $file->getMimeType();

        // Generate unique filename with employee name prefix
        $extension = $file->getClientOriginalExtension();
        $prefix = Str::slug($employeeName) . '_';
        $fileName = $prefix . date('Ymd_His') . '_' . Str::random(6) . '.' . $extension;
        
        // Store file
        $path = $file->storeAs('employee_documents', $fileName, 'public');

        try {
            EmployeeDocument::create([
                'applicant_id' => $request->applicant_id,
                'employee_name' => $employeeName,
                'document_type' => $request->document_type,
                'document_name' => $originalName,
                'file_path' => $path,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'uploaded_by' => $user->username,
                'description' => $request->description,
                'status' => 'active',
                'uploaded_at' => now(),
            ]);

            return redirect()->route('documents.index')
                ->with('success', "Document '{$originalName}' uploaded for {$employeeName}!");

        } catch (\Exception $e) {
            // Delete file if database insert fails
            Storage::disk('public')->delete($path);
            
            return redirect()->route('documents.index')
                ->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Download a document
     */
    public function download($id)
    {
        $document = EmployeeDocument::findOrFail($id);

        if (!Storage::disk('public')->exists($document->file_path)) {
            return redirect()->route('documents.index')
                ->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->document_name);
    }

    /**
     * Delete a document
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('documents.index')
                ->with('error', 'You do not have permission to delete documents.');
        }

        $document = EmployeeDocument::findOrFail($id);
        $name = $document->document_name;

        // Delete file
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', "Document '{$name}' deleted successfully.");
    }

    /**
     * Get documents for a specific applicant (API endpoint)
     */
    public function getApplicantDocuments($applicantId)
    {
        $documents = EmployeeDocument::where('applicant_id', $applicantId)
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($documents);
    }
}
