<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class SkillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $skills = Skill::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Skill retrieved successfully',
            'data' => $skills
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:skills,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $skill = Skill::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Skill created successfully',
            'data' => $skill
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
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json([
                'status' => false,
                'message' => 'Skill not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('skills', 'name')->ignore($id),],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $skill->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Skill updated successfully',
            'data' => $skill
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json([
                'status' => false,
                'message' => 'Skill not found',
            ], 404);
        }

       $status = ($skill->status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
       $skill->update(['status' => $status]);

        return response()->json([
            'status' => true,
            'message' => 'Skill status updated to ' . $status,
            'data' => $skill
        ], 200);
    }
}
