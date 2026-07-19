<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days',
        'is_paid',
        'is_calendar_days',
        'requires_medical_certificate',
        'requires_document',
        'accrues_over_time',
        'resets_annually',
        'legal_reference',
        'pay_tiers',
        'active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_calendar_days' => 'boolean',
        'requires_medical_certificate' => 'boolean',
        'requires_document' => 'boolean',
        'accrues_over_time' => 'boolean',
        'resets_annually' => 'boolean',
        'active' => 'boolean',
        'default_days' => 'integer',
        'pay_tiers' => 'array',
    ];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
