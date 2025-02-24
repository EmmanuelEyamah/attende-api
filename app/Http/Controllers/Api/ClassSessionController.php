<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Course;
use App\Models\Student;
use App\Models\ClassSession;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClassSessionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'class_name' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'room_location' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'course_id.required' => 'Please select a course',
            'course_id.exists' => 'Selected course does not exist',
            'class_name.required' => 'Please enter a class name',
            'date.required' => 'Please select a date',
            'date.after_or_equal' => 'Date must be today or in the future',
            'time.required' => 'Please select a time',
            'room_location.required' => 'Please enter a room/location',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $classSession = ClassSession::create([
                'course_id' => $request->course_id,
                'class_name' => $request->class_name,
                'date' => $request->date,
                'time' => $request->time,
                'room_location' => $request->room_location,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            // Create default absent records for all enrolled students
            $course = Course::with('students')->findOrFail($request->course_id);
            foreach ($course->students as $student) {
                AttendanceRecord::create([
                    'class_session_id' => $classSession->id,
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'marked_by' => Auth::id(),
                ]);
            }

            return ResponseHelper::success(1, 'Class session created successfully', $classSession, 201);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to create class session: ' . $e->getMessage(), [], 500);
        }
    }

    public function getCourseClasses($courseId)
    {
        try {
            $classes = ClassSession::where('course_id', $courseId)
                                 ->with(['creator', 'attendanceRecords.student', 'course.students'])
                                 ->orderBy('date', 'desc')
                                 ->orderBy('time', 'desc')
                                 ->get();

            return ResponseHelper::success(1, 'Classes retrieved successfully', $classes, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve classes: ' . $e->getMessage(), [], 500);
        }
    }

    public function getClassAttendance($sessionId)
    {
        try {
            // Get the class session with related course and students
            $classSession = ClassSession::with(['course.students', 'attendanceRecords.student'])
                                       ->findOrFail($sessionId);

            // Get the enrolled students from the course
            $course = $classSession->course;
            $students = $course->students;

            // Check for missing attendance records and create them for any student who doesn't have a record
            foreach ($students as $student) {
                $exists = AttendanceRecord::where('class_session_id', $sessionId)
                                        ->where('student_id', $student->id)
                                        ->exists();

                if (!$exists) {
                    AttendanceRecord::create([
                        'class_session_id' => $sessionId,
                        'student_id' => $student->id,
                        'status' => 'absent',
                        'marked_by' => Auth::id(),
                    ]);
                }
            }

            // Reload the class session with the newly created records
            $classSession->load(['attendanceRecords.student']);
            $attendance = $classSession->attendanceRecords()->with('student')->get();

            return ResponseHelper::success(1, 'Attendance records retrieved successfully', [
                'class_session' => $classSession,
                'attendance' => $attendance
            ], 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve attendance records: ' . $e->getMessage(), [], 500);
        }
    }

    public function markAttendance(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $attendance = AttendanceRecord::updateOrCreate(
                [
                    'class_session_id' => $sessionId,
                    'student_id' => $request->student_id,
                ],
                [
                    'status' => $request->status,
                    'marked_by' => Auth::id(),
                ]
            );

            return ResponseHelper::success(1, 'Attendance marked successfully', $attendance, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to mark attendance: ' . $e->getMessage(), [], 500);
        }
    }

    public function bulkMarkAttendance(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $records = [];
            foreach ($request->attendance as $record) {
                $attendance = AttendanceRecord::updateOrCreate(
                    [
                        'class_session_id' => $sessionId,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'status' => $record['status'],
                        'marked_by' => Auth::id(),
                    ]
                );
                $records[] = $attendance;
            }

            // Get updated class session data with all attendance records
            $classSession = ClassSession::with(['course.students', 'attendanceRecords.student'])
                                      ->findOrFail($sessionId);

            return ResponseHelper::success(1, 'Attendance marked successfully', [
                'class_session' => $classSession,
                'records' => $records
            ], 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to mark attendance: ' . $e->getMessage(), [], 500);
        }
    }
}
