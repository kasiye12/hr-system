<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'leave_type_id',
        'leave_year_id',
        'start_date',
        'end_date',
        'total_days',
        'working_days',
        'calendar_days',
        'block_number',
        'reason',
        'status',
        'attachment_path',
        'document_type',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'created_by',
        'is_paid',
        'pay_tier',
        'daily_rate_at_request',
        'total_payable',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:1',
        'working_days' => 'decimal:1',
        'calendar_days' => 'decimal:1',
        'approved_at' => 'datetime',
        'is_paid' => 'boolean',
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
