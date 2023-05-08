<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\CounterExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CounterExpensesController extends Controller
{
    public function index()
    {
        return CounterExpense::where(['company_id' => Auth::user()->company_id, 'terminal_id' => Auth::user()->terminal_id])->get();
    }

    public function store(Request $request)
    {
        $rules = [
            'amount' => 'required | integer',
            'narration' => 'required',
        ];

        $customMessages = [
            'amount.required' => 'Expenses Amount is Required!',
            'narration.required' => 'Expenses Narration is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return CounterExpense::create([
            'company_id' => Auth::user()->company_id,
            'terminal_id' => Auth::user()->terminal_id,
            'amount' => $request->amount,
            'narration' => $request->narration,
            'added_by' => Auth::user()->id,
        ]);

    }

    public function update(Request $request)
    {
        $rules = [
            'amount' => 'required | integer',
            'narration' => 'required',
        ];

        $customMessages = [
            'amount.required' => 'Expenses Amount is Required!',
            'narration.required' => 'Expenses Narration is Required!',
        ];
        $this->validate($request, $rules, $customMessages);
        return CounterExpense::find($request->id)->update([
            'amount' => $request->amount,
            'narration' => $request->narration,
            'updated_by' => Auth::user()->id,
        ]);

    }
}
