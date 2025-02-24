<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use Illuminate\Support\Carbon;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'first_name.required' => 'Please enter your first name',
            'last_name.required' => 'Please enter your last name',
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Please enter a password',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return ResponseHelper::success(1, 'Registration successful', $user, 201);
        } catch (Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Registration failed', [], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Please enter your password',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return ResponseHelper::error(0, 'Invalid credentials', [], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return ResponseHelper::success(1, 'Login successful', [
                'user' => $user,
                'token' => $token
            ], 200);
        } catch (Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Login failed', [], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ], [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return ResponseHelper::error(0, 'User not found', [], 404);
            }

            $token = Str::random(60);

            PasswordResetToken::updateOrCreate(
                ['email' => $user->email],
                [
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]
            );

            Mail::to($user->email)->send(new PasswordResetMail($token, $user));

            return ResponseHelper::success(1, 'Password reset link sent to your email', [
                'email' => $user->email
            ], 200);
        } catch (Exception $e) {
            Log::error('Forgot Password Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Failed to process request', [], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $passwordReset = PasswordResetToken::where('token', $request->token)
                ->first();

            if (!$passwordReset) {
                return ResponseHelper::error(0, 'Invalid or expired token', [], 400);
            }

            $user = User::where('email', $passwordReset->email)->first();

            if (!$user) {
                return ResponseHelper::error(0, 'User not found', [], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            $passwordReset->delete();

            return ResponseHelper::success(1, 'Password reset successful', [], 200);
        } catch (Exception $e) {
            Log::error('Reset Password Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Failed to reset password', [], 500);
        }
    }

    public function getProfile()
    {
        try {
            $user = Auth::user();
            return ResponseHelper::success(1, 'Profile retrieved successfully', [
                'user' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error('Get Profile Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Failed to retrieve profile', [], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . Auth::id(),
        ], [
            'first_name.required' => 'Please enter your first name',
            'last_name.required' => 'Please enter your last name',
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $user = Auth::user();
            $user->update($request->only([
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'department',
                'office_location',
            ]));

            return ResponseHelper::success(1, 'Profile updated successfully', [
                'user' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error('Update Profile Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Failed to update profile', [], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'different:current_password'
            ],
        ], [
            'current_password.required' => 'Please enter your current password',
            'password.required' => 'Please enter your new password',
            'password.min' => 'New password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
            'password.different' => 'New password must be different from current password',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return ResponseHelper::error(0, 'Current password is incorrect', [], 400);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return ResponseHelper::success(1, 'Password changed successfully', [], 200);
        } catch (Exception $e) {
            Log::error('Change Password Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Failed to change password', [], 500);
        }
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.exists' => 'Email address not found'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), [], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            $token = Str::random(60);

            PasswordResetToken::updateOrCreate(
                ['email' => $user->email],
                [
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]
            );

            Mail::to($user->email)->send(new PasswordResetMail($token, $user));

            return ResponseHelper::success(1, 'New verification code sent to your email', [
                'email' => $user->email
            ], 200);
        } catch (Exception $e) {
            Log::error('Resend OTP Error: ' . $e->getMessage());
            return ResponseHelper::error(0, 'Failed to send verification code', [], 500);
        }
    }
}
