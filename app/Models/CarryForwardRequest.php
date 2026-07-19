<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarryForwardRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'from_year',
        'to_year',
        'days',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'expiry_date',
    ];

    protected $casts = [
        'days' => 'decimal:1',
        'approved_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
