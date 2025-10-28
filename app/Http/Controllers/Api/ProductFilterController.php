<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductFilterController extends Controller
{
    public function search(Request $request)
    {
        $word = $request->has('search') ? $request->input('search') : null;

        $products = Product::when($word != null, function ($q) use ($word) {
            $q->where('name', 'like', '%' . $word . '%')
                ->orWhere('description', 'like', '%' . $word . '%');

        })->latest()->get();

        if (count($products) > 0) {
            return ApiResponse::sendResponse(ProductResource::collection($products), 'Search Completed And Products retrieved successfully.');
        }

        return ApiResponse::sendResponse([], 'No matching Products.');
    }

    public function filteredProducts(Request $request)
    {
        $products = Product::query()
            ->when($request->has('price_min'), fn($q) => $q->where('price', '>=', $request->price_min))
            ->when($request->has('price_max'), fn($q) => $q->where('price', '<=', $request->price_max))
            ->when($request->has('stock_min'), function ($q) use ($request) {
                $q->where('stock', '>=', $request->stock_min);

            })->get();

        if (count($products) > 0) {
            return ApiResponse::sendResponse(ProductResource::collection($products),'Filter Completed And Products retrieved successfully.');
        }
        return ApiResponse::sendResponse([], 'No matching Products.');

    }

}

