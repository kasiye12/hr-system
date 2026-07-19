<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCarryForward extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'year',
        'days',
        'is_expired',
    ];

    protected $casts = [
        'days' => 'integer',
        'is_expired' => 'boolean',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
