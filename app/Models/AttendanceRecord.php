<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_session_id',
        'student_id',
        'status',
        'marked_by'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
