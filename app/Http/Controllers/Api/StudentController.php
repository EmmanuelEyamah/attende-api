<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Student::query()
                ->with(['courses' => function($query) {
                    $query->select('courses.id', 'course_name', 'course_code');
                }]);

            // Search filter
            if ($request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('student_id', 'LIKE', "%{$search}%");
                });
            }

            // Course filter
            if ($request->course_id) {
                $query->whereHas('courses', function($q) use ($request) {
                    $q->where('courses.id', $request->course_id);
                });
            }

            $students = $query->get();

            // Calculate attendance percentage for each student
            $students = $students->map(function($student) use ($request) {
                $courseId = $request->course_id;
                $courses = $student->courses->map(function($course) use ($student) {
                    $course->attendance_percentage = $student->getAttendancePercentage($course->id);
                    return $course;
                });

                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'phone_number' => $student->phone_number,
                    'courses' => $courses
                ];
            });

            // Get course summary
            $courseSummary = Course::withCount('students')
                ->get()
                ->map(function($course) {
                    return [
                        'id' => $course->id,
                        'course_code' => $course->course_code,
                        'course_name' => $course->course_name,
                        'student_count' => $course->students_count
                    ];
                });

            return ResponseHelper::success(1, 'Students retrieved successfully', [
                'students' => $students,
                'course_summary' => $courseSummary
            ], 200);

        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve students', [], 500);
        }
    }

    public function getStudentsByCourse($courseId)
    {
        try {
            $course = Course::with('students')->findOrFail($courseId);

            $students = $course->students->map(function($student) use ($courseId) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'phone_number' => $student->phone_number,
                    'attendance_percentage' => $student->getAttendancePercentage($courseId)
                ];
            });

            return ResponseHelper::success(1, 'Students retrieved successfully', [
                'course' => [
                    'id' => $course->id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'student_count' => $students->count()
                ],
                'students' => $students
            ], 200);

        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve students', [], 500);
        }
    }

    public function getStudentAttendance($studentId)
    {
        try {
            $student = Student::with('courses')->findOrFail($studentId);

            $courseAttendance = $student->courses->map(function($course) use ($student) {
                return [
                    'course_id' => $course->id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'attendance_percentage' => $student->getAttendancePercentage($course->id)
                ];
            });

            return ResponseHelper::success(1, 'Student attendance retrieved successfully', [
                'student' => [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                ],
                'course_attendance' => $courseAttendance
            ], 200);

        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve student attendance', [], 500);
        }
    }
}
