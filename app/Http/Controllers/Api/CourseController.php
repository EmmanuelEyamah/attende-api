<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function index()
    {
        try {
            // Get authenticated user ID
            $userId = Auth::id();

            // Filter courses by created_by (courses created by this user)
            $courses = Course::with('creator')
                            ->withCount('students')
                            ->where('created_by', $userId)
                            ->get();

            return ResponseHelper::success(1, 'Courses retrieved successfully', $courses, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to retrieve courses', [], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_code' => 'required|string|max:255|unique:courses',
            'course_name' => 'required|string|max:255',
            'course_description' => 'nullable|string',
            'schedule' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ], [
            'course_code.required' => 'Please enter a course code',
            'course_code.unique' => 'This course code already exists',
            'course_name.required' => 'Please enter a course name',
            'schedule.required' => 'Please enter a schedule',
            'status.required' => 'Please select a status',
            'status.in' => 'Status must be either active or inactive',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $course = Course::create([
                'course_code' => $request->course_code,
                'course_name' => $request->course_name,
                'course_description' => $request->course_description,
                'schedule' => $request->schedule,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            return ResponseHelper::success(1, 'Course created successfully', $course, 201);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to create course', [], 500);
        }
    }

    public function show($id)
    {
        try {
            // Get authenticated user ID
            $userId = Auth::id();

            // Find course by ID and ensure it belongs to the authenticated user
            $course = Course::with('creator')
                            ->withCount('students')
                            ->where('created_by', $userId)
                            ->findOrFail($id);

            return ResponseHelper::success(1, 'Course retrieved successfully', $course, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Course not found or unauthorized', [], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'course_code' => 'required|string|max:255|unique:courses,course_code,'.$id,
            'course_name' => 'required|string|max:255',
            'course_description' => 'nullable|string',
            'schedule' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            // Get authenticated user ID
            $userId = Auth::id();

            // Find course by ID and ensure it belongs to the authenticated user
            $course = Course::where('created_by', $userId)->findOrFail($id);
            $course->update($request->all());

            return ResponseHelper::success(1, 'Course updated successfully', $course, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to update course or unauthorized', [], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Get authenticated user ID
            $userId = Auth::id();

            // Find course by ID and ensure it belongs to the authenticated user
            $course = Course::where('created_by', $userId)->findOrFail($id);
            $course->delete();

            return ResponseHelper::success(1, 'Course deleted successfully', [], 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to delete course or unauthorized', [], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ], [
            'status.required' => 'Please select a status',
            'status.in' => 'Status must be either active or inactive',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            // Get authenticated user ID
            $userId = Auth::id();

            // Find course by ID and ensure it belongs to the authenticated user
            $course = Course::where('created_by', $userId)->findOrFail($id);
            $course->status = $request->status;
            $course->save();

            return ResponseHelper::success(1, 'Course status updated successfully', $course, 200);
        } catch (Exception $e) {
            return ResponseHelper::error(0, 'Failed to update course status or unauthorized', [], 500);
        }
    }
}
