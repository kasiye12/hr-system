<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id', 'leave_type_id', 'year',
        'total_days', 'used_days', 'remaining_days', 'expiry_date'
    ];

    protected $casts = [
        'total_days' => 'decimal:1',
        'used_days' => 'decimal:1',
        'remaining_days' => 'decimal:1',
        'expiry_date' => 'date',
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
