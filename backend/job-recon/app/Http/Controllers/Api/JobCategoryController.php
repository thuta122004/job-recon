<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = JobCategory::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Job categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name',
            'description' => 'nullable|string',
            'icon_class' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        $category = JobCategory::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Job category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $category = JobCategory::find($id);

        // if (!$category) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Category not found'
        //     ], 404);
        // }

        // return response()->json([
        //     'status' => true,
        //     'data' => $category
        // ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = JobCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('job_categories', 'name')->ignore($id)],
            'description' => 'nullable|string',
            'icon_class' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $category->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Job category updated successfully',
            'data' => $category
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = JobCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $newStatus = ($category->status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        $category->update(['status' => $newStatus]);

        return response()->json([
            'status' => true,
            'message' => 'Category status updated to ' . $newStatus,
            'data' => $category
        ], 200);
    }
}
