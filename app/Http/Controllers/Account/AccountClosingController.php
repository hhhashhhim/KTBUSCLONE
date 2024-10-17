<?php

namespace App\Http\Controllers\Account;

use App\Models\Account\Account;
use App\Models\Account\AccountGroup;
use App\Models\Account\AccountHead;
use App\Models\Account\Bank;
use App\Models\Schedule\Schedule;
use App\Models\Account\Cash;
use App\Models\TerminalCommission;
use App\Models\Expense\TicketMergeExpense;
use App\Models\Ticket;
use App\Models\Bus\Bus;
use App\Models\Schedule\TicketClosing;
use App\Models\Schedule\TicketClosingMerge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class AccountClosingController extends BaseController
{

    public function accountClosingUpdate(Request $request)
    {
        $ticket_merge_id = 1999;
        $checkTotal = 0;
        $closing_pair = TicketClosing::with("schedule")->where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $ticket_merge_id])->get();
        $data = (object)[];
        
        $data->schedule_start = Ticket::withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[0]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[0]->id)->with('terminal:id,name','bus:id,bus_number')->get()->groupBy(['terminal_id']);

        $data->schedule_return = Ticket:: withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[1]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[1]->id)->with('terminal:id,name','bus:id,bus_number')->get()->groupBy(['terminal_id']);
    
        $data->expense = TicketMergeExpense::where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $ticket_merge_id])
        ->with("expense_category:id,name","merge.bus:id,bus_number","merge:id,bus_id")->get();

        // refund amount
        $cancelTicket = Ticket::
            onlyTrashed()
            ->where([
                'company_id' => Auth::user()->company_id,
                'ticket_merge_id' => $ticket_merge_id,
                'type' => "canceled",
            ])
            ->with("cancel_ticket:id,ticket_id,percentage","terminal:id,name")
            ->get(["id","seat_fare","discount","terminal_id"])->groupBy("terminal_id");

        $refundTerminal = [];
        $cancelTicket->map(function($single) use (&$refundTerminal){
            
            $refundAmount = 0;
            $single->map(function($ticket) use (&$refundAmount){
            
                if($ticket->cancel_ticket)
                {
                    $refundAmount += (($ticket->seat_fare - $ticket->discount) / 100) * $ticket->cancel_ticket->percentage;
                }
            });
            $singleTerminal = [];
            $singleTerminal["terminal"] = $single[0]->terminal->name;
            $singleTerminal["amount"] = $refundAmount;

            $refundTerminal[] = $singleTerminal;
        });
        // departure
        foreach($data->schedule_start as $item)
        { 
            // sales ledgers opening
            $startLedgers = $this->getSaleLedger($item);
            if($item[0]->commission)
            {
                if($item[0]->commission->flat_commission == 0)
                {
                    $startCommission = (($item->sum("seat_fare") - ($item->sum("discount")))/100)*$item[0]->commission->percentage_commission;
                }
                else
                {
                    $startCommission = $item->count() * $item[0]->commission->flat_commission;
                } 
                $startAdjustCommission = (($item->sum("seat_fare") - $item->sum("discount"))/100)*$item[0]->commission->adjustment_commission;
                $startFixCommission = $item[0]->commission->fix_commission;    
            }
            else
            {  
                $startCommission = 0 ;
                $startAdjustCommission = 0;
                $startFixCommission = 0;
                   
            }   
            $startElt = 0;
            foreach($item as $ticket)
            {
                if($ticket->elt)
                {
                    $startElt += $ticket->elt->elt_price;
                }
            }
            // sale
            $checkTotal += ($item->sum('seat_fare') - $item->sum('discount')) - $startCommission - $startAdjustCommission - $startFixCommission;
            $checkTotal += $startElt;
        }
        // arrival
        foreach($data->schedule_return as $item)
        {
            // sales ledgers opening
            $endLedgers = $this->getSaleLedger($item);
            if($item[0]->commission)
            {
                if($item[0]->commission->flat_commission == 0)
                {
                    $returnCommission = (($item->sum("seat_fare") - ($item->sum("discount")))/100)*$item[0]->commission->percentage_commission;
                
                }
                else
                {
                    $returnCommission = $item->count() * $item[0]->commission->flat_commission;
                }
                $returnAdjustCommission = (($item->sum("seat_fare") - $item->sum("discount"))/100)*$item[0]->commission->adjustment_commission;
                $returnFixCommission = $item[0]->commission->fix_commission; 
            }       
            else
            {  
                $returnCommission = 0 ;
                $returnAdjustCommission = 0;
                $returnFixCommission = 0;
            }

            $returnElt = 0;
            foreach($item as $ticket)
            {
                if($ticket->elt)
                {
                    $returnElt += $ticket->elt->elt_price;
                }
            }
                    
            $checkTotal += ($item->sum('seat_fare') - $item->sum('discount')) - $returnCommission - $returnAdjustCommission - $returnFixCommission;
            $checkTotal += $returnElt;
        }
        // expense
        foreach($data->expense as $item)
        {
            // expense ledgers opening
            $expenseLedgers = $this->getExpenseLedger($item);
            $item->expense_category->name;
            $checkTotal -= $item->amount;
        }

        $checkTotal += array_sum(array_column($refundTerminal, 'amount'));
        return $data; 
    }

    // tier 4 creation
    function accountGroupFourthCreate($name, $second, $third,) {

        $existGroup = AccountGroup::where(["name"=>$name,"account_id"=>$second,"parent_id"=>$third])->first();
        if($existGroup)
        {
            return $existGroup;
        }
        // if not then create new one
        $code = AccountGroup::latest('id')->where('parent_id', $third )->limit(1)->value('code') + 1;
        $code = str_pad($code, 3, '0', STR_PAD_LEFT);

        $group = AccountGroup::create([
            'name'       => strtoupper($name),
            'code'       => $code,
            'account_id' =>  $second,
            'parent_id'  => $third,
            'company_id'  => 0,
            'added_by'         => Auth::user()->id,
            'company_id'         => Auth::user()->company_id,
        ]);

        return $group;
    }
    // tier 5 creation
    function accountHeadCreate($name, $first, $second, $third, $fourth) 
    {
        $existHead = AccountHead::where(["name"=>$name,"parent_account_id"=>$first,"account_id"=>$second,"parent_group_id"=>$third,"group_id"=>$fourth])->first();
        if($existHead)
        {
            return $existHead;
        }

        $code = AccountHead::latest('id')->where('group_id', $fourth )->limit(1)->value('code') + 1;
        $code = str_pad($code, 4, '0', STR_PAD_LEFT);

        $head = AccountHead::create([
            'name' => strtoupper($name),
            'code' => $code,
            'parent_account_id' => $first,
            'account_id' => $second,
            'parent_group_id' => $third,
            'group_id' => $fourth,
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);

        return $head;
    }
    // ledger opening
    function getSaleLedger($item) 
    {
        // sale side terminal group at level four with terminal name
        $terminalSaleGroup = $this->accountGroupFourthCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id." |SALE GROUP",
            13, // SERVICE REVENUE, SALES
            56, // STATION WISE REVENUE
        );
        // sale side terminal ledger refund charges ledger
        $refundChargesHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-REFUND CHARGES |SALE LEDGER',
            4, // REVENUE
            13, // SERVICE REVENUE, SALES
            54, // STATION WISE REVENUE
            $terminalSaleGroup->id, // NOW CREATED TERMINAL SALE GROUP ID
        );
        // sale side terminal ledger sale ledger
        $terminalSaleHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-SALE |SALE LEDGER',
            4, // REVENUE
            13, // SERVICE REVENUE, SALES
            54, // STATION WISE REVENUE
            $terminalSaleGroup->id, // NOW CREATED TERMINAL SALE GROUP ID
        );
        // sale side terminal ledger elt ledger
        $terminalSaleHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-ELT |SALE LEDGER',
            4, // REVENUE
            13, // SERVICE REVENUE, SALES
            54, // STATION WISE REVENUE
            $terminalSaleGroup->id, // NOW CREATED TERMINAL SALE GROUP ID
        );


        //  expense side terminal group at level four with terminal name
        $terminalExpenseGroup = $this->accountGroupFourthCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id." |EXPENSE GROUP",
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
        );
        // expense side terminal child ledger at level five like termianl commissions, terminal discount etc
        $fixedComHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-FIXED COMMISSION |EXPENSE LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $terminalExpenseGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
        );
        $perTicketComHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-PER TICKET COMMISSION |EXPENSE LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $terminalExpenseGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
        );
        $adjustmentComHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-ADJUSTMENT COMMISSION |EXPENSE LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $terminalExpenseGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
        );
        $terminalDiscHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-TERMINAL DISCOUNT |EXPENSE LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $terminalExpenseGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
        );
        $scheduleDiscHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-SCHEDULE DISCOUNT |EXPENSE LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $terminalExpenseGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
        );
        $manualDiscHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-MANUAL DISCOUNT |EXPENSE LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $terminalExpenseGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
        );
                    

        // sale side bus group at level four with bus number
        $busSaleGroup = $this->accountGroupFourthCreate(
           $item[0]->bus->bus_number.'-'.$item[0]->bus->id." |SALE GROUP",
            13, // SERVICE REVENUE, SALES
            56, // STATION WISE REVENUE
        );
        // sale side bus ledger at level five with bus number
        $busSaleHead = $this->accountHeadCreate(
            $item[0]->bus->bus_number.'-'.$item[0]->bus->id.' |SALE LEDGER',
            4, // REVENUE
            13, // SERVICE REVENUE, SALES
            56, // STATION WISE REVENUE
            $busSaleGroup->id, // NOW CREATED BUS SALE GROUP ID
        );

        // cash side bus group at level four with bus number
        $busCashGroup = $this->accountGroupFourthCreate(
            $item[0]->bus->bus_number.'-'.$item[0]->bus->id." |CASH GROUP",
            6, // CURRENT ASSETS
            29, // CASH AND BANK BALANCES
         );
        // cash side bus ledger at level five with bus number
        $busCashHead = $this->accountHeadCreate(
            $item[0]->bus->bus_number.'-'.$item[0]->bus->id.' |CASH LEDGER',
            1, // ASSETS
            6, // CURRENT ASSETS
            29, // CASH AND BANK BALANCES
            $busCashGroup->id, // NOW CREATED BUS CASH GROUP ID
        );

        return [
            "terminalSaleGroup" => $terminalSaleGroup,
            "refundChargesHead" => $refundChargesHead,
            "terminalSaleHead" => $terminalSaleHead,
            "terminalExpenseGroup" => $terminalExpenseGroup,
            "fixedComHead" => $fixedComHead,
            "perTicketComHead" => $perTicketComHead,
            "adjustmentComHead" => $adjustmentComHead,
            "terminalDiscHead" => $terminalDiscHead,
            "scheduleDiscHead" => $scheduleDiscHead,
            "manualDiscHead" => $manualDiscHead,
            "busSaleGroup" => $busSaleGroup,
            "busSaleHead" => $busSaleHead,
            "busCashGroup" => $busCashGroup,
            "busCashHead" => $busCashHead,
        ];
    }
    function getExpenseLedger($item) 
    {
        //  expense side bus group at level four with bus number
        $busExpenseGroup = $this->accountGroupFourthCreate(
            $item->merge->bus->bus_number.'-'.$item->merge->bus->id.' |EXPENSE GROUP',
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
        );
        // expense side ledger at level five with expense category name
        $expenseHead = $this->accountHeadCreate(
            $item->expense_category->name.'-'.$item->expense_category->id.' |EXPENSE LEDGER',
            5, // EXPENSES
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $busExpenseGroup->id, // NOW CREATED BUS EXPENSE GROUP ID
        );

        return $expenseHead;
    }
}
