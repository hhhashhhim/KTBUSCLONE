<?php

namespace App\Http\Controllers\ReportsHeader;

use App\Http\Controllers\Controller;
use App\Models\ReportsHeader;
use App\Models\ReportHeaderLink;
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

    public function linkGet(Request $request)
    {
        
        $links = ReportHeaderLink::where(['company_id'=> Auth::user()->company_id,'ticket_merge_id'=>$request->ticket_merge_id])->get();
        $headers = ReportsHeader::with('addedBy')->where('company_id', Auth::user()->company_id)->get();
        if($links->count() > 0)
        {
            return [
                "headers" => $headers,
                "links" => $links
            ];
        }
        else
        {
            return [
                "headers" => $headers,
                "links" => null
            ];
        }
    }

    public function headerLink(Request $request)
    {
        ReportHeaderLink::where("ticket_merge_id", $request->ticket_merge_id)->delete();
        foreach ($request->headIds as $key => $value) {
            ReportHeaderLink::create([
                'ticket_merge_id' => $request->ticket_merge_id,
                'header_id' => $request->headIds[$key],
                'value' => $request->values[$key],
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
            ]);
        }
    }
}
