<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'date', 'calendar_type', 'recurring_yearly', 'description'
    ];

    protected $casts = [
        'date' => 'date',
        'recurring_yearly' => 'boolean',
    ];
}
