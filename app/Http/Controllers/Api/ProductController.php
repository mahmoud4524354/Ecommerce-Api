<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Product::where('is_active', true)->paginate(10);

        if (count($data) > 0) {
            if ($data->total() > $data->perPage()) {

                $data = [
                    'records' => ProductResource::collection($data),
                    'pagination links' => [
                        'current_page' => $data->currentPage(),
                        'per_page' => $data->perPage(),
                        'total' => $data->total(),
                        'links' => [
                            'first' => $data->url(1),
                            'last_page' => $data->url($data->lastPage()),
                        ],
                    ],
                ];

            } else {
                $data = ProductResource::collection($data);
            }
            return ApiResponse::sendResponse($data, 'Products retrieved successfully.');
        }
        return ApiResponse::sendResponse([], 'No Products Available');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|unique:products',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'sku' => $request->sku,
            'is_active' => true
        ]);


        return ApiResponse::sendResponse(new ProductResource($product), 'Product created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiResponse::sendError('Product not found.');
        }

        return ApiResponse::sendResponse(new ProductResource($product), 'Product retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiResponse::sendError('Product not found.', 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'sku' => 'sometimes|required|string|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $product->update([
            'name' => $request->name ?? $product->name,
            'slug' => $request->name ? Str::slug($request->name) : $product->slug,
            'description' => $request->description ?? $product->description,
            'price' => $request->price ?? $product->price,
            'stock' => $request->stock ?? $product->stock,
            'sku' => $request->sku ?? $product->sku,
            'is_active' => $request->is_active ?? $product->is_active,
        ]);

        $product->save();

        return ApiResponse::sendResponse(new ProductResource($product), 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiResponse::sendError('Product not found.', 404);
        }
        $product->delete();  // soft delete

        return ApiResponse::sendResponse(null, 'Product soft deleted successfully.');
    }

    public function forceDelete(string $id)
    {
        $product = Product::withTrashed()->find($id);

        if (!$product) {
            return ApiResponse::sendError('Product not found.', 404);
        }

        $product->forceDelete();
        return ApiResponse::sendResponse(null, 'Product deleted successfully.');
    }


    public function restore(string $id)
    {
        $product = Product::onlyTrashed()->find($id);

        if (!$product) {
            return ApiResponse::sendError('No deleted product found with this ID.', 404);
        }

        $product->restore();
        return ApiResponse::sendResponse(null, 'Product restored successfully.');
    }


    public function trashed()
    {
        $products = Product::onlyTrashed()->get();
        return ApiResponse::sendResponse(ProductResource::collection($products), 'Trashed products retrieved successfully.');
    }

}
