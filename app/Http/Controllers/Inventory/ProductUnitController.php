<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;;

use App\Models\Inventory\ProductUnit;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductUnitController extends Controller
{
    //
    public function index()
    {
        $units = ProductUnit::withCount('products')
            ->latest()->get()
            ->map(function ($unit) {
                $unit->is_deletable = $unit->products_count == 0;
                return $unit;
            });

        return response()->json([
            'success' => true,
            'message' => 'Units fetched successfully.',
            'data'    => $units,
        ], 200);
    }

    
    public function store(Request $request)
    {
        $unit = ProductUnit::create([
            'name'       => $request->input('unit'),
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
            'data'    => $unit,
        ], 201);
    }


    /**
     * Update an existing unit.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id'    => 'required',
            'name'  => 'required',
        ]);
        
        $unit = ProductUnit::findOrFail($request->input('id'));
        $unit->name = $request->input('name');
        $unit->save();

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.',
            'data'    => $unit,
        ], 200);
    }

    /**
     * Delete a unit.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);
        
        $deleted = ProductUnit::destroy($request->input('id'));
        
        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unit.',
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.',
        ], 200);
        
    }
 
}
