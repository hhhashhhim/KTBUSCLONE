<?php

namespace App\Http\Controllers\Account;

use App\Models\Account\Account;
use App\Models\Account\AccountGroup;
use App\Models\Account\AccountHead;
use App\Models\Account\Bank;
use App\Models\Schedule\Schedule;
use App\Models\Discount\Discount;
use App\Models\FareTable;
use App\Models\TerminalDiscount;
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
use App\Models\Account\AccountTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class AccountClosingController extends BaseController
{

    public function accountClosingUpdate(Request $request)
    {
        $ticket_merge_id = $request->ticket_merge_id;
        $closing_pair = TicketClosing::with("schedule")->where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $ticket_merge_id])->get();
        $data = (object)[];
        
        $data->schedule_start = Ticket::withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[0]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[0]->id)->with('terminal:id,name','bus:id,bus_number', 'schedule:id,discount_id')->get()->groupBy(['terminal_id']);

        $data->schedule_return = Ticket:: withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[1]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[1]->id)->with('terminal:id,name','bus:id,bus_number', 'schedule:id,discount_id')->get()->groupBy(['terminal_id']);
    
        $data->expense = TicketMergeExpense::where(["company_id" => Auth::user()->company_id, "ticket_merge_id" => $ticket_merge_id])
        ->with("expense_category:id,name","merge.bus:id,bus_number","merge:id,bus_id")->get();

        // refund amount
        $startCancelTicket = Ticket::
            onlyTrashed()
            ->where([
                'company_id' => Auth::user()->company_id,
                "ticket_closing_id"=> $closing_pair[0]->id,
                'type' => "canceled",
            ])
            ->with("cancel_ticket:id,ticket_id,percentage","terminal:id,name")
            ->get(["id","seat_fare","discount","terminal_id"])->groupBy("terminal_id");
        $returnCancelTicket = Ticket::
            onlyTrashed()
            ->where([
                'company_id' => Auth::user()->company_id,
                "ticket_closing_id"=> $closing_pair[1]->id,
                'type' => "canceled",
            ])
            ->with("cancel_ticket:id,ticket_id,percentage","terminal:id,name")
            ->get(["id","seat_fare","discount","terminal_id"])->groupBy("terminal_id");

        // departure
        $startRefundTerminal = [];
        $startCancelTicket->map(function($single) use (&$startRefundTerminal){
            
            $refundAmount = 0;
            $single->map(function($ticket) use (&$refundAmount){
            
                if($ticket->cancel_ticket)
                {
                    $refundAmount += (($ticket->seat_fare - $ticket->discount) / 100) * $ticket->cancel_ticket->percentage;
                }
            });
            $singleTerminal = [];
            $singleTerminal["id"] = $single[0]->terminal->id;
            $singleTerminal["terminal"] = $single[0]->terminal->name;
            $singleTerminal["amount"] = $refundAmount;

            $startRefundTerminal[] = (object)$singleTerminal;
        });
        $startRefundTerminal = collect($startRefundTerminal);
        
        // return
        $returnRefundTerminal = [];
        $returnCancelTicket->map(function($single) use (&$returnRefundTerminal){
            
            $refundAmount = 0;
            $single->map(function($ticket) use (&$refundAmount){
            
                if($ticket->cancel_ticket)
                {
                    $refundAmount += (($ticket->seat_fare - $ticket->discount) / 100) * $ticket->cancel_ticket->percentage;
                }
            });
            $singleTerminal = [];
            $singleTerminal["id"] = $single[0]->terminal->id;
            $singleTerminal["terminal"] = $single[0]->terminal->name;
            $singleTerminal["amount"] = $refundAmount;

            $returnRefundTerminal[] = (object)$singleTerminal;
        });
        $returnRefundTerminal = collect($returnRefundTerminal);
        // here i know in this closing 8 jv created so i will run this decsending order
        $document = AccountTransaction::where(["company_id"=>Auth::user()->company_id])
        ->where("type","JV")
        ->orderBy("document_id","DESC")
        ->first();
        $document_id = $document ? $document->document_id + 1 : 1;
        
        // departure
        $startSaleAmount = 0;
        $startDriverAmount = 0;
        $startTotalCommission = 0;
        foreach($data->schedule_start as $item)
        { 
            // sales ledgers opening
            $startLedgers = (object)$this->getSaleLedger($item);
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
            // $sale = ($item->sum('seat_fare') - $item->sum('discount') - $item->sum('discount')) + $startElt - $startCommission - $startAdjustCommission - $startFixCommission;
            
            // ticket price to terminal
            $this->updateSaleTransaction(
                $startLedgers->terminalSaleHead, // head
                $startLedgers->busSaleHead->id,//other head id
                0, //credit
                $item->sum('seat_fare') - $item->sum('discount'), //debit
                $document_id, //document id
                "Schedule Departure Ticket Amount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                $ticket_merge_id //posting id
            );
            $startSaleAmount += $item->sum('seat_fare') - $item->sum('discount');
            // ticket price from terminal
            $this->updateSaleTransaction(
                $startLedgers->terminalSaleHead, // head
                $startLedgers->busCashHead->id,//other head id
                $item->sum('seat_fare') - $item->sum('discount'), //credit
                0, //debit
                ($document_id + 1), //document id
                "Schedule Departure Ticket Amount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                $ticket_merge_id //posting id
            );
            $startDriverAmount += $item->sum('seat_fare') - $item->sum('discount');
            if($startElt > 0)
            {
                // ticket elt to terminal
                $this->updateSaleTransaction(
                    $startLedgers->terminalEltHead, // head
                    $startLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $startElt, //debit
                    $document_id, //document id
                    "Schedule Departure Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startSaleAmount += $startElt;
                // ticket elt from terminal
                $this->updateSaleTransaction(
                    $startLedgers->terminalEltHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    $startElt, //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startDriverAmount += $startElt;
            }
            $startRefund = $startRefundTerminal->where("id",$item[0]->terminal->id)->first();
            if($startRefund && $startRefund->amount > 0)
            {
                // ticket refund charges to terminal
                $this->updateSaleTransaction(
                    $startLedgers->refundChargesHead, // head
                    $startLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $startRefund->amount, //debit
                    $document_id, //document id
                    "Schedule Departure Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startSaleAmount += $startRefund->amount;
                // ticket refund charges from terminal
                $this->updateSaleTransaction(
                    $startLedgers->refundChargesHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    $startElt, //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startDriverAmount += $startRefund->amount;
            }
            if($item->sum('discount') > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $startLedgers->manualDiscHead, // head
                    $startLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $item->sum('discount'), //debit
                    $document_id, //document id
                    "Schedule Departure Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startSaleAmount += $item->sum('discount');
            }
            if($startCommission > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $startLedgers->perTicketComHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    0, //credit
                    $startCommission, //debit
                    ($document_id + 2), //document id
                    "Schedule Departure Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startTotalCommission += $startCommission;
            }
            if($startFixCommission > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $startLedgers->fixedComHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    0, //credit
                    $startFixCommission, //debit
                    ($document_id + 2), //document id
                    "Schedule Departure Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startTotalCommission += $startFixCommission;
            }
            if($startAdjustCommission > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $startLedgers->adjustmentComHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    0, //credit
                    $startAdjustCommission, //debit
                    ($document_id + 2), //document id
                    "Schedule Departure Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $startTotalCommission += $startAdjustCommission;
            }
        }
        // total sale sum
        $this->updateSaleTransaction(
            $startLedgers->busSaleHead, // head
            $startLedgers->terminalSaleHead->id,//other head id
            $startSaleAmount, //credit
            0, //debit
            $document_id, //document id
            "Schedule Departure Total Amount Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
            $ticket_merge_id //posting id
        );
        // total driver amount
        $this->updateSaleTransaction(
            $startLedgers->busCashHead, // head
            $startLedgers->terminalSaleHead->id,//other head id
            0, //credit
            $startDriverAmount, //debit
            ($document_id + 1), //document id
            "Schedule Departure Total Amount Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
            $ticket_merge_id //posting id
        );
        // total commission amount
        $this->updateSaleTransaction(
            $startLedgers->busCashHead, // head
            $startLedgers->adjustmentComHead->id,//other head id
            $startTotalCommission, //credit
            0, //debit
            ($document_id + 2), //document id
            "Schedule Departure Total Amount Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
            $ticket_merge_id //posting id
        );
         // arrival
         $returnSaleAmount = 0;
         $returnDriverAmount = 0;
         $returnTotalCommission = 0;
        foreach($data->schedule_return as $item)
        {
            // sales ledgers opening
            $endLedgers = (object)$this->getSaleLedger($item);
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
            // ticket price to terminal
            $this->updateSaleTransaction(
                $endLedgers->terminalSaleHead, // head
                $endLedgers->busSaleHead->id,//other head id
                0, //credit
                $item->sum('seat_fare') - $item->sum('discount'), //debit
                ($document_id + 3), //document id
                "Schedule Return Ticket Amount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                $ticket_merge_id //posting id
            );
            $returnSaleAmount += $item->sum('seat_fare') - $item->sum('discount');
            // ticket price from terminal
            $this->updateSaleTransaction(
                $endLedgers->terminalSaleHead, // head
                $endLedgers->busCashHead->id,//other head id
                $item->sum('seat_fare') - $item->sum('discount'), //credit
                0, //debit
                ($document_id + 4), //document id
                "Schedule Return Ticket Amount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                $ticket_merge_id //posting id
            );
            $returnDriverAmount += $item->sum('seat_fare') - $item->sum('discount');
            if($returnElt > 0)
            {
                // ticket elt to terminal
                $this->updateSaleTransaction(
                    $endLedgers->terminalEltHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $returnElt, //debit
                    ($document_id + 3), //document id
                    "Schedule Return Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnSaleAmount += $returnElt;
                // ticket elt from terminal
                $this->updateSaleTransaction(
                    $endLedgers->terminalEltHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    $returnElt, //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Departure Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnDriverAmount += $returnElt;
            }
            $returnRefund = $returnRefundTerminal->where("id",$item[0]->terminal->id)->first();
            if($returnRefund && $returnRefund->amount > 0)
            {
                // ticket refund charges to terminal
                $this->updateSaleTransaction(
                    $endLedgers->terminalEltHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $returnRefund->amount, //debit
                    ($document_id + 3), //document id
                    "Schedule Return Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnSaleAmount += $returnRefund->amount;
                // ticket refund charges from terminal
                $this->updateSaleTransaction(
                    $endLedgers->terminalEltHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    $returnRefund->amount, //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Departure Ticket Elt of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnDriverAmount += $returnRefund->amount;
            }
            if($item->sum('discount') > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $endLedgers->manualDiscHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $item->sum('discount'), //debit
                    ($document_id + 3), //document id
                    "Schedule Return Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnSaleAmount += $item->sum('discount');
            }
            if($returnCommission > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $endLedgers->perTicketComHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    0, //credit
                    $returnCommission, //debit
                    ($document_id + 5), //document id
                    "Schedule Return Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnTotalCommission += $returnCommission;
            }
            if($returnFixCommission > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $endLedgers->fixedComHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    0, //credit
                    $returnFixCommission, //debit
                    ($document_id + 5), //document id
                    "Schedule Return Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnTotalCommission += $returnFixCommission;
            }
            if($returnAdjustCommission > 0)
            {
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $endLedgers->adjustmentComHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    0, //credit
                    $returnAdjustCommission, //debit
                    ($document_id + 5), //document id
                    "Schedule Return Discount of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
                    $ticket_merge_id //posting id
                );
                $returnTotalCommission += $returnAdjustCommission;
            }
        }
        // total sale sum
        $this->updateSaleTransaction(
            $endLedgers->busSaleHead, // head
            $endLedgers->terminalSaleHead->id,//other head id
            $returnSaleAmount, //credit
            0, //debit
            ($document_id + 3), //document id
            "Schedule Return Total Amount Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
            $ticket_merge_id //posting id
        );
        // total driver amount
        $this->updateSaleTransaction(
            $endLedgers->busCashHead, // head
            $endLedgers->terminalSaleHead->id,//other head id
            0, //credit
            $returnDriverAmount, //debit
            ($document_id + 4), //document id
            "Schedule Return Total Amount Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
            $ticket_merge_id //posting id
        );
        // total commission amount
        $this->updateSaleTransaction(
            $endLedgers->busCashHead, // head
            $endLedgers->adjustmentComHead->id,//other head id
            $returnTotalCommission, //credit
            0, //debit
            ($document_id + 5), //document id
            "Schedule Return Total Amount Against Merge-$ticket_merge_id (Elt, Commissions, Discounts Applied)",
            $ticket_merge_id //posting id
        );
        // expense
        $expenseTotal = 0;
        foreach($data->expense as $item)
        {
            // expense ledgers opening
            $expenseLedger = $this->getExpenseLedger($item);
          
            // expense
            $this->updateSaleTransaction(
                $expenseLedger, // head
                $endLedgers->busCashHead->id,//other head id
                0, //credit
                $item->amount, //debit
                ($document_id + 6), //document id
                "Schedule Expense Against Merge-$ticket_merge_id",
                $ticket_merge_id //posting id
            );
            $expenseTotal += $item->amount;
        }
        // total expense
        $this->updateSaleTransaction(
            $endLedgers->busCashHead, // head
            $expenseLedger->id,//other head id
            $expenseTotal, //credit
            0, //debit
            ($document_id + 6), //document id
            "Schedule Expense Total Amount Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
        $handCashHead = AccountHead::where(["company_id"=>Auth::user()->company_id,"id"=>1])->first();
        $driverDebit = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"posting_id"=>$ticket_merge_id,"account_head_id"=>$endLedgers->busCashHead->id])->sum('debit');
        $driverCredit = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"posting_id"=>$ticket_merge_id,"account_head_id"=>$endLedgers->busCashHead->id])->sum('credit');
        $netDriverCash = $driverCredit - $driverDebit;
        // driver cash
        $this->updateSaleTransaction(
            $endLedgers->busCashHead, // head
            $handCashHead->id,//cash in hand ledger
            $netDriverCash > 0 ? $netDriverCash : 0 , //credit
            $netDriverCash > 0 ? 0 : $netDriverCash, //debit
            ($document_id + 7), //document id
            "Bus Cash Ledger Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
        // cash in hand
        $this->updateSaleTransaction(
            $handCashHead, // head
            $endLedgers->busCashHead->id,//cash in hand ledger
            $netDriverCash > 0 ? 0 : $netDriverCash , //credit
            $netDriverCash > 0 ? $netDriverCash : 0, //debit
            ($document_id + 7), //document id
            "Cash In Hand Ledger Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
       
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
            6, // CURRENT ASSETS
            50, // ACCOUNT RECEIVABLE
        );
        // sale side terminal ledger refund charges ledger
        $refundChargesHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-REFUND CHARGES |SALE LEDGER',
            1, // ASSETS
            6, // CURRENT ASSETS
            50, // ACCOUNT RECEIVABLE
            $terminalSaleGroup->id, // NOW CREATED TERMINAL SALE GROUP ID
        );
        // sale side terminal ledger sale ledger
        $terminalSaleHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-TICKET |SALE LEDGER',
            1, // ASSETS
            6, // CURRENT ASSETS
            50, // ACCOUNT RECEIVABLE
            $terminalSaleGroup->id, // NOW CREATED TERMINAL SALE GROUP ID
        );
        // sale side terminal ledger elt ledger
        $terminalEltHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-ELT |SALE LEDGER',
            1, // ASSETS
            6, // CURRENT ASSETS
            50, // ACCOUNT RECEIVABLE
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
            "terminalEltHead" => $terminalEltHead,
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
    function updateSaleTransaction($head,$other_id,$credit,$debit,$document_id,$narration,$posting_id) 
    {
        AccountTransaction::create([
            'terminal_id' => 1,
            'account_head_id' => $head->id,
            'other_account_head_id' => $other_id,
            'credit' => $credit,
            'debit' => $debit,
            'document_id' => $document_id,
            'type' => "JV",
            'narration' => strtoupper($narration),
            'posting_type' => 'closing',
            'posting_id' => $posting_id,
            'approved' => 1,
            'approved_by' => 0,
            'parent_account_id' => $head->parent_account_id, 
            'account_id' => $head->account_id, 
            'parent_group_id' => $head->parent_group_id, 
            'group_id' => $head->group_id, 
            'added_by' => Auth::user()->id,
            'company_id' => Auth::user()->company_id,
        ]);
    }
}
