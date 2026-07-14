<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantCriterionScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'criterion_id',
        'score',
        'comment',
        'updated_by',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function criterion()
    {
        return $this->belongsTo(SelectionCriterion::class);
    }
}