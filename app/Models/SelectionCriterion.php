<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectionCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'position_id',
        'criterion_name',
        'weight',
        'max_score',
        'pass_mark',
        'display_order',
        'active',
    ];

    protected $casts = [
        'weight' => 'float',
        'max_score' => 'float',
        'pass_mark' => 'float',
        'display_order' => 'integer',
        'active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function position()
    {
        return $this->belongsTo(ApplicantPosition::class);
    }

    public function scores()
    {
        return $this->hasMany(ApplicantCriterionScore::class);
    }
}