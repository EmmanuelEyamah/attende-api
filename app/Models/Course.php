<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'course_name',
        'course_description',
        'schedule',
        'status',
        'created_by',
        'join_code'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected $appends = ['student_count'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            // Generate unique join code
            $course->join_code = Str::random(8);
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'course_enrollments')
                    ->withTimestamps();
    }

     // Accessor for student count
     public function getStudentCountAttribute()
     {
         return $this->students()->count();
     }
}
