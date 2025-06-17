<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Inventory\ProductCategory;
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
        $category->name = $request->input('name');
        $category->save();

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
        
        $deleted = ProductCategory::destroy($request->input('id'));
        
        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category.',
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ], 200);
        
    }
 
}
