<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'screening_score',
        'written_score',
        'interview_score',
        'practical_score',
        'final_score',
        'rank_no',
        'decision',
        'selection_date',
        'review_date',
        'committee_members',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'screening_score' => 'float',
        'written_score' => 'float',
        'interview_score' => 'float',
        'practical_score' => 'float',
        'final_score' => 'float',
        'rank_no' => 'integer',
        'selection_date' => 'date',
        'review_date' => 'date',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Calculate weighted score based on criteria
     */
    public static function calculateWeightedScore($applicantId)
    {
        $applicant = Applicant::with('criterionScores.criterion')->find($applicantId);
        if (!$applicant) {
            return 0;
        }

        $weightedTotal = 0;
        $weightTotal = 0;

        foreach ($applicant->criterionScores as $score) {
            if ($score->criterion) {
                $weightedTotal += ($score->score / $score->criterion->max_score) * $score->criterion->weight;
                $weightTotal += $score->criterion->weight;
            }
        }

        return $weightTotal > 0 ? round(($weightedTotal / $weightTotal) * 100, 2) : 0;
    }

    /**
     * Update rank for all applicants in a position
     */
    public static function updateRanks($positionId)
    {
        $applicants = Applicant::where('position_id', $positionId)
            ->with('selection')
            ->get();

        $scoredApplicants = [];
        foreach ($applicants as $applicant) {
            if ($applicant->selection) {
                $scoredApplicants[] = [
                    'id' => $applicant->id,
                    'score' => $applicant->selection->final_score,
                ];
            }
        }

        // Sort by score descending
        usort($scoredApplicants, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Update ranks
        foreach ($scoredApplicants as $index => $data) {
            ApplicantSelection::where('applicant_id', $data['id'])
                ->update(['rank_no' => $index + 1]);
        }

        return $scoredApplicants;
    }
}