<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}