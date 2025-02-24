<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CourseEnrollmentController extends Controller
{
    public function join(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'student_id' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:255',
            'join_code' => 'required|string|exists:courses,join_code',
        ], [
            'first_name.required' => 'Please enter your first name',
            'last_name.required' => 'Please enter your last name',
            'student_id.required' => 'Please enter your student ID',
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'phone_number.required' => 'Please enter your phone number',
            'join_code.required' => 'Please enter the course join code',
            'join_code.exists' => 'Invalid course join code',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            // Get or create student
            $student = Student::firstOrCreate(
                ['student_id' => $request->student_id],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone_number' => $request->phone_number,
                ]
            );

            // Get course by join code
            $course = Course::where('join_code', $request->join_code)
                          ->where('status', 'active')
                          ->firstOrFail();

            // Check if already enrolled
            if ($student->courses()->where('course_id', $course->id)->exists()) {
                return ResponseHelper::error(0, 'You are already enrolled in this course', [], 400);
            }

            // Enroll student
            $student->courses()->attach($course->id);

            return ResponseHelper::success(1, 'Successfully joined the course', [
                'student' => $student,
                'course' => $course
            ], 200);

        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to join course', [], 500);
        }
    }

    public function getEnrolledStudents($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);
            $students = $course->students;

            return ResponseHelper::success(1, 'Students retrieved successfully', [
                'course' => $course,
                'students' => $students
            ], 200);

        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve students', [], 500);
        }
    }

    public function getStudentCourses($studentId)
    {
        try {
            $student = Student::where('student_id', $studentId)->firstOrFail();
            $courses = $student->courses;

            return ResponseHelper::success(1, 'Courses retrieved successfully', [
                'student' => $student,
                'courses' => $courses
            ], 200);

        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve courses', [], 500);
        }
    }
}
