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
        $secondLevel = Account::where('account_id' , '!=' ,'0')->get();
       
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
                'location_id'  => 0,
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
                'account_id' => [
                    'required',
                    'integer',
                    'gt:0', // Ensures that the account_id is greater than 0
                ],
                'category' => [
                    'required',
                ],
            ]);
            
            if($request->category == "parent")
            {
                $request->validate([
                    'level_three.name' => [
                        'required',
                        Rule::unique('account_groups', 'name')->ignore($request->level_three['id']),
                    ],
                ]);
            }
            else
            {
                $request->validate([
                    'name' => [
                        'required',
                        Rule::unique('account_groups', 'name')->ignore($request->id),
                    ],
                    'parent_id' => [
                        'required',
                        'integer',
                        'gt:0',
                    ],
                ]);
            }
            DB::beginTransaction();

            return 'helo';

            if($request->category == "parent")
            {
                if($request->account_id == $request->level_two['id'])
                {
                    AccountGroup::where('id', $request->id )->update([
                        'name'       => strtoupper($request->name),
                    ]);
                }
                else
                {
                    $code = AccountGroup::latest('id')->where('account_id', $request->account_id )->where('parent_id', 0 )->limit(1)->value('code') + 1;
                    $code = str_pad($code, 2, '0', STR_PAD_LEFT);

                    AccountGroup::where('id', $request->id )->update([
                        'name'       => strtoupper($request->name),
                        'code'       => $code,
                        'account_id' => $request->account_id, 
                        'parent_id'  => 0,
                    ]);
                }
            }
            else
            {
                if($request->parent_id == $request->level_three['id'])
                {
                    AccountGroup::where('id', $request->id )->update([
                        'name'       => strtoupper($request->name),
                    ]);
                }
                else
                {
                    $code = AccountGroup::latest('id')->where('parent_id', $request->parent_id )->limit(1)->value('code') + 1;
                    $code = str_pad($code, 3, '0', STR_PAD_LEFT);

                    AccountGroup::where('id', $request->id )->update([
                        'name'       => strtoupper($request->name),
                        'code'       => $code,
                        'account_id' => $request->account_id, 
                        'parent_id'  => $request->parent_id,
                    ]);
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }
}
