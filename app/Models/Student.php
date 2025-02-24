<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'student_id',
        'email',
        'phone_number'
    ];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_enrollments')
                    ->withTimestamps();
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function getAttendancePercentage($courseId)
    {
        // Get all class sessions for this course
        $totalSessions = ClassSession::where('course_id', $courseId)->count();

        if ($totalSessions === 0) {
            return 0;
        }

        // Get present sessions for this student in this course
        $presentSessions = $this->attendanceRecords()
            ->join('class_sessions', 'attendance_records.class_session_id', '=', 'class_sessions.id')
            ->where('class_sessions.course_id', $courseId)
            ->where('attendance_records.status', 'present')
            ->count();

        return round(($presentSessions / $totalSessions) * 100);
    }
}
