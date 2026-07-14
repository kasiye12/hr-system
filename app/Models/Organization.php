<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function positions()
    {
        return $this->hasMany(ApplicantPosition::class);
    }

    public function selectionCriteria()
    {
        return $this->hasMany(SelectionCriterion::class);
    }
}