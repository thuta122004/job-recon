<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmployerProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = EmployerProfile::with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'ACTIVE');
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Employer profiles retrieved successfully',
            'data' => $profiles
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if ($request->has('specialties') && is_string($request->specialties)) {
            $decoded = json_decode($request->specialties, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['specialties' => $decoded]);
            }
        }
        $validator = Validator::make($request->all(), [
            'user_id'               => 'required|integer|exists:users,id|unique:employer_profiles,user_id',
            'company_name'          => 'required|string|max:255',
            'tagline'               => 'nullable|string|max:255',
            'about_us'              => 'nullable|string',
            'website_url'           => 'nullable|url|max:255',
            'industry'              => 'nullable|string|max:255',
            'headquarters_location' => 'nullable|string|max:255',
            'company_size'          => 'nullable|string|max:255',
            'founded_year'          => 'nullable|integer|min:1800|max:' . date('Y'),
            'specialties'           => 'nullable|array',
            'linkedin_url'          => 'nullable|url|max:255',
            'company_logo'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('employers/logos', 'public');
            $data['company_logo_url'] = $path;
        }

        $profile = EmployerProfile::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Employer profile created successfully',
            'data' => $profile
        ], 201);
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
        $profile = EmployerProfile::find($id);

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Employer profile not found'], 404);
        }

        if ($request->has('specialties') && is_string($request->specialties)) {
            $decoded = json_decode($request->specialties, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['specialties' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'user_id'               => 'required|integer|exists:users,id|unique:employer_profiles,user_id,' . $profile->id,
            'company_name'          => 'required|string|max:255',
            'tagline'               => 'nullable|string|max:255',
            'about_us'              => 'nullable|string',
            'website_url'           => 'nullable|url|max:255',
            'industry'              => 'nullable|string|max:255',
            'headquarters_location' => 'nullable|string|max:255',
            'company_size'          => 'nullable|string|max:255',
            'founded_year'          => 'nullable|integer|min:1800|max:' . date('Y'),
            'specialties'           => 'nullable|array',
            'linkedin_url'          => 'nullable|url|max:255',
            'company_logo'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'delete_logo'           => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('company_logo')) {
            if ($profile->getRawOriginal('company_logo_url')) {
                Storage::disk('public')->delete($profile->getRawOriginal('company_logo_url'));
            }
            $data['company_logo_url'] = $request->file('company_logo')->store('employers/logos', 'public');
        } 
        elseif ($request->delete_logo === 'true' || $request->delete_logo === '1') {
            if ($profile->getRawOriginal('company_logo_url')) {
                Storage::disk('public')->delete($profile->getRawOriginal('company_logo_url'));
            }
            $data['company_logo_url'] = null;
        }

        $profile->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Employer profile updated successfully',
            'data' => $profile->load('user')
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $profile = EmployerProfile::find($id);

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Employer profile not found'], 404);
        }

        $profile->is_verified = !$profile->is_verified;
        $profile->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile verification status updated',
            'data'    => $profile
        ], 200);
    }
}
