<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slot',
        'status',
    ];

    protected $casts = [
        'slot' => 'integer',
    ];

    public function dailyEntries()
    {
        return $this->hasMany(DailyEntry::class);
    }
}