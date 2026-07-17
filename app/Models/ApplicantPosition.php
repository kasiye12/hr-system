<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'grade',
        'salary',
        'requirement_type',
        'criteria',
        'organization_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'organization_id' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'position_id');
    }

    public function selectionCriteria()
    {
        return $this->hasMany(SelectionCriterion::class);
    }
}