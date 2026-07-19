<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'leave_request_id',
        'days',
    ];

    protected $casts = [
        'days' => 'decimal:1',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
