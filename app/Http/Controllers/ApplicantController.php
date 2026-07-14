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

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $canEdit = $user->canEdit();

        // Get all organizations and positions
        $organizations = Organization::orderBy('name')->get();
        $positions = ApplicantPosition::with('organization')
            ->orderBy('name')
            ->get();

        // Build filter query
        $query = Applicant::with(['position.organization', 'selection']);

        if ($request->filled('organization')) {
            $query->whereHas('position', function($q) use ($request) {
                $q->where('organization_id', $request->organization);
            });
        }

        if ($request->filled('position')) {
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

        $applicants = $query->orderBy('registration_date', 'desc')
            ->orderBy('surname')
            ->get();

        // Get work experiences
        $allWorkExperiences = ApplicantWorkExperience::with(['applicant.position'])
            ->whereIn('applicant_id', $applicants->pluck('id'))
            ->orderBy('start_date')
            ->get()
            ->groupBy('applicant_id');

        // For review form
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

        // Edit mode
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

        // Academic levels
        $academicLevels = [
            'First Degree', 'Second Degree', 'Third Degree', 
            'Diploma', 'High School Complete', 'Below High School'
        ];

        $decisions = ['Pending', 'Shortlisted', 'Selected', 'Reserve', 'Rejected'];

        return view('applicants.index', compact(
            'organizations',
            'positions',
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
                'details' => "{$experience->company} | {$experience->job_title}",
            ]);

            DB::commit();

            return redirect()->route('applicants.index')
                ->with('success', "Work experience added successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('applicants.index')
                ->with('error', 'Failed to add work experience: ' . $e->getMessage());
        }
    }

    public function deleteWorkExperience($id)
    {
        $user = Auth::user();

        if (!$user->canEdit()) {
            return redirect()->route('applicants.index')
                ->with('error', 'You do not have permission to delete work experience.');
        }

        $experience = ApplicantWorkExperience::findOrFail($id);
        $details = "{$experience->company} | {$experience->job_title}";

        DB::beginTransaction();

        try {
            $experience->delete();

            AuditLog::create([
                'username' => $user->username,
                'action' => 'delete_work_experience',
                'details' => $details,
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

    // Organization Management
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

    // Position Management
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

    // app/Http/Controllers/ApplicantController.php

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
            if ($position->applicants()->count() > 0) {
                throw new \Exception('This position has applicants and cannot be deleted.');
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

    // Criteria Management
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
}