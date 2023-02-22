<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account\Account;
use App\Models\CityToCity;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Expense\ExpenseCategory;
use App\Models\Expense\TicketMergeExpense;
use App\Models\Route\RouteFare;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{

    public function getSecondLevel(Request $request)
    {
        return Account::where(["parent_id"=>$request->id])->orderBy('id')->get();
    }
    
    public function categoryStore(Request $request)
    {return $request;
        return Account::where(["parent_id"=>$request->id])->orderBy('id')->get();
    }
}
