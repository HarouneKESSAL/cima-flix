<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /*
    * @param Request $request
    */
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Email is not valid',
            'password.required' => 'Password is required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation error', 'general:validation', 400, $validator->errors());
        }

        $credentials = $request->only('email', 'password');
        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken('authToken')->plainTextToken;
            return ApiResponse::success('Login success', ['accessToken' => $token]);
        }
        return ApiResponse::error('Invalid credentials', 'general:invalid_credentials', 401);
    }

    /*
     * @param Request $request
     */
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'role' => 'nullable|in:admin,user'
        ], [
            'username.required' => 'Username is required',
            'email.required' => 'Email is required',
            'email.email' => 'Email is not valid',
            'email.unique' => 'Email is already taken',
            'password.required' => 'Password is required',
            'role.in' => 'Role is not valid'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation error', 'general:validation', 400, $validator->errors());
        }

        $user = new User();
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        if ($request->role)
            $user->role = $request->role;
        $user->save();

        return ApiResponse::success('Register success', new UserResource($user));
    }

    /*
     * @param Request $request
     */
    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::success('User data', new UserResource($request->user()));
    }
}
