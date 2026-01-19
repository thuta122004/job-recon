<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('role')->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'User retrieved successfully',
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'role_id'    => 'required|integer|exists:roles,id',
            'email'      => 'required|email:rfc,dns|max:255|unique:users,email',
            'phone'      => 'required|string|max:255',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'role_id'    => $request->role_id,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => strtolower($request->email),
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'User created successfully',
            'data'    => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'User found',
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role_id'    => 'required|integer|exists:roles,id',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email:rfc,dns|max:255|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:8|confirmed',
            'phone'      => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = [
            'role_id'    => $request->role_id,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => strtolower($request->email),
            'phone'      => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'User updated successfully',
            'data'    => $user
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status === 'SUSPENDED') {
            return response()->json([
                'status'  => false,
                'message' => 'Suspended users cannot be toggled. Unsuspend them first.',
            ], 400);
        }

        $newStatus = ($user->status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        
        $user->update(['status' => $newStatus]);

        return response()->json([
            'status'  => true,
            'message' => 'User status updated to ' . $newStatus,
            'data'    => $user
        ], 200);
    }

    public function suspend($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status === 'INACTIVE') {
            return response()->json([
                'status' => false,
                'message' => 'Inactive users cannot be suspended',
            ], 400);
        }

       $status = ($user->status === 'ACTIVE') ? 'SUSPENDED' : 'ACTIVE';
       $user->update(['status' => $status]);

        return response()->json([
            'status' => true,
            'message' => 'User status updated to ' . $status,
            'data' => $user
        ], 200);
    }
}
