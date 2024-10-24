<?php

namespace App\Http\Controllers\Account\report;

use App\Models\Account\Account;
use App\Models\Account\AccountGroup;
use App\Models\Terminal;
use App\Models\Account\AccountHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use App\Models\Account\AccountTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class FinanceReportController extends BaseController
{
    public function helperData(Request $request)
    {
        $fourth_level = AccountGroup::where("parent_id", '!=', 0)
        ->get(["id","name","code"]);
        $heads = AccountHead::where(["company_id"=>Auth::user()->company_id])
        ->get(["id","name","code"]);
        $terminals = Terminal::where(["company_id"=>Auth::user()->company_id])
        ->get(["id","name"]);
        

        return [
            "fourth_level" => $fourth_level,
            "heads" => $heads,
            "terminals" => $terminals,
        ];
    }

    public function receiptReport(Request $request)
    {
        $receipts = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("receipt_id",'!=',null)
        ->with("account_head:id,name,code","terminal:id,name")
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("receipt_id",'ASC')
        ->get()
        ->groupBy('receipt_id')
        ->map(function ($group) {
            $group->shift();  
            return $group;
        })
        ->flatten(1); //this take inner data from groupby and leave it at parent

        return [
            "receipts" => $receipts,
        ];
    }
    
    public function generalLedgerReport(Request $request)
    {
        $heads = AccountHead::where(["company_id"=>Auth::user()->company_id,"group_id"=>$request->level_four])->pluck('id');
        
        // to get name
        $level_four = AccountGroup::where("id",$request->level_four)->first();
        $terminal = Terminal::where("id",$request->terminal)->first();

        // to get sum of previous debits
        $previous_debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of previous credits
        $previous_credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // to get sum of debits
        $debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of credits
        $credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->with("account_head:id,name,code","other_head_name:id,name,code")
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->orderBy('document_id')
        ->get();

        $general_ledgers = (object)[];
        $general_ledgers->terminal = $terminal ? $terminal->name : null;
        $general_ledgers->debits = $debits;
        $general_ledgers->credits = $credits;
        $general_ledgers->record = $record;
        $general_ledgers->level_four = $level_four;
        $general_ledgers->from = $request->from;
        $general_ledgers->to = $request->to;
        $general_ledgers->previous_debits = $previous_debits;
        $general_ledgers->previous_credits = $previous_credits;

        return [
            "general_ledgers" => $general_ledgers,
        ];
    }
    
    public function ledgerReport(Request $request)
    {
        // to get name
        $head = AccountHead::with("level_four:id,name")->where("id",$request->head)->first();
        $terminal = Terminal::where("id",$request->terminal)->first();

        // to get sum of previous debits
        $previous_debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of previous credits
        $previous_credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // to get sum of debits
        $debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of credits
        $credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("document_id",'ASC')
        ->orderBy('document_id')
        ->get();

        $ledgers = (object)[];
        $ledgers->terminal = $terminal ? $terminal->name : null;
        $ledgers->debits = $debits;
        $ledgers->credits = $credits;
        $ledgers->record = $record;
        $ledgers->head = $head;
        $ledgers->from = $request->from;
        $ledgers->to = $request->to;
        $ledgers->previous_debits = $previous_debits;
        $ledgers->previous_credits = $previous_credits;

        return [
            "ledgers" => $ledgers,
        ];
    }
    public function journalReport(Request $request)
    {
        $terminal = Terminal::where("id",$request->terminal)->first();

        // to get sum of previous debits
        $previous_debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of previous credits
        $previous_credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // to get sum of debits
        $debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of credits
        $credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->with("account_head:id,name,code,group_id","other_head_name:id,name,code","account_head.level_four:id,name,code")
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->orderBy('document_id')
        ->get();

        $journals = (object)[];
        $journals->terminal = $terminal ? $terminal->name : null;
        $journals->debits = $debits;
        $journals->credits = $credits;
        $journals->record = $record;
        $journals->from = $request->from;
        $journals->to = $request->to;
        $journals->previous_debits = $previous_debits;
        $journals->previous_credits = $previous_credits;

        return [
            "journals" => $journals,
        ];
    }

    public function trialSheetReport(Request $request)
    {
        $terminal = Terminal::where("id",$request->terminal)->first();
        // this is for get previous trial
        $previous_transactions = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->get()
        ->groupBy(["account_id","group_id"]);

        // this is to get duration trial
        $current_transactions = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->get()
        ->groupBy(["account_id","group_id"]);

        // this loop for if any previous trial or current trial does't respond then this loop will handle and show    
        $total_transactions = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->with("level_two:id,name,code","level_four:id,name")
        ->get()
        ->groupBy(["account_id","group_id"]);


        $main = [];
        // two mean level two
        foreach($total_transactions as $keyTwo=>$two)
        {
            $single = [];
            $fourData = [];
            // four mean four level
            foreach($two as $keyFour=>$four)
            {
                $temp = (object)[];

                $temp->level_four_id =  $four[0]->level_four->id;
                $temp->level_four_name =  $four[0]->level_four->name;


                $temp->current_debits = isset($current_transactions[$keyTwo][$keyFour]) ? $current_transactions[$keyTwo][$keyFour]->sum('debit') : 0;
                $temp->current_credits = isset($current_transactions[$keyTwo][$keyFour]) ? $current_transactions[$keyTwo][$keyFour]->sum('credit') : 0;

                $temp->previous_debits = isset($previous_transactions[$keyTwo][$keyFour]) ? $previous_transactions[$keyTwo][$keyFour]->sum('debit') : 0;
                $temp->previous_credits = isset($previous_transactions[$keyTwo][$keyFour]) ? $previous_transactions[$keyTwo][$keyFour]->sum('credit') : 0;

                $temp->closing_debits = $temp->current_debits + $temp->previous_debits;
                $temp->closing_credits =  $temp->current_credits + $temp->previous_credits;

                $fourData[] = $temp;
            } 
            
            $single['level_two_id'] = $two->first()[0]->level_two->id;
            $single['level_two_name'] = $two->first()[0]->level_two->name;
            $single['level_four'] = $fourData;
            $main[] = $single;
        }

        $data = (object)[];
        $data->record = $main;
        $data->terminal = $terminal ? $terminal->name : null;
        $data->from = $request->from;
        $data->to = $request->to;
        return [
            "data" => $data,
        ];
    }

    public function dailyReport(Request $request)
    {
        $terminal = Terminal::where("id",$request->terminal)->first();
        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->current, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->current." 00:00:00");
            $q->where('created_at', '<=', $request->current." 23:59:59");
        })
        ->with("account_head:id,name,code","level_four:id,name,code")
        ->orderBy("document_id",'ASC')
        ->get()
        ->groupBy(["type","document_id"]);

        $main = [];
        foreach($record as $outerKey=>$type)
        {
            $single = [];
            foreach($type as $innerKey=>$document)
            {
                $temp = [];
                $temp['document_id'] = $document[0]->document_id;
                $temp['type'] = $document[0]->type;
                $temp['amount'] = $document->sum('credit');
                $temp['narration'] = $document[0]->narration;
                $temp['account'] = $document[0]->account_head->name;
                $temp['parent_account'] = $document[0]->level_four->name;
                $temp['created_at'] = $document[0]->created_at;
                $single[] = (object)$temp;
            }
            $main[$outerKey] = (object)$single;
        }
        
        $daily_data = (object)[];
        $daily_data->terminal = $terminal ? $terminal->name : null;
        $daily_data->current = $request->current;
        $daily_data->record = (object)$main;

        return [
            "daily_data" => $daily_data,
        ];
    }
}
