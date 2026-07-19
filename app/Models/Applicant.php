<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        return $this->belongsTo(ApplicantPosition::class, 'position_id');
    }

    public function selection()
    {
        return $this->hasOne(ApplicantSelection::class, 'applicant_id');
    }

    public function workExperiences()
    {
        return $this->hasMany(ApplicantWorkExperience::class, 'applicant_id');
    }

    public function criterionScores()
    {
        return $this->hasMany(ApplicantCriterionScore::class, 'applicant_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->surname,
        ])));
    }

    /**
     * Calculate total experience from work experiences
     */
    public function calculateTotalExperience()
    {
        $totalDays = 0;
        $experiences = $this->workExperiences()->whereNotNull('start_date')->get();

        foreach ($experiences as $exp) {
            $start = Carbon::parse($exp->start_date);
            $end = $exp->end_date ? Carbon::parse($exp->end_date) : Carbon::now();
            $totalDays += $start->diffInDays($end);
        }

        $years = floor($totalDays / 365);
        $months = floor(($totalDays % 365) / 30);
        $days = ($totalDays % 365) % 30;

        return [
            'years' => $years,
            'months' => $months,
            'days' => $days,
            'total_days' => $totalDays,
            'formatted' => $this->formatExperience($years, $months, $days),
        ];
    }

    /**
     * Calculate specific experience (relevant to position)
     */
    public function calculateSpecificExperience()
    {
        $totalDays = 0;
        $experiences = $this->workExperiences()
            ->where('specific_to_position', true)
            ->whereNotNull('start_date')
            ->get();

        foreach ($experiences as $exp) {
            $start = Carbon::parse($exp->start_date);
            $end = $exp->end_date ? Carbon::parse($exp->end_date) : Carbon::now();
            $totalDays += $start->diffInDays($end);
        }

        $years = floor($totalDays / 365);
        $months = floor(($totalDays % 365) / 30);
        $days = ($totalDays % 365) % 30;

        return [
            'years' => $years,
            'months' => $months,
            'days' => $days,
            'total_days' => $totalDays,
            'formatted' => $this->formatExperience($years, $months, $days),
        ];
    }

    /**
     * Format experience for display
     */
    private function formatExperience($years, $months, $days)
    {
        $parts = [];
        if ($years > 0) {
            $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
        }
        if ($months > 0) {
            $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
        }
        if ($days > 0) {
            $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
        }
        return !empty($parts) ? implode(' ', $parts) : '0 days';
    }
}