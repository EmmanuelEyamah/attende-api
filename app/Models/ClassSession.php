<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'class_name',
        'date',
        'time',
        'room_location',
        'description',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'attendance_records')
                    ->withPivot('status', 'marked_by')
                    ->withTimestamps();
    }
}
