<?php

namespace App\Http\Controllers\FareClass;

use App\Http\Controllers\Controller;
use App\Models\FareClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class FareClassController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    protected function index()
    {
        return FareClass::with('addedBy')->orderBy('id')->where('company_id', $this->company_id)->get();
    }

    public function storeFareClass(Request $request)
    {
        $rules = [
            'FareClassName' => ['required', Rule::unique('fare_classes', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],
//            'noOfRows' => 'required|integer',
//            'noOfCols' => 'required|integer',
        ];

        $customMessages = [
            'FareClassName.required' => 'Fare Class Name is Required!',
            'name.unique' => 'Fare Class Name is already available!',
//            'noOfRows.required' => 'No of Rows of Bus  is Required!',
//            'noOfCols.required' => 'No of Cols of Bus  is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return FareClass::create([
            'name' => $request->FareClassName,
            'is_active' => $request->isActive,
//            'seat_map' => $request->seatMap,
//            'no_of_rows' => $request->noOfRows,
//            'no_of_cols' => $request->noOfCols,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateFareClass(Request $request)
    {
        $rules = [
            'name' => 'required',
//            'no_of_rows' => 'required|integer',
//            'no_of_cols' => 'required|integer',
        ];

        $customMessages = [
            'name.required' => 'FareClass Name is Required!',
//            'no_of_rows.required' => 'No of Rows of Bus  is Required!',
//            'no_of_cols.required' => 'No of Cols of Bus  is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return FareClass::where('id', $request->id)->update([
            'name' => $request->name,
//            'seat_map' => $request->seat_map,
//            'no_of_rows' => $request->no_of_rows,
//            'no_of_cols' => $request->no_of_cols,
            'company_id' => $this->company_id,
            'is_active' => !isset($request->is_Active) ? 0 : $request->is_Active,
            'updated_by' => Auth::user()->id,
        ]);
    }

    public function deleteFareClass(Request $request)
    {
        return FareClass::find($request->id)->delete();
    }
}
