<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\Bus\BusClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BusClassController extends Controller
{ public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    protected function index()
    {
        return BusClass::with('addedBy')->orderBy('id')->where('company_id', $this->company_id)->get();
    }

    public function storeBusClass(Request $request)
    {
            dd($request->all());
        $rules = [
            'BusClassName' => ['required', Rule::unique('bus_classes', 'name')->where('company_id', $this->company_id)->whereNull('deleted_at')],
//            'BusClassColor' => 'required',
            'noOfRows' => 'required|integer',
            'noOfCols' => 'required|integer',
        ];

        $customMessages = [
            'BusClassName.required' => 'Bus Class Name is Required!',
//            'BusClassColor.required' => 'Bus Class Color is Required!',
            'name.unique' => 'Bus Class Name is already available!',
            'noOfRows.required' => 'No of Rows of Bus  is Required!',
            'noOfCols.required' => 'No of Cols of Bus  is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return BusClass::create([
            'name' => $request->BusClassName,
            'color' => $request->BusClassColor ? '#000000' : $request->BusClassColor,
            'is_active' => !$request->isActive ? 1 : $request->isActive,
            'seat_map' => $request->seatMap,
            'no_of_rows' => $request->noOfRows,
            'no_of_cols' => $request->noOfCols,
            'company_id' => $this->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function updateBusClass(Request $request)
    {
        return BusClass::where('id', $request->id)->update([
            'name' => $request->name,
            'color' => $request->busClassColor,
            'seat_map' => $request->seat_map,
            'no_of_rows' => $request->no_of_rows,
            'no_of_cols' => $request->no_of_cols,
            'is_active' => $request->is_active,
        ]);
    }

    public function deleteBusClass(Request $request)
    {
        return BusClass::find($request->id)->delete();
    }
}
