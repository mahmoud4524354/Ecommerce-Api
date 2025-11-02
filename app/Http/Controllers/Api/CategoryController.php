<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::where('is_active', true)->paginate(10);

        if ($categories->isEmpty()) {
            return ApiResponse::sendResponse([], 'No categories available.');
        }

        $data = [
            'records' => CategoryResource::collection($categories),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'first_page_url' => $categories->url(1),
                'last_page_url' => $categories->url($categories->lastPage()),
            ],
        ];

        return ApiResponse::sendResponse($data, 'Categories retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'nullable|boolean'
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = Category::create($data);

        return ApiResponse::sendResponse(new CategoryResource($category), 'Category created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return ApiResponse::sendError([], 'Category not found.', 404);
        }

        return ApiResponse::sendResponse(new CategoryResource($category), 'Category retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return ApiResponse::sendError([], 'Category not found.', 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        $category->update($data);

        return ApiResponse::sendResponse(new CategoryResource($category), 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        if(!$category){
            return ApiResponse::sendError('Category not found.', 404);
        }

        $category->delete();

        return ApiResponse::sendResponse([], 'Category deleted successfully.');
    }
}
