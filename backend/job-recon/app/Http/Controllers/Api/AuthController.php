<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'ACTIVE') {
            return response()->json([
                'message' => "Login blocked. Your account status is: {$user->status}."
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'is_admin' => $user->isAdmin(),
                'is_seeker' => $user->isJobSeeker(),
                'is_employer' => $user->isEmployer(),
                'profile' => $user->profile,
                'job_seeker_profile_id' => $user->profile?->id,
                'employerProfile' => $user->employerProfile ? [
                    'company_name' => $user->employerProfile->company_name,
                    'company_logo_url' => $user->employerProfile->company_logo_url,
                    'is_verified' => $user->employerProfile->is_verified,
                    'employer_profile_id' => $user->employerProfile->id,
                ] : null,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:8|confirmed',
            'phone'      => 'required|string|max:20',
            'role_id'    => ['required', Rule::in([2, 3])],

            'company_name' => 'required_if:role_id,3|nullable|string|max:255',
            'industry'     => 'required_if:role_id,3|nullable|string|max:255',

            'headline' => 'required_if:role_id,2|nullable|string|max:255',
            'location' => 'required_if:role_id,2|nullable|string|max:255',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $user = User::create([
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    'email'      => $request->email,
                    'password'   => Hash::make($request->password),
                    'phone'      => $request->phone,
                    'role_id'    => $request->role_id,
                    'status'     => 'ACTIVE',
                ]);

                if ($user->isEmployer()) {
                    $user->employerProfile()->create([
                        'company_name' => $request->company_name,
                        'industry'     => $request->industry,
                    ]);
                } else if ($user->isJobSeeker()) {
                    $user->profile()->create([
                        'headline' => $request->headline,
                        'location' => $request->location,
                    ]);
                }

                return $user;
            });

            return response()->json([
                'message' => 'Account created successfully.',
                'token'   => $result->createToken('auth_token')->plainTextToken,
                'user'    => $result->load(['role', 'profile', 'employerProfile']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
