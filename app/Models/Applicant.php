<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'first_name',
        'middle_name',
        'surname',
        'registration_date',
        'experience_years',
        'experience_months',
        'academic_level',
        'academic_detail',
        'academic_field',
        'graduation_date',
        'phone_primary',
        'phone_secondary',
        'created_by',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'graduation_date' => 'date',
        'experience_years' => 'integer',
        'experience_months' => 'integer',
    ];

    public function position()
    {
        return $this->belongsTo(ApplicantPosition::class);
    }

    public function selection()
    {
        return $this->hasOne(ApplicantSelection::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(ApplicantWorkExperience::class);
    }

    public function criterionScores()
    {
        return $this->hasMany(ApplicantCriterionScore::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->surname,
        ])));
    }
}