<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Inventory\ProductCategory;
use App\Support\ActivityLogger;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{
    //
    public function index()
    {
        $categories = ProductCategory::withCount('products')
            ->get()
            ->map(function ($category) {
                $category->is_deletable = $category->products_count == 0;
                return $category;
            });
    
        return response()->json([
            'success' => true,
            'message' => 'Categories fetched successfully.',
            'data'    => $categories,
        ], 200);
    }
    
    public function store(Request $request)
    {
        
        $category = ProductCategory::create([
            'name' => $request->input('category'),
            'added_by' => Auth::user()->id,
            'company_id'=> Auth::user()->company_id,
        ]);

        ActivityLogger::log('Inventory Product Category', 'create', 'Product category created', $category->id, [], $category->toArray(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => $category,
        ], 201);
    }


    /**
     * Update an existing category.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id'    => 'required|exists:product_categories,id',
            'name'  => 'required|string|max:255',
        ]);
        
        $category = ProductCategory::findOrFail($request->input('id'));
        $oldValues = $category->only(['name']);
        $category->name = $request->input('name');
        $category->save();

        ActivityLogger::log('Inventory Product Category', 'update', 'Product category updated', $category->id, $oldValues, $category->only(['name']), $request);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => $category,
        ], 200);
    }

    /**
     * Delete a category.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:product_categories,id',
        ]);
        
        $category = ProductCategory::findOrFail($request->input('id'));
        $oldValues = $category->toArray();
        $deleted = $category->delete();
        
        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category.',
            ], 500);
        }
        ActivityLogger::log('Inventory Product Category', 'delete', 'Product category deleted', $category->id, $oldValues, [], $request);
        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ], 200);
        
    }
 
}
