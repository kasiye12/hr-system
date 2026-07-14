<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'department',
        'employment_type',
        'row_order',
        'is_reconciliation',
    ];

    protected $casts = [
        'row_order' => 'integer',
        'is_reconciliation' => 'boolean',
    ];

    public function dailyEntries()
    {
        return $this->hasMany(DailyEntry::class);
    }
}