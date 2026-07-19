<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'leave_type_id',
        'total_entitled',
        'used_current_year',
        'pending_days',
        'available_days',
        'carry_forward_days',
        'carry_forward_expiry',
        'sick_leave_used_12m',
        'last_calculated_at',
    ];

    protected $casts = [
        'total_entitled' => 'decimal:1',
        'used_current_year' => 'decimal:1',
        'pending_days' => 'decimal:1',
        'available_days' => 'decimal:1',
        'carry_forward_days' => 'decimal:1',
        'sick_leave_used_12m' => 'decimal:1',
        'last_calculated_at' => 'datetime',
        'carry_forward_expiry' => 'date',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
