<?php

namespace App\Http\Controllers\Account;

use App\Models\Account\Account;
use App\Models\Account\AccountGroup;
use App\Models\Account\AccountHead;
use App\Models\Account\Bank;
use App\Models\Account\Cash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class AccountHeadController extends BaseController
{

    public function accountHeads(Request $request)
    {
        $firstLevel = Account::where('parent_id' , '=' ,'0')->get(["id","name"]);
       
        $accountHeads = AccountHead::with("level_one:id,name,code","level_two:id,name,code","level_three:id,name,code","level_four:id,name,code")
        ->latest('id')
        ->where(["company_id"=>Auth::user()->company_id])->get();

        return [
            "firstLevel" => $firstLevel,
            "accountHeads" => $accountHeads,
        ];
    }

    public function headStore(Request $request)
    {
        
        try {
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_heads','name')->where("company_id",Auth::user()->company_id),
                ],
                'first_level' => [
                    'required',
                ],
                'second_level' => [
                    'required',
                ],
                'third_level' => [
                    'required',
                ],
                'fourth_level' => [
                    'required',
                ],
            ]);
            DB::beginTransaction();

            $head = accountHeadCreate( 
                $request->name,
                $request->first_level,
                $request->second_level,
                $request->third_level,
                $request->fourth_level
            );

            if($request->fourth_level == 31)
            {
                Bank::create([
                    "name" => $request->name,
                    "address" => "dumy",
                    "iban" => "000",
                    "account_number" => "000",
                    "balance" => 0,
                    "status" => "active",
                    "account_head_id" => $head->id,
                    'added_by' => Auth::user()->id,
                    "company_id" => Auth::user()->company_id
                ]);
            }
            
            if($request->fourth_level == 30)
            {
                Cash::create([
                    "amount" => 0,
                    "account_head_id" => $head->id,
                    'added_by' => Auth::user()->id,
                    "company_id" => Auth::user()->company_id
                ]);
            }

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->username." | Added Head ($request->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([],201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }
    
    public function headUpdate(Request $request)
    {
        
        try {
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_heads','name')->where("company_id",Auth::user()->company_id)->ignore($request->id),
                ],
            ]);
            DB::beginTransaction();

            AccountHead::where("id",$request->id)->update([
                'name' => strtoupper($request->name),
                'updated_by' => Auth::user()->id,
            ]);

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->username." | Updated Head ($request->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([],200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }

    public function accountHeadBanks(Request $request)
    {  
        $accountHeadBanks = AccountHead::with("level_one:id,name,code","level_two:id,name,code","level_three:id,name,code","level_four:id,name,code","head_bank")
        ->latest('id')
        ->where(["company_id"=>Auth::user()->company_id,"group_id"=>31])->get();

        return [
            "accountHeadBanks" => $accountHeadBanks,
        ];
    }

    public function headBankStore(Request $request)
    {
        
        try {
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_heads','name')->where("company_id",Auth::user()->company_id),
                ],
                'address' => [
                    'required',
                ],
                'iban' => [
                    'required',
                ],
                'account_number' => [
                    'required',
                ],
            ]);
            DB::beginTransaction();

            $head = accountHeadCreate( 
                $request->name,
                1, // Asset
                6, // Current asset
                29, // Cash and bank balances
                31, // Bank current account
            );

            Bank::create([
                "name" => $request->name,
                "address" => $request->address,
                "iban" => $request->iban,
                "account_number" => $request->account_number,
                "balance" => 0,
                "status" => "active",
                "account_head_id" => $head->id,
                'added_by' => Auth::user()->id,
                "company_id" => Auth::user()->company_id
            ]);

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->username." | Added Bank Head ($request->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([],201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }
    
    public function headBankUpdate(Request $request)
    {
        
        try {
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_heads','name')->where("company_id",Auth::user()->company_id)->ignore($request->id),
                ],
                'head_bank.address' => [
                    'required',
                ],
                'head_bank.iban' => [
                    'required',
                ],
                'head_bank.account_number' => [
                    'required',
                ],
            ]);
            DB::beginTransaction();

            AccountHead::where("id",$request->id)->update([
                'name' => strtoupper($request->name),
                'updated_by' => Auth::user()->id,
            ]);

            Bank::where("account_head_id",$request->id)->update([
                "name" => $request->name,
                "address" => $request->head_bank['address'],
                "iban" => $request->head_bank['iban'],
                "account_number" => $request->head_bank['account_number'],
                'updated_by' => Auth::user()->id,
            ]);

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->username." | Added Bank Head ($request->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([],200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }
    
    public function accountHeadCash(Request $request)
    {  
        $accountHeadCash = AccountHead::with("level_one:id,name,code","level_two:id,name,code","level_three:id,name,code","level_four:id,name,code","head_cash")
        ->latest('id')
        ->where(["company_id"=>Auth::user()->company_id,"group_id"=>30])->get();

        return [
            "accountHeadCash" => $accountHeadCash,
        ];
    }

    public function headCashStore(Request $request)
    {
        
        try {
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_heads','name')->where("company_id",Auth::user()->company_id),
                ],
            ]);
            DB::beginTransaction();

            $head = accountHeadCreate( 
                $request->name,
                1, // Asset
                6, // Current asset
                29, // Cash and bank balances
                30, // Cash ledger
            );

            Cash::create([
                "amount" => 0,
                "account_head_id" => $head->id,
                'added_by' => Auth::user()->id,
                "company_id" => Auth::user()->company_id
            ]);

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->username." | Added Cash Head ($request->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([],201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }
    
    public function headCashUpdate(Request $request)
    {
        
        try {
            $request->validate([
                'name' => [
                    'required',
                    Rule::unique('account_heads','name')->where("company_id",Auth::user()->company_id)->ignore($request->id),
                ],
            ]);
            DB::beginTransaction();

            AccountHead::where("id",$request->id)->update([
                'name' => strtoupper($request->name),
                'updated_by' => Auth::user()->id,
            ]);

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->username." | Added Bank Head ($request->name)",
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);

            DB::commit();
            return response()->json([],200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
        }

    }
}
