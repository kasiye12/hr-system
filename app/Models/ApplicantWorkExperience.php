<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApplicantWorkExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'company',
        'job_title',
        'start_date',
        'end_date',
        'specific_to_position',
        'specific_position_title',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'specific_to_position' => 'boolean',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Calculate duration of this experience
     */
    public function getDurationAttribute()
    {
        if (!$this->start_date) {
            return 'Not started';
        }

        $start = Carbon::parse($this->start_date);
        $end = $this->end_date ? Carbon::parse($this->end_date) : Carbon::now();
        $diff = $start->diff($end);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        }

        return !empty($parts) ? implode(' ', $parts) : '0 days';
    }

    /**
     * Get formatted start date
     */
    public function getFormattedStartDateAttribute()
    {
        return $this->start_date ? Carbon::parse($this->start_date)->format('M d, Y') : '-';
    }

    /**
     * Get formatted end date
     */
    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? Carbon::parse($this->end_date)->format('M d, Y') : 'Present';
    }
}