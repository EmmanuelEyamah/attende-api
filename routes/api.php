<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseEnrollmentController;
use App\Http\Controllers\Api\ClassSessionController;
use App\Http\Controllers\Api\StudentController;

Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        // Public routes
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('forgot-password', 'forgotPassword');
        Route::post('reset-password', 'resetPassword');
        Route::post('resend-otp', 'resendOtp');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('profile', 'getProfile');
            Route::put('update-profile', 'updateProfile');
            Route::post('change-password', 'changePassword');
        });
    });
});

Route::prefix('courses')->group(function () {
    Route::controller(CourseController::class)->group(function () {
        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::patch('/{id}/status', 'updateStatus');
        });
    });
});

Route::prefix('courses')->group(function () {
    Route::controller(CourseEnrollmentController::class)->group(function () {
        // Public route for joining a course
        Route::post('/join', 'join');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/{courseId}/students', 'getEnrolledStudents');
            Route::get('/student/{studentId}', 'getStudentCourses');
        });
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('class-sessions')->group(function () {
        Route::controller(ClassSessionController::class)->group(function () {
            Route::post('/', 'store');
            Route::get('/course/{courseId}', 'getCourseClasses');
            Route::get('/{sessionId}/attendance', 'getClassAttendance');
            Route::post('/{sessionId}/attendance', 'markAttendance');
            Route::post('/{sessionId}/attendance/bulk', 'bulkMarkAttendance');
        });
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('students')->group(function () {
        Route::controller(StudentController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/course/{courseId}', 'getStudentsByCourse');
            Route::get('/{studentId}/attendance', 'getStudentAttendance');
        });
    });
});
