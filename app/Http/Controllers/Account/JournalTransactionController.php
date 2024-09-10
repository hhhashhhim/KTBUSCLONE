<?php

namespace App\Http\Controllers\Account;

use App\Models\Account\Account;
use App\Models\Account\Bank;
use App\Models\Account\Cash;
use App\Models\Account\AccountGroup;
use App\Models\Account\AccountTransaction;
use App\Models\Account\BankTransaction;
use App\Models\Account\AccountReceipt;
use App\Models\Account\CashTransaction;
use App\Models\Terminal;
use App\Models\Account\AccountHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class JournalTransactionController extends BaseController
{
    public function journalTransactions(Request $request)
    {
        // dropdown data
        $terminals = Terminal::where(["company_id"=>Auth::user()->company_id])->get(["id","name"]);
        $heads = AccountHead::with('level_four:id,name')
        ->get()
        ->map(function($single) {
            return [
                'id' => $single->id,
                'text' => $single->name . ' (' . $single->level_four->name . ')',
                'name' => $single->name,
            ];
        });
       
        // main page data
        $journalTransactions = AccountTransaction::
        where(["company_id"=>Auth::user()->company_id])
        ->where("type","JV")
        ->orderBy("id",'ASC')
        ->get()
        ->groupBy('document_id')
        ->map(function ($group) {
            return [
                'id' => $group->first()->id,
                'document_id' => $group->first()->document_id ?? null,
                'cash_name' => $group->first()->account_head->name ?? 'N/A',
                'terminal' => $group->first()->terminal->name ?? 'N/A',
                'amount' => $group->sum("debit"),
                'added_by' => $group->first()->added_by_name->name ?? 'N/A',
                'type' => $group->first()->type,
                'approved' => $group->first()->approved,
                'approved_by' => $group->first()->approved_by_name->name ?? 'N/A',
                'posted_date' => date("H:i d/m/Y",strtotime($group->first()->created_at)),
            ];
        })->values();

        return [
            "terminals" => $terminals,
            "heads" => $heads,
            "journalTransactions" => $journalTransactions,
        ];
    }

    public function journalTransactionAdd(Request $request)
    {
        $request->validate([
            'ledgers' => ['required'],
            'credits' => ['required'],
            'debits' => ['required'],
            'narrations' => ['required'],
        ]);

        try {
            DB::beginTransaction();
            
            
            $document = AccountTransaction::where(["company_id"=>Auth::user()->company_id])
            ->where("type","JV")
            ->orderBy("document_id","DESC")
            ->first();
            $document_id = $document ? $document->document_id + 1 : 1;

    
            foreach ($request->ledgers as $i => $value) {
                AccountTransaction::create([
                    'terminal_id' => $request->terminal,
                    'account_head_id' => $request->ledgers[$i],
                    'other_account_head_id' => $request->ledgers[$i + 1]??$request->ledgers[$i],
                    'credit' => $request->credits[$i] > 0 ? $request->credits[$i] : 0,
                    'debit' => $request->credits[$i] > 0 ? 0 : $request->debits[$i],
                    'document_id' => $document_id,
                    'type' => "JV",
                    'narration' => strtoupper($request->narrations[$i]),
                    'posting_type' => '',
                    'posting_id' => null,
                    'added_by' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
            }

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | Added Transaction JV-".$document_id,
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

    public function journalTransaction(Request $request)
    {
        $transactions = AccountTransaction::
        where(['type'=>$request->type,"company_id"=>Auth::user()->company_id,"document_id"=>$request->id])
        ->orderBy('id','ASC')
        ->get();

        $transaction = (object)[];
        $transaction->id = $transactions[0]->id;
        $transaction->terminal_id = $transactions[0]->terminal_id;
        $transaction->receipt_id = $transactions[0]->receipt_id;
        // to get posting ids
        $transaction->postings = $transactions->pluck("id");
        
        // to get ledgers
        $transaction->ledgers = $transactions->pluck("account_head_id");
        
        // to get credits
        $transaction->credits = $transactions->pluck("credit");
        // to get debits
        $transaction->debits = $transactions->pluck("debit");
        
        // to get receiver narration
        $transaction->narrations = $transactions->pluck("narration");

        return [
            "transaction" => $transaction,
        ];
    }
    
    public function journalTransactionDetail(Request $request)
    {
        $transaction = AccountTransaction::
        where(['type'=>$request->type,"company_id"=>Auth::user()->company_id,"document_id"=>$request->id])
        ->orderBy("id",'ASC')
        ->with("account_head.level_four:id,code","account_head.level_three:id,code","account_head.level_two:id,code","account_head.level_one:id,code")
        ->with("added_by_name:id,name","updated_by_name:id,name")
        ->get();

        return [
            "transaction" => $transaction,
        ];
    }
    
    public function approveJournalTransaction(Request $request)
    {
        try {
            DB::beginTransaction();


            $trans = AccountTransaction::
            where(["company_id"=>Auth::user()->company_id,"document_id"=>$request->id,'type'=>$request->type])->get();

            foreach($trans as $single)
            {

                $head = AccountHead::where(["company_id"=>Auth::user()->company_id,"id"=>$single->account_head_id])
                ->first();

                AccountTransaction::
                where(["company_id"=>Auth::user()->company_id,"id"=>$single->id])
                ->update([
                    "approved" => 1,
                    "approved_by" => Auth::user()->id,
                    "parent_account_id" => $head->parent_account_id,
                    "account_id" => $head->account_id,
                    "parent_group_id" => $head->parent_group_id,
                    "group_id" => $head->group_id
                ]);
            }
            

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | Changed the status of ".$request->type.'-'.$request->id,
                "requested_host" => $request->ip(),
                "company_id" => Auth::user()->company_id
            ]);
            DB::commit();

            return response()->json([],200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database transaction error: ' . $e->getMessage());
            return response()->json(["message" => 'An error occurred during the database transaction'], 409);
        }
    }

    public function journalTransactionUpdate(Request $request)
    {
        $request->validate([
            'ledgers' => ['required'],
            'credits' => ['required'],
            'debits' => ['required'],
            'narrations' => ['required'],
        ]);

        try {
            DB::beginTransaction();
    
            $oldTrans = AccountTransaction::where("id",$request->id)->first();

            AccountTransaction::where(["type"=>$oldTrans->type,"document_id"=>$oldTrans->document_id,"company_id"=>Auth::user()->company_id])
            ->delete();
    
            foreach ($request->ledgers as $i => $value) {
                    AccountTransaction::create([
                        'terminal_id' => $request->terminal_id,
                        'account_head_id' => $request->ledgers[$i],
                        'other_account_head_id' =>$request->ledgers[$i + 1]??$request->ledgers[$i],
                        'credit' => $request->credits[$i] > 0 ? $request->credits[$i] : 0,
                        'debit' => $request->credits[$i] > 0 ? 0 : $request->debits[$i],
                        'document_id' => $oldTrans->document_id,
                        'type' => "JV",
                        'narration' => strtoupper($request->narrations[$i]),
                        'posting_type' => '',
                        'posting_id' => null,
                        'company_id' => Auth::user()->company_id,
                        'added_by' => $oldTrans->added_by ?? Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                        'created_at' => $oldTrans->created_at ?? now(),
                    ]);
            }

            ActivityLog::create([
                "activity_by" => Auth::user()->id,
                "message" => Auth::user()->name." | Updated transaction ".$oldTrans->type.'-'.$oldTrans->document_id,
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
