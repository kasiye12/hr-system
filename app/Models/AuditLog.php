<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'action',
        'details',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->logged_at = now();
        });
    }
}