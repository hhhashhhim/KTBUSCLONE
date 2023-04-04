<?php

namespace App\Http\Controllers\ReportsHeader;

use App\Http\Controllers\Controller;
use App\Models\ReportsHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReportsHeaderController extends Controller
{
    public function index()
    {
        return ReportsHeader::with('addedBy')->where('company_id', Auth::user()->company_id)->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required' => Rule::unique('reports_headers', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Header is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);

        return ReportsHeader::create([
            'name' => $request->name,
            'company_id' => Auth::user()->company_id,
            'added_by' => Auth::user()->id,
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => ['required' => Rule::unique('reports_headers', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],

        ];

        $customMessages = [
            'name.required' => 'Name Field is Required!',
            'name.unique' => 'Header is Already Exist',
        ];
        $this->validate($request, $rules, $customMessages);
        $expCtg = ReportsHeader::find($request->id);
        return $expCtg->update([
            'name' => $request->name,
            'updated_by' => Auth::user()->id,
        ]);
    }

}
