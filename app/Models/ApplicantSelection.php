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
}