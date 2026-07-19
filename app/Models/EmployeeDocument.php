<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_name',
        'employee_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'file_type',
        'uploaded_by',
        'description',
        'status',
        'uploaded_at',
        'applicant_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the applicant/employee that owns this document
     */
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    public function getFileIconAttribute()
    {
        $ext = strtolower(pathinfo($this->document_name, PATHINFO_EXTENSION));
        $icons = [
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'xls' => '📊',
            'xlsx' => '📊',
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'png' => '🖼️',
            'gif' => '🖼️',
            'txt' => '📃',
            'zip' => '📦',
            'rar' => '📦',
        ];
        return $icons[$ext] ?? '📎';
    }
}
