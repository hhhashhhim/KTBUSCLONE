<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account\Account;
use App\Models\Account\AccountGroup;
use App\Models\Expense\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountController extends Controller
{

    public function accountGroups(Request $request)
    {
        $secondLevel = Account::where('parent_id' , '!=' ,'0')->get();
       
        $fourthLevel = AccountGroup::where('parent_id','!=','0')
        ->with('level_two:id,name,code', 'level_three:id,name,code')
        ->orderBy('name')
        ->get();

        return [
            "secondLevel" => $secondLevel,
            "fourthLevel" => $fourthLevel,
        ];
    }

    public function getThirdLevel(Request $request)
    {
        $thirdLevel = AccountGroup::where(["account_id" => $request->id])->where('parent_id','0')->orderBy('id')->get();
        return [
            "thirdLevel" => $thirdLevel,
        ];
    }

    public function groupStore(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $request->validate([
                'groupName' => 'required|unique:account_groups,name',
                'secondLevel' => 'required',
            ]);
    
            if( $request->thirdLevel == 0 ){
                $code = AccountGroup::latest('id')->where('account_id', $request->secondLevel )->where('parent_id', 0 )->limit(1)->value('code') + 1;
                $code = str_pad($code, 2, '0', STR_PAD_LEFT);
            }else{
                $code = AccountGroup::latest('id')->where('parent_id', $request->thirdLevel )->limit(1)->value('code') + 1;
                $code = str_pad($code, 3, '0', STR_PAD_LEFT);
            }

            $group = AccountGroup::create([
                'name'       => strtoupper($request->groupName),
                'code'       => $code,
                'account_id' => $request->secondLevel, 
                'parent_id'  => $request->thirdLevel,
                'company_id'  => 0,
                'added_by'         => Auth::user()->id
            ]);

            DB::commit();
            return $group;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }

    public function groupUpdate(Request $request)
    {
        // return $request->group->name;
        try {
            
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_groups', 'name')->ignore($request->id),
                ],
            ]);
            
            DB::beginTransaction();

               
            AccountGroup::where('id', $request->id )->update([
                'name'       => strtoupper($request->name),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }

    public function secondLevelOfFirst($first)
    {
        $secondLevel = Account::where(["parent_id" => $first])->orderBy('id')->get(["id","name"]);
        
        return [
            "secondLevel" => $secondLevel,
        ];
    }
    
    public function thirdLevelOfSecond($second)
    {
        $thirdLevel = AccountGroup::where(["account_id" => $second])->where('parent_id','0')->orderBy('id')->get(["id","name"]);
        
        return [
            "thirdLevel" => $thirdLevel,
        ];
    }
    
    public function fourthLevelOfThird($third)
    {
        $fourthLevel = AccountGroup::where(["parent_id" => $third])->orderBy('id')->get(["id","name"]);
        
        return [
            "fourthLevel" => $fourthLevel,
        ];
    }
}
