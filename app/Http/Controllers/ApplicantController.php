<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantPosition;
use App\Models\ApplicantSelection;
use App\Models\ApplicantWorkExperience;
use App\Models\ApplicantCriterionScore;
use App\Models\Organization;
use App\Models\SelectionCriterion;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class ApplicantController extends Controller
{
    /**
     * Display a listing of applicants
     */
    public function index(Request $request)
{
    $user = Auth::user();
    $canEdit = $user->canEdit();

    // Get all organizations and positions
    $organizations = Organization::orderBy('name')->get();
    $positions = ApplicantPosition::with('organization')
        ->orderBy('name')
        ->get();

    // Get active positions and organizations
    $activePositions = $positions->filter(function($p) { 
        return $p->active == 1 || $p->active === true; 
    });
    $activeOrganizations = $organizations->filter(function($o) { 
        return $o->active == 1 || $o->active === true; 
    });

    // Build filter query
    $query = Applicant::with(['position.organization', 'selection']);

    // Apply organization filter
    if ($request->filled('organization') && $request->organization != 0) {
        $query->whereHas('position', function($q) use ($request) {
            $q->where('organization_id', $request->organization);
        });
    }

    // Apply position filter
    if ($request->filled('position') && $request->position != 0) {
        $query->where('position_id', $request->position);
    }

    // Apply date filters
    if ($request->filled('date_from')) {
        $query->where('registration_date', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->where('registration_date', '<=', $request->date_to);
    }

    // Apply academic level filter
    if ($request->filled('academic') && $request->academic != 'All') {
        $query->where('academic_level', $request->academic);
    }

    // Apply selection decision filter
    if ($request->filled('decision') && $request->decision != 'All') {
        $query->whereHas('selection', function($q) use ($request) {
            $q->where('decision', $request->decision);
        });
    }

    // Get filtered applicants
    $applicants = $query->orderBy('registration_date', 'desc')
        ->orderBy('surname')
        ->get();

    // Get work experiences for all applicants
    $allWorkExperiences = ApplicantWorkExperience::with(['applicant.position'])
        ->whereIn('applicant_id', $applicants->pluck('id'))
        ->orderBy('start_date')
        ->get()
        ->groupBy('applicant_id');

    // For review form - load applicant data
    $reviewApplicantId = $request->input('review_applicant');
    $reviewApplicant = null;
    $reviewCriteria = [];
    $reviewScores = [];
    $reviewExperiences = [];

    if ($reviewApplicantId) {
        $reviewApplicant = Applicant::with(['position.organization', 'selection'])->find($reviewApplicantId);
        if ($reviewApplicant) {
            $reviewCriteria = SelectionCriterion::where('organization_id', $reviewApplicant->position->organization_id)
                ->where(function($q) use ($reviewApplicant) {
                    $q->where('position_id', $reviewApplicant->position_id)
                        ->orWhereNull('position_id');
                })
                ->where('active', 1)
                ->orderBy('display_order')
                ->get();

            $reviewScores = ApplicantCriterionScore::where('applicant_id', $reviewApplicantId)
                ->get()
                ->keyBy('criterion_id');

            $reviewExperiences = ApplicantWorkExperience::where('applicant_id', $reviewApplicantId)
                ->orderBy('start_date')
                ->get();
        }
    }

    // Edit mode - load applicant for editing
    $editApplicant = null;
    if ($request->filled('edit_registration')) {
        $editApplicant = Applicant::with('position')->find($request->edit_registration);
    }

    // Statistics
    $stats = [
        'total' => $applicants->count(),
        'degree_plus' => $applicants->whereIn('academic_level', ['First Degree', 'Second Degree', 'Third Degree'])->count(),
        'selected' => $applicants->filter(function($a) {
            return $a->selection && $a->selection->decision == 'Selected';
        })->count(),
        'positions' => $applicants->groupBy('position_id')->count(),
    ];

    // Academic levels for dropdown
    $academicLevels = [
        'First Degree', 'Second Degree', 'Third Degree', 
        'Diploma', 'High School Complete', 'Below High School'
    ];

    // Selection decisions for dropdown
    $decisions = ['Pending', 'Shortlisted', 'Selected', 'Reserve', 'Rejected'];

    // Return view with all variables
    return view('applicants.index', compact(
        'organizations',
        'positions',
        'activePositions',
        'activeOrganizations',
        'applicants',
        'allWorkExperiences',
        'reviewApplicant',
        'reviewCriteria',
        'reviewScores',
        'reviewExperiences',
        'editApplicant',
        'stats',
        'academicLevels',
        'decisions',
        'canEdit'
    ));
}
    /**
     * Store a newly created applicant
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to add applicants.');
        }

        $validator = Validator::make($request->all(), [
            'position_id' => 'required|exists:applicant_positions,id',
            'first_name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'registration_date' => 'required|date',
            'academic_level' => 'required|string',
            'academic_field' => 'required|string|max:200',
            'experience_years' => 'required|integer|min:0',
            'experience_months' => 'required|integer|min:0|max:11',
            'phone_primary' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->route('applicants.index')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $applicant = Applicant::create([
                'position_id' => $request->position_id,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name ?? '',
                'surname' => $request->surname,
                'registration_date' => $request->registration_date,
                'experience_years' => $request->experience_years,
                'experience_months' => $request->experience_months,
                'academic_level' => $request->academic_level,
                'academic_detail' => $request->academic_detail ?? '',
                'academic_field' => $request->academic_field,
                'graduation_date' => $request->graduation_date,
                'phone_primary' => $request->phone_primary ?? '',
                'phone_secondary' => $request->phone_secondary ?? '',
                'created_by' => $user->username,
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'add_applicant',
                'details' => $applicant->full_name . ' (' . $applicant->id . ')',
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Applicant '{$applicant->full_name}' registered successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to register applicant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the specified applicant
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to update applicants.');
        }

        $applicant = Applicant::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'position_id' => 'required|exists:applicant_positions,id',
            'first_name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'registration_date' => 'required|date',
            'academic_level' => 'required|string',
            'academic_field' => 'required|string|max:200',
            'experience_years' => 'required|integer|min:0',
            'experience_months' => 'required|integer|min:0|max:11',
        ]);

        if ($validator->fails()) {
            return redirect()->route('applicants.index')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $oldName = $applicant->full_name;
            
            $applicant->update([
                'position_id' => $request->position_id,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name ?? '',
                'surname' => $request->surname,
                'registration_date' => $request->registration_date,
                'experience_years' => $request->experience_years,
                'experience_months' => $request->experience_months,
                'academic_level' => $request->academic_level,
                'academic_detail' => $request->academic_detail ?? '',
                'academic_field' => $request->academic_field,
                'graduation_date' => $request->graduation_date,
                'phone_primary' => $request->phone_primary ?? '',
                'phone_secondary' => $request->phone_secondary ?? '',
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'update_applicant',
                'details' => "{$oldName} -> {$applicant->full_name}",
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Applicant '{$applicant->full_name}' updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to update applicant: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified applicant
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to delete applicants.');
        }

        $applicant = Applicant::findOrFail($id);
        $name = $applicant->full_name;

        DB::beginTransaction();

        try {
            ApplicantCriterionScore::where('applicant_id', $id)->delete();
            ApplicantSelection::where('applicant_id', $id)->delete();
            ApplicantWorkExperience::where('applicant_id', $id)->delete();
            $applicant->delete();

            AuditLog::create([
                'username' => $user->username,
                'action' => 'delete_applicant',
                'details' => $name,
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Applicant '{$name}' deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to delete applicant: ' . $e->getMessage());
        }
    }

    // ============================================
    // WORK EXPERIENCE METHODS
    // ============================================

    /**
     * Store a new work experience record
     */
    public function storeWorkExperience(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to add work experience.');
        }

        $validator = Validator::make($request->all(), [
            'applicant_id' => 'required|exists:applicants,id',
            'company' => 'required|string|max:200',
            'job_title' => 'required|string|max:200',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'specific_to_position' => 'boolean',
            'specific_position_title' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('applicants.index')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $experience = ApplicantWorkExperience::create([
                'applicant_id' => $request->applicant_id,
                'company' => $request->company,
                'job_title' => $request->job_title,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'specific_to_position' => $request->boolean('specific_to_position'),
                'specific_position_title' => $request->specific_position_title ?? '',
                'notes' => $request->notes ?? '',
                'created_by' => $user->username,
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'add_work_experience',
                'details' => "{$experience->company} | {$experience->job_title} (Applicant: {$experience->applicant->full_name})",
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Work experience added successfully for {$experience->applicant->full_name}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to add work experience: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete a work experience record
     */
    public function deleteWorkExperience($id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to delete work experience.');
        }

        $experience = ApplicantWorkExperience::findOrFail($id);
        $applicantName = $experience->applicant->full_name;
        $details = "{$experience->company} | {$experience->job_title}";

        DB::beginTransaction();

        try {
            $experience->delete();

            AuditLog::create([
                'username' => $user->username,
                'action' => 'delete_work_experience',
                'details' => "{$details} (Applicant: {$applicantName})",
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Work experience deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to delete work experience: ' . $e->getMessage());
        }
    }

    /**
     * Get work experience data for editing (AJAX)
     */
    public function editWorkExperience($id)
    {
        $experience = ApplicantWorkExperience::with('applicant')->findOrFail($id);
        
        return response()->json([
            'id' => $experience->id,
            'applicant_name' => $experience->applicant->full_name,
            'company' => $experience->company,
            'job_title' => $experience->job_title,
            'start_date' => $experience->start_date,
            'end_date' => $experience->end_date,
            'specific_to_position' => $experience->specific_to_position,
            'specific_position_title' => $experience->specific_position_title,
            'notes' => $experience->notes,
        ]);
    }

    /**
     * Update a work experience record
     */
    public function updateWorkExperience(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to update work experience.');
        }

        $experience = ApplicantWorkExperience::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company' => 'required|string|max:200',
            'job_title' => 'required|string|max:200',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'specific_to_position' => 'boolean',
            'specific_position_title' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('applicants.index')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $oldDetails = "{$experience->company} | {$experience->job_title}";
            
            $experience->update([
                'company' => $request->company,
                'job_title' => $request->job_title,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'specific_to_position' => $request->boolean('specific_to_position'),
                'specific_position_title' => $request->specific_position_title ?? '',
                'notes' => $request->notes ?? '',
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'update_work_experience',
                'details' => "{$oldDetails} -> {$experience->company} | {$experience->job_title} (Applicant: {$experience->applicant->full_name})",
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Work experience updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to update work experience: ' . $e->getMessage());
        }
    }

    // ============================================
    // COMMITTEE REVIEW METHODS
    // ============================================

    /**
     * Save committee review scores and decision
     */
    public function saveCommitteeReview(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to save committee reviews.');
        }

        $applicantId = $request->applicant_id;
        $applicant = Applicant::with('position')->findOrFail($applicantId);

        DB::beginTransaction();

        try {
            $criteria = SelectionCriterion::where('organization_id', $applicant->position->organization_id)
                ->where(function($q) use ($applicant) {
                    $q->where('position_id', $applicant->position_id)
                        ->orWhereNull('position_id');
                })
                ->where('active', 1)
                ->get();

            $weightedTotal = 0;
            $weightTotal = 0;
            $legacyScores = [
                'screening' => 0,
                'written' => 0,
                'interview' => 0,
                'practical' => 0
            ];

            foreach ($criteria as $criterion) {
                $scoreKey = "criterion_score_{$criterion->id}";
                $commentKey = "criterion_comment_{$criterion->id}";
                
                $score = (float) $request->input($scoreKey, 0);
                $comment = $request->input($commentKey, '');

                if ($score < 0 || $score > $criterion->max_score) {
                    throw new \Exception("Score for {$criterion->criterion_name} must be between 0 and {$criterion->max_score}.");
                }

                ApplicantCriterionScore::updateOrCreate(
                    [
                        'applicant_id' => $applicantId,
                        'criterion_id' => $criterion->id,
                    ],
                    [
                        'score' => $score,
                        'comment' => $comment,
                        'updated_by' => $user->username,
                    ]
                );

                $weightedTotal += ($score / $criterion->max_score) * $criterion->weight;
                $weightTotal += $criterion->weight;

                $normalized = ($score / $criterion->max_score) * 100;
                $lowerName = strtolower($criterion->criterion_name);
                
                if (strpos($lowerName, 'screen') !== false || strpos($lowerName, 'document') !== false) {
                    $legacyScores['screening'] = $normalized;
                } elseif (strpos($lowerName, 'written') !== false || strpos($lowerName, 'exam') !== false) {
                    $legacyScores['written'] = $normalized;
                } elseif (strpos($lowerName, 'interview') !== false) {
                    $legacyScores['interview'] = $normalized;
                } elseif (strpos($lowerName, 'practical') !== false || strpos($lowerName, 'skill') !== false) {
                    $legacyScores['practical'] = $normalized;
                }
            }

            $finalScore = $weightTotal > 0 ? round(($weightedTotal / $weightTotal) * 100, 2) : 0;
            $rankNo = (int) $request->input('rank_no', 0);
            $decision = $request->input('decision', 'Pending');
            $reviewDate = $request->input('review_date', now()->format('Y-m-d'));

            if ($rankNo < 0) {
                throw new \Exception('Rank cannot be negative.');
            }

            ApplicantSelection::updateOrCreate(
                ['applicant_id' => $applicantId],
                [
                    'screening_score' => $legacyScores['screening'],
                    'written_score' => $legacyScores['written'],
                    'interview_score' => $legacyScores['interview'],
                    'practical_score' => $legacyScores['practical'],
                    'final_score' => $finalScore,
                    'rank_no' => $rankNo,
                    'decision' => $decision,
                    'selection_date' => $reviewDate,
                    'review_date' => $reviewDate,
                    'committee_members' => $request->input('committee_members', ''),
                    'remarks' => $request->input('remarks', ''),
                    'updated_by' => $user->username,
                ]
            );

            AuditLog::create([
                'username' => $user->username,
                'action' => 'committee_review',
                'details' => "{$applicant->full_name} | {$decision} | Final Score {$finalScore}",
            ]);

            DB::commit();

            return redirect()->route('applicants.index', ['review_applicant' => $applicantId])
                ->with('success', "Committee review saved successfully for {$applicant->full_name}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index', ['review_applicant' => $applicantId])
                ->with('error', 'Failed to save committee review: ' . $e->getMessage());
        }
    }

    // ============================================
    // ORGANIZATION MANAGEMENT
    // ============================================

    public function storeOrganization(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to manage organizations.');
        }

        $request->validate([
            'name' => 'required|string|max:200|unique:organizations',
        ]);

        DB::beginTransaction();

        try {
            $organization = Organization::create([
                'name' => $request->name,
                'active' => true,
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'add_organization',
                'details' => $organization->name,
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Organization '{$organization->name}' created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to create organization: ' . $e->getMessage());
        }
    }

    public function deleteOrganization($id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to delete organizations.');
        }

        $organization = Organization::findOrFail($id);
        $name = $organization->name;

        DB::beginTransaction();

        try {
            if ($organization->positions()->count() > 0) {
                throw new \Exception('This organization has positions and cannot be deleted.');
            }

            $organization->delete();

            AuditLog::create([
                'username' => $user->username,
                'action' => 'delete_organization',
                'details' => $name,
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Organization '{$name}' deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to delete organization: ' . $e->getMessage());
        }
    }

    // ============================================
    // POSITION MANAGEMENT
    // ============================================

    public function storePosition(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to manage positions.');
        }

        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:200|unique:applicant_positions',
            'grade' => 'nullable|string|max:100',
            'salary' => 'nullable|string|max:100',
            'requirement_type' => 'nullable|string|max:100',
            'criteria' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $position = ApplicantPosition::create([
                'organization_id' => $request->organization_id,
                'name' => $request->name,
                'active' => true,
                'grade' => $request->grade ?? '',
                'salary' => $request->salary ?? '',
                'requirement_type' => $request->requirement_type ?? '',
                'criteria' => $request->criteria ?? '',
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'add_position',
                'details' => $position->name,
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Position '{$position->name}' created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to create position: ' . $e->getMessage());
        }
    }

    public function updatePosition(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to update positions.');
        }

        $request->validate([
            'position_id' => 'required|exists:applicant_positions,id',
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:200',
            'grade' => 'nullable|string|max:100',
            'salary' => 'nullable|string|max:100',
            'requirement_type' => 'nullable|string|max:100',
            'criteria' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $position = ApplicantPosition::findOrFail($request->position_id);
            $oldName = $position->name;

            // Check if name is unique (except for this position)
            $existing = ApplicantPosition::where('name', $request->name)
                ->where('id', '!=', $position->id)
                ->first();
            
            if ($existing) {
                throw new \Exception('A position with this name already exists.');
            }

            $position->update([
                'organization_id' => $request->organization_id,
                'name' => $request->name,
                'grade' => $request->grade ?? '',
                'salary' => $request->salary ?? '',
                'requirement_type' => $request->requirement_type ?? '',
                'criteria' => $request->criteria ?? '',
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'update_position',
                'details' => "{$oldName} -> {$position->name}",
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Position '{$position->name}' updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to update position: ' . $e->getMessage())
                ->withInput();
        }
    }

  /**
 * Delete a position
 */
public function deletePosition($id)
{
    $user = Auth::user();

    if (!$user->canEdit()) {
        return redirect()->route('applicants.index')
            ->with('error', 'You do not have permission to delete positions.');
    }

    $position = ApplicantPosition::findOrFail($id);
    $name = $position->name;

    DB::beginTransaction();

    try {
        // Check if position has applicants
        $applicantCount = Applicant::where('position_id', $id)->count();
        if ($applicantCount > 0) {
            throw new \Exception('This position has ' . $applicantCount . ' applicant(s) and cannot be deleted.');
        }

        $position->delete();

        AuditLog::create([
            'username' => $user->username,
            'action' => 'delete_position',
            'details' => $name,
        ]);

        DB::commit();

        return redirect()->route('applicants.index')
            ->with('success', "Position '{$name}' deleted successfully.");

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->route('applicants.index')
            ->with('error', 'Failed to delete position: ' . $e->getMessage());
    }
}

    // ============================================
    // CRITERIA MANAGEMENT
    // ============================================

    public function storeCriterion(Request $request)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to manage criteria.');
        }

        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'criterion_name' => 'required|string|max:200',
            'weight' => 'required|numeric|min:0.01',
            'max_score' => 'required|numeric|min:0.01',
            'pass_mark' => 'required|numeric|min:0',
            'display_order' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $criterion = SelectionCriterion::create([
                'organization_id' => $request->organization_id,
                'position_id' => $request->criterion_position_id ?: null,
                'criterion_name' => $request->criterion_name,
                'weight' => $request->weight,
                'max_score' => $request->max_score,
                'pass_mark' => $request->pass_mark,
                'display_order' => $request->display_order,
                'active' => true,
            ]);

            AuditLog::create([
                'username' => $user->username,
                'action' => 'add_criterion',
                'details' => $criterion->criterion_name,
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Criterion '{$criterion->criterion_name}' created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to create criterion: ' . $e->getMessage());
        }
    }

    public function deleteCriterion($id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to delete criteria.');
        }

        $criterion = SelectionCriterion::findOrFail($id);
        $name = $criterion->criterion_name;

        DB::beginTransaction();

        try {
            $criterion->delete();

            AuditLog::create([
                'username' => $user->username,
                'action' => 'delete_criterion',
                'details' => $name,
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Criterion '{$name}' deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to delete criterion: ' . $e->getMessage());
        }
    }

    // ============================================
    // APPLICANT REPORT METHODS
    // ============================================

    /**
     * Get applicant report data with filters
     */
    private function getApplicantReportData(Request $request)
    {
        $query = Applicant::with(['position.organization', 'selection', 'workExperiences']);

        // Apply filters
        if ($request->filled('organization') && $request->organization != 0) {
            $query->whereHas('position', function($q) use ($request) {
                $q->where('organization_id', $request->organization);
            });
        }

        if ($request->filled('position') && $request->position != 0) {
            $query->where('position_id', $request->position);
        }

        if ($request->filled('date_from')) {
            $query->where('registration_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('registration_date', '<=', $request->date_to);
        }

        if ($request->filled('academic') && $request->academic != 'All') {
            $query->where('academic_level', $request->academic);
        }

        if ($request->filled('decision') && $request->decision != 'All') {
            $query->whereHas('selection', function($q) use ($request) {
                $q->where('decision', $request->decision);
            });
        }

        return $query->orderBy('registration_date', 'desc')
            ->orderBy('surname')
            ->get();
    }

    /**
     * Export Applicant Register Report
     */
    public function exportApplicants(Request $request)
    {
        $user = Auth::user();

        // Get filtered data
        $applicants = $this->getApplicantReportData($request);
        $filters = [
            'organization' => $request->input('organization', 0),
            'position' => $request->input('position', 0),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'academic' => $request->input('academic', 'All'),
            'decision' => $request->input('decision', 'All'),
        ];

        if ($applicants->isEmpty()) {
            return redirect()->route('applicants.index')
                ->with('error', 'No applicants found for the selected criteria.');
        }

        // Log the export
        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_applicants_xlsx',
            'details' => "Applicant Register exported with " . $applicants->count() . " records",
        ]);

        // Generate Excel
        return $this->generateApplicantExcel($applicants, $filters);
    }

    /**
     * Export Selection Report
     */
    public function exportSelection(Request $request)
    {
        $user = Auth::user();

        // Get filtered data
        $applicants = $this->getApplicantReportData($request);
        $filters = [
            'organization' => $request->input('organization', 0),
            'position' => $request->input('position', 0),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'academic' => $request->input('academic', 'All'),
            'decision' => $request->input('decision', 'All'),
        ];

        if ($applicants->isEmpty()) {
            return redirect()->route('applicants.index')
                ->with('error', 'No applicants found for the selected criteria.');
        }

        // Log the export
        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_selection_xlsx',
            'details' => "Selection Report exported with " . $applicants->count() . " records",
        ]);

        // Generate Excel
        return $this->generateSelectionExcel($applicants, $filters);
    }

    /**
     * Export Detailed Profile Report (Attached Format)
     */
    public function exportSelectionProfile(Request $request)
    {
        $user = Auth::user();

        // Get filtered data
        $applicants = $this->getApplicantReportData($request);
        $filters = [
            'organization' => $request->input('organization', 0),
            'position' => $request->input('position', 0),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'academic' => $request->input('academic', 'All'),
            'decision' => $request->input('decision', 'All'),
        ];

        if ($applicants->isEmpty()) {
            return redirect()->route('applicants.index')
                ->with('error', 'No applicants found for the selected criteria.');
        }

        // Log the export
        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_selection_profile_xlsx',
            'details' => "Selection Profile exported with " . $applicants->count() . " records",
        ]);

        // Generate Excel
        return $this->generateProfileExcel($applicants, $filters);
    }

    /**
     * Export Single Applicant Profile (Professional Format)
     */
    public function exportSingleProfile($id)
    {
        $user = Auth::user();
        $applicant = Applicant::with(['position.organization', 'selection', 'workExperiences'])->findOrFail($id);
        
        // Log the export
        AuditLog::create([
            'username' => $user->username,
            'action' => 'export_single_profile',
            'details' => "Profile exported for: {$applicant->full_name}",
        ]);
        
        return $this->generateSingleProfileExcel($applicant);
    }

    // ============================================
    // EXCEL GENERATION METHODS
    // ============================================

    /**
     * Generate Applicant Register Excel
     */
    private function generateApplicantExcel($applicants, $filters)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TNT HR Reporting')
            ->setLastModifiedBy('TNT HR Reporting')
            ->setTitle('Applicant Register Report')
            ->setSubject('Applicant Register')
            ->setDescription('Comprehensive Applicant Register Report');

        // ============================================
        // SHEET 1: APPLICANT DETAILS
        // ============================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Applicant Details');

        $row = 1;

        // Title
        $sheet->mergeCells('A1:W1');
        $sheet->setCellValue('A1', 'TNT HR - APPLICANT REGISTER REPORT');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(35);
        $row += 2;

        // Report Info
        $info = [
            ['Generated:', now()->format('Y-m-d H:i:s')],
            ['Generated By:', Auth::user()->username ?? 'System'],
            ['Organization:', $this->getOrganizationName($filters['organization'])],
            ['Position:', $this->getPositionName($filters['position'])],
            ['Date Range:', ($filters['date_from'] ?: 'All') . ' to ' . ($filters['date_to'] ?: 'All')],
            ['Academic Level:', $filters['academic']],
            ['Selection Decision:', $filters['decision']],
            ['Total Records:', $applicants->count()],
        ];

        foreach ($info as $infoRow) {
            $sheet->setCellValue('A' . $row, $infoRow[0]);
            $sheet->setCellValue('B' . $row, $infoRow[1]);
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $row++;
        }
        $row += 2;

        // Headers
        $headers = [
            'No.', 'Registration Date', 'Organization', 'Application Position',
            'First Name', 'Middle Name', 'Surname', 'Full Name',
            'Academic Level', 'Academic Field', 'Graduation Date', 'Education Detail',
            'Experience Years', 'Experience Months', 'Primary Phone', 'Secondary Phone',
            'Work Experience Records', 'Screening Score', 'Written Score', 'Interview Score',
            'Practical Score', 'Final Score', 'Rank', 'Decision', 'Selection Date', 'Remarks'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getColumnDimension($col)->setWidth(15);
            $col++;
        }
        $row++;

        // Data rows
        $index = 1;
        foreach ($applicants as $applicant) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $index);
            $sheet->setCellValue($col++ . $row, $applicant->registration_date);
            $sheet->setCellValue($col++ . $row, $applicant->position->organization->name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->position->name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->first_name);
            $sheet->setCellValue($col++ . $row, $applicant->middle_name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->surname);
            $sheet->setCellValue($col++ . $row, $applicant->full_name);
            $sheet->setCellValue($col++ . $row, $applicant->academic_level);
            $sheet->setCellValue($col++ . $row, $applicant->academic_field);
            $sheet->setCellValue($col++ . $row, $applicant->graduation_date ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->academic_detail ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->experience_years);
            $sheet->setCellValue($col++ . $row, $applicant->experience_months);
            $sheet->setCellValue($col++ . $row, $applicant->phone_primary ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->phone_secondary ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->workExperiences->count());
            $sheet->setCellValue($col++ . $row, $applicant->selection->screening_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->written_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->interview_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->practical_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->final_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->rank_no ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->selection->decision ?? 'Pending');
            $sheet->setCellValue($col++ . $row, $applicant->selection->selection_date ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->selection->remarks ?? '');
            $index++;
            $row++;
        }

        // Apply number formatting to numeric columns
        $sheet->getStyle('M2:V' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Auto-size columns
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ============================================
        // SHEET 2: SUMMARY BY POSITION
        // ============================================
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('By Position');
        $row = 1;

        // Title
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'TNT HR - APPLICANT SUMMARY BY POSITION');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Generated:');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $row += 2;

        // Headers
        $sheet->setCellValue('A' . $row, 'Organization');
        $sheet->setCellValue('B' . $row, 'Application Position');
        $sheet->setCellValue('C' . $row, 'Applicant Count');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $row++;

        // Group by position
        $positionGroups = $applicants->groupBy(function($a) {
            return $a->position->organization->name . '|' . $a->position->name;
        });

        foreach ($positionGroups as $key => $group) {
            list($org, $pos) = explode('|', $key);
            $sheet->setCellValue('A' . $row, $org);
            $sheet->setCellValue('B' . $row, $pos);
            $sheet->setCellValue('C' . $row, $group->count());
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ============================================
        // SHEET 3: SUMMARY BY DATE
        // ============================================
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('By Registration Date');
        $row = 1;

        // Title
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'TNT HR - APPLICANT SUMMARY BY REGISTRATION DATE');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Generated:');
        $sheet->setCellValue('B' . $row, now()->format('Y-m-d H:i:s'));
        $row += 2;

        // Headers
        $sheet->setCellValue('A' . $row, 'Registration Date');
        $sheet->setCellValue('B' . $row, 'Application Position');
        $sheet->setCellValue('C' . $row, 'Applicant Count');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $row++;

        // Group by date and position
        $dateGroups = $applicants->groupBy(function($a) {
            return $a->registration_date . '|' . $a->position->name;
        });

        foreach ($dateGroups as $key => $group) {
            list($date, $pos) = explode('|', $key);
            $sheet->setCellValue('A' . $row, $date);
            $sheet->setCellValue('B' . $row, $pos);
            $sheet->setCellValue('C' . $row, $group->count());
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Remove default sheet if we created others
        $spreadsheet->setActiveSheetIndex(0);

        // Save and download
        $writer = new Xlsx($spreadsheet);
        $filename = "TNT_Applicant_Register_" . now()->format('Ymd_His') . ".xlsx";

        $tempFile = tempnam(sys_get_temp_dir(), 'app_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate Selection Report Excel
     */
    private function generateSelectionExcel($applicants, $filters)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TNT HR Reporting')
            ->setLastModifiedBy('TNT HR Reporting')
            ->setTitle('Selection Report')
            ->setSubject('Selection Report')
            ->setDescription('Comprehensive Selection Report');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Selection Report');

        $row = 1;

        // Title
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'TNT HR - FINAL HIRING / SELECTION REPORT');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // Report Info
        $info = [
            ['Generated:', now()->format('Y-m-d H:i:s')],
            ['Generated By:', Auth::user()->username ?? 'System'],
            ['Organization:', $this->getOrganizationName($filters['organization'])],
            ['Position:', $this->getPositionName($filters['position'])],
            ['Date Range:', ($filters['date_from'] ?: 'All') . ' to ' . ($filters['date_to'] ?: 'All')],
            ['Academic Level:', $filters['academic']],
            ['Decision:', $filters['decision']],
            ['Total Candidates:', $applicants->count()],
        ];

        foreach ($info as $infoRow) {
            $sheet->setCellValue('A' . $row, $infoRow[0]);
            $sheet->setCellValue('B' . $row, $infoRow[1]);
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $row++;
        }
        $row += 2;

        // Headers
        $headers = [
            'No.', 'Registration Date', 'Organization', 'Position',
            'Candidate Name', 'Academic Level', 'Academic Field',
            'Total Experience', 'Screening', 'Written', 'Interview',
            'Practical', 'Final Score', 'Rank', 'Decision', 'Remarks'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getColumnDimension($col)->setWidth(15);
            $col++;
        }
        $row++;

        // Data rows - sorted by rank
        $sortedApplicants = $applicants->sortBy(function($a) {
            return $a->selection->rank_no ?? 999999;
        });

        $index = 1;
        foreach ($sortedApplicants as $applicant) {
            if (!$applicant->selection) continue;

            $col = 'A';
            $sheet->setCellValue($col++ . $row, $index);
            $sheet->setCellValue($col++ . $row, $applicant->registration_date);
            $sheet->setCellValue($col++ . $row, $applicant->position->organization->name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->position->name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->full_name);
            $sheet->setCellValue($col++ . $row, $applicant->academic_level);
            $sheet->setCellValue($col++ . $row, $applicant->academic_field);
            $sheet->setCellValue($col++ . $row, $applicant->experience_years . ' yrs, ' . $applicant->experience_months . ' mos');
            $sheet->setCellValue($col++ . $row, $applicant->selection->screening_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->written_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->interview_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->practical_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->final_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->rank_no ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->selection->decision ?? 'Pending');

            // Remarks with styling for selected candidates
            $remarks = $applicant->selection->remarks ?? '';
            $sheet->setCellValue($col++ . $row, $remarks);
            if ($applicant->selection->decision === 'Selected') {
                $sheet->getStyle('O' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
                    'font' => ['bold' => true],
                ]);
            }
            $index++;
            $row++;
        }

        // Apply number formatting to score columns
        $sheet->getStyle('I2:N' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Auto-size columns
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save and download
        $writer = new Xlsx($spreadsheet);
        $filename = "TNT_Selection_Report_" . now()->format('Ymd_His') . ".xlsx";

        $tempFile = tempnam(sys_get_temp_dir(), 'sel_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate Profile Excel (Attached Format)
     */
    private function generateProfileExcel($applicants, $filters)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TNT HR Reporting')
            ->setLastModifiedBy('TNT HR Reporting')
            ->setTitle('Applicant Selection Profile')
            ->setSubject('Selection Profile')
            ->setDescription('Detailed Applicant Selection Profile - Attached Format');

        // Summary Sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Selection Summary');

        // Title
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'TNT HR - SELECTION PROFILE SUMMARY');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row = 3;

        // Headers
        $headers = [
            'No.', 'Registration Date', 'Organization', 'Position',
            'Applicant Name', 'Academic Level', 'Academic Field',
            'Total Experience', 'Phone', 'Final Score', 'Rank', 'Decision'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
            $col++;
        }
        $row++;

        $index = 1;
        foreach ($applicants as $applicant) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $index);
            $sheet->setCellValue($col++ . $row, $applicant->registration_date);
            $sheet->setCellValue($col++ . $row, $applicant->position->organization->name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->position->name ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->full_name);
            $sheet->setCellValue($col++ . $row, $applicant->academic_level);
            $sheet->setCellValue($col++ . $row, $applicant->academic_field);
            $sheet->setCellValue($col++ . $row, $applicant->experience_years . ' yrs, ' . $applicant->experience_months . ' mos');
            $sheet->setCellValue($col++ . $row, $applicant->phone_primary ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->selection->final_score ?? 0);
            $sheet->setCellValue($col++ . $row, $applicant->selection->rank_no ?? '');
            $sheet->setCellValue($col++ . $row, $applicant->selection->decision ?? 'Pending');
            
            if ($applicant->selection && $applicant->selection->decision === 'Selected') {
                $sheet->getStyle('K' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFEEEA']],
                    'font' => ['bold' => true],
                ]);
            }
            $index++;
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "TNT_Selection_Profile_" . now()->format('Ymd_His') . ".xlsx";

        $tempFile = tempnam(sys_get_temp_dir(), 'prof_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate Single Applicant Profile Excel (Professional Format)
     */
    private function generateSingleProfileExcel($applicant)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TNT HR Reporting')
            ->setLastModifiedBy('TNT HR Reporting')
            ->setTitle('Applicant Profile Report')
            ->setSubject('Applicant Profile')
            ->setDescription('Professional Applicant Profile Report');
        
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Applicant Profile');
        
        $row = 1;
        
        // Header - Position
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'POSITION: ' . strtoupper($applicant->position->name ?? 'N/A'));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;
        
        // Grade, Salary, Criteria, Requirement Type
        $sheet->setCellValue('A' . $row, 'Grade:');
        $sheet->setCellValue('B' . $row, $applicant->position->grade ?? '');
        $sheet->setCellValue('D' . $row, 'Salary:');
        $sheet->setCellValue('E' . $row, $applicant->position->salary ?? '');
        $sheet->setCellValue('G' . $row, 'Requirement Type:');
        $sheet->setCellValue('H' . $row, $applicant->position->requirement_type ?? '');
        $sheet->setCellValue('J' . $row, 'Criteria:');
        $sheet->setCellValue('K' . $row, $applicant->position->criteria ?? '');
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
            'font' => ['size' => 11],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row += 2;
        
        // Personal Information
        $sheet->setCellValue('A' . $row, 'No:');
        $sheet->setCellValue('B' . $row, '1');
        $sheet->setCellValue('C' . $row, 'Applicant Name:');
        $sheet->setCellValue('D' . $row, $applicant->full_name);
        $sheet->setCellValue('E' . $row, 'Background of Education:');
        $sheet->setCellValue('F' . $row, $applicant->academic_level . ' ' . ($applicant->academic_field ?? ''));
        $sheet->setCellValue('G' . $row, 'Level:');
        $sheet->setCellValue('H' . $row, $applicant->academic_level);
        $sheet->setCellValue('I' . $row, 'Institution:');
        $sheet->setCellValue('J' . $row, $applicant->academic_detail ?? '');
        $sheet->setCellValue('K' . $row, 'Phone:');
        $sheet->setCellValue('L' . $row, $applicant->phone_primary ?? '');
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
            'font' => ['size' => 11],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A' . $row . ':C' . $row . ',' . 'E' . $row . ',' . 'G' . $row . ',' . 'I' . $row . ',' . 'K' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
        ]);
        $row++;
        
        // Total Experience
        $sheet->setCellValue('A' . $row, 'Total Experience:');
        $sheet->setCellValue('B' . $row, $applicant->experience_years . ' years ' . $applicant->experience_months . ' months');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
        ]);
        $row++;
        
        // Work Experience Table Header
        $headers = ['Company', 'Position', 'Start Date', 'End Date', 'Difference of Date', 'Total Experience', 'Specific Built project', 'Remark'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE5EE']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getColumnDimension($col)->setWidth(18);
            $col++;
        }
        $row++;
        
        // Work Experience Data
        $workExperiences = $applicant->workExperiences->sortBy('start_date');
        $totalSpecificDays = 0;
        
        foreach ($workExperiences as $exp) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $exp->company);
            $sheet->setCellValue($col++ . $row, $exp->job_title);
            $sheet->setCellValue($col++ . $row, $exp->start_date ? Carbon::parse($exp->start_date)->format('m/d/Y') : '');
            $sheet->setCellValue($col++ . $row, $exp->end_date ? Carbon::parse($exp->end_date)->format('m/d/Y') : '');
            
            // Difference of Date
            $diffDays = $exp->start_date && $exp->end_date ? $exp->start_date->diffInDays($exp->end_date) : 0;
            $years = floor($diffDays / 365);
            $months = floor(($diffDays % 365) / 30);
            $days = ($diffDays % 365) % 30;
            $diffText = ($years ? $years . ' years ' : '') . ($months ? $months . ' months ' : '') . ($days ? $days . ' days' : '');
            $sheet->setCellValue($col++ . $row, $diffText ?: '');
            
            // Total Experience (cumulative)
            if ($exp->specific_to_position) {
                $totalSpecificDays += $diffDays;
            }
            $totalYears = floor($totalSpecificDays / 365);
            $totalMonths = floor(($totalSpecificDays % 365) / 30);
            $totalDays = ($totalSpecificDays % 365) % 30;
            $totalText = ($totalYears ? $totalYears . ' years ' : '') . ($totalMonths ? $totalMonths . ' months ' : '') . ($totalDays ? $totalDays . ' days' : '');
            $sheet->setCellValue($col++ . $row, $totalText ?: '');
            
            // Specific Built project            $sheet->setCellValue($col++ . $row, $exp->specific_position_title ?? '');
            
            // Remark
            $sheet->setCellValue($col++ . $row, $exp->notes ?? '');
            
            // Style the row
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            
            // Highlight specific experience rows
            if ($exp->specific_to_position) {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                ]);
            }
            
            $row++;
        }
        
        // Selection Committee Section
        $row += 2;
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A' . $row, 'SELECTION COMMITTEE APPROVAL');
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']],
            'font' => ['color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;
        
        // Scores
        $scores = [
            ['Document Screening', $applicant->selection->screening_score ?? 0],
            ['Written Exam', $applicant->selection->written_score ?? 0],
            ['Interview', $applicant->selection->interview_score ?? 0],
            ['Practical Test', $applicant->selection->practical_score ?? 0],
            ['Final Score', $applicant->selection->final_score ?? 0],
            ['Rank', $applicant->selection->rank_no ?? ''],
            ['Decision', $applicant->selection->decision ?? 'Pending'],
        ];
        
        $col = 'A';
        foreach ($scores as $score) {
            $sheet->setCellValue($col . $row, $score[0]);
            $sheet->setCellValue($col . ($row + 1), $score[1]);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getStyle($col . ($row + 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            if (is_numeric($score[1])) {
                $sheet->getStyle($col . ($row + 1))->getNumberFormat()->setFormatCode('0.00');
            }
            $sheet->getColumnDimension($col)->setWidth(18);
            $col++;
        }
        $row += 3;
        
        // Approval Signatures
        $approvals = ['Prepared By', 'Reviewed By', 'Approved By'];
        $col = 'A';
        foreach ($approvals as $approval) {
            $sheet->setCellValue($col . $row, $approval);
            $sheet->setCellValue($col . ($row + 1), '');
            $sheet->setCellValue($col . ($row + 2), 'Signature');
            $sheet->setCellValue($col . ($row + 3), '');
            $sheet->setCellValue($col . ($row + 4), 'Date');
            $sheet->setCellValue($col . ($row + 5), '');
            $sheet->getStyle($col . $row . ':' . $col . ($row + 5))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF4F8']],
            ]);
            $col++;
        }
        $row += 8;
        
        // Footer
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A' . $row, 'Generated: ' . now()->format('Y-m-d H:i:s') . ' | Generated By: ' . (Auth::user()->username ?? 'System'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Save and download
        $writer = new Xlsx($spreadsheet);
        $filename = "Applicant_Profile_" . $applicant->full_name . "_" . now()->format('Ymd_His') . ".xlsx";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'profile_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function getOrganizationName($id)
    {
        if (!$id) return 'All Organizations';
        $org = Organization::find($id);
        return $org ? $org->name : 'Unknown';
    }

    private function getPositionName($id)
    {
        if (!$id) return 'All Positions';
        $pos = ApplicantPosition::find($id);
        return $pos ? $pos->name : 'Unknown';
    }
}