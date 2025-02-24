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
            $course = Course::findOrFail($request->course_id);
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
            return ResponseHelper::error(0, 'Failed to create class session', [], 500);
        }
    }

    public function getCourseClasses($courseId)
    {
        try {
            $classes = ClassSession::where('course_id', $courseId)
                                 ->with(['creator', 'attendanceRecords'])
                                 ->get();

            return ResponseHelper::success(1, 'Classes retrieved successfully', $classes, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve classes', [], 500);
        }
    }

    public function getClassAttendance($sessionId)
    {
        try {
            $classSession = ClassSession::with(['course', 'attendanceRecords.student'])
                                      ->findOrFail($sessionId);

            $attendance = $classSession->attendanceRecords()->with('student')->get();

            return ResponseHelper::success(1, 'Attendance records retrieved successfully', [
                'class_session' => $classSession,
                'attendance' => $attendance
            ], 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve attendance records', [], 500);
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
            return ResponseHelper::error(0, 'Failed to mark attendance', [], 500);
        }
    }

    public function bulkMarkAttendance(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $records = [];
            foreach ($request->attendance as $record) {
                AttendanceRecord::updateOrCreate(
                    [
                        'class_session_id' => $sessionId,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'status' => $record['status'],
                        'marked_by' => Auth::id(),
                    ]
                );
            }

            return ResponseHelper::success(1, 'Attendance marked successfully', [], 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to mark attendance', [], 500);
        }
    }
}
