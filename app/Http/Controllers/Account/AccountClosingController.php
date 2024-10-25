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
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[0]->id)->with('terminal:id,name', 'schedule:id,discount_id')->get()->groupBy(['terminal_id']);

        $data->schedule_return = Ticket::withTrashed()
            ->where(function ($query) {
                $query->where("type", "booked")
                      ->orWhere("type", "over-issue");
                })
            ->with("elt")->with(["commission"=>function($q) use ($closing_pair){
            $q->where("route_id",$closing_pair[1]->schedule->route_id);
        }])->where(["company_id" => Auth::user()->company_id])->where("ticket_closing_id", $closing_pair[1]->id)->with('terminal:id,name', 'schedule:id,discount_id')->get()->groupBy(['terminal_id']);
    
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
        $handCashHead = AccountHead::where(["company_id"=>Auth::user()->company_id,"id"=>1])->first();
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
            
            // ticket price to terminal sale head
            $this->updateSaleTransaction(
                $startLedgers->terminalSaleHead, // head
                $startLedgers->busSaleHead->id,//other head id
                0, //credit
                $item->sum('seat_fare') + $item->sum('schedule_discount') + $item->sum('terminal_discount'), //debit
                $document_id, //document id
                "Schedule Departure Ticket Amount to ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                $ticket_merge_id //posting id
            );
            $startSaleAmount += $item->sum('seat_fare') + $item->sum('schedule_discount') + $item->sum('terminal_discount');
            // ticket price from terminal sale head to driver
            if($item[0]->online_terminal != 1)
            {
                $this->updateSaleTransaction(
                    $startLedgers->terminalSaleHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    $item->sum('seat_fare') - $item->sum('discount') - round($startCommission) - round($startFixCommission), //credit
                    0, //debit
                    ($document_id + 2), //document id
                    "Schedule Departure Ticket Amount From ".$item[0]->terminal->name." To Driver Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startDriverAmount += $item->sum('seat_fare') - $item->sum('discount') - round($startCommission) - round($startFixCommission);
            }
            if($startElt > 0)
            {
                // ticket elt to terminal elt head
                $this->updateSaleTransaction(
                    $startLedgers->terminalEltHead, // head
                    $startLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $startElt, //debit
                    $document_id, //document id
                    "Schedule Departure Ticket Elt Amount to ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startSaleAmount += $startElt;
                // ticket elt from terminal elt head to driver
                $this->updateSaleTransaction(
                    $startLedgers->terminalEltHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    $startElt, //credit
                    0, //debit
                    ($document_id + 2), //document id
                    "Schedule Departure Ticket Elt Amount from ".$item[0]->terminal->name." To Driver Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startDriverAmount += $startElt;
            }
            $startRefund = $startRefundTerminal->where("id",$item[0]->terminal->id)->first();
            if($startRefund && $startRefund->amount > 0)
            {
                // ticket refund charges to terminal refund head
                $this->updateSaleTransaction(
                    $startLedgers->refundChargesHead, // head
                    $startLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $startRefund->amount, //debit
                    $document_id, //document id
                    "Schedule Departure Ticket Cancellation Charges to ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startSaleAmount += $startRefund->amount;
                // ticket refund charges from terminal refund head to driver
                $this->updateSaleTransaction(
                    $startLedgers->refundChargesHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    $startRefund->amount, //credit
                    0, //debit
                    ($document_id + 2), //document id
                    "Schedule Departure Ticket Cancellation Charges From ".$item[0]->terminal->name." To Driver Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startDriverAmount += $startRefund->amount;
            }
            if($item->sum('discount') > 0)
            {
                // ticket simple discount to terminal discount head
                $this->updateSaleTransaction(
                    $startLedgers->manualDiscHead, // head
                    $startLedgers->terminalSaleHead->id,//other head id
                    0, //credit
                    $item->sum('discount'), //debit
                    $document_id, //document id
                    "Schedule Departure Discount To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket simple discount from terminal sale head to discount head
                $this->updateSaleTransaction(
                    $startLedgers->terminalSaleHead, // head
                    $startLedgers->manualDiscHead->id,//other head id
                    $item->sum('discount'), //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Discount From ".$item[0]->terminal->name." To Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
            if($item->sum('schedule_discount') > 0)
            {
                // ticket schedule discount to terminal discount head
                $this->updateSaleTransaction(
                    $startLedgers->scheduleDiscHead, // head
                    $startLedgers->terminalSaleHead->id,//other head id
                    0, //credit
                    $item->sum('schedule_discount'), //debit
                    $document_id, //document id
                    "Schedule Departure Schedule Discount To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket schedule discount from terminal sale head to discount head
                $this->updateSaleTransaction(
                    $startLedgers->terminalSaleHead, // head
                    $startLedgers->scheduleDiscHead->id,//other head id
                    $item->sum('schedule_discount'), //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Schedule Discount From ".$item[0]->terminal->name." To Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
            if($item->sum('terminal_discount') > 0)
            {
                // ticket terminal discount to terminal discount head
                $this->updateSaleTransaction(
                    $startLedgers->terminalDiscHead, // head
                    $startLedgers->terminalSaleHead->id,//other head id
                    0, //credit
                    $item->sum('terminal_discount'), //debit
                    $document_id, //document id
                    "Schedule Departure Terminal Discount To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket terminal discount from terminal sale head to discount head
                $this->updateSaleTransaction(
                    $startLedgers->terminalSaleHead, // head
                    $startLedgers->terminalDiscHead->id,//other head id
                    $item->sum('terminal_discount'), //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Terminal Discount From ".$item[0]->terminal->name." To Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
            if($startCommission > 0)
            {
                // per ticket commission to expense
                $this->updateSaleTransaction(
                    $startLedgers->perTicketComHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    0, //credit
                    round($startCommission), //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Per Ticket Commission To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // per ticket commission from terminal
                $this->updateSaleTransaction(
                    $startLedgers->terminalSaleHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    round($startCommission), //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Per ticket Commission Of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startTotalCommission += round($startCommission);
            }
            if($startFixCommission > 0)
            {
                // terminal fixed commission to expense
                $this->updateSaleTransaction(
                    $startLedgers->fixedComHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    0, //credit
                    round($startFixCommission), //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Fixed Commission To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // terminal fixed commission from terminal
                $this->updateSaleTransaction(
                    $startLedgers->terminalSaleHead, // head
                    $startLedgers->busCashHead->id,//other head id
                    round($startFixCommission), //credit
                    0, //debit
                    ($document_id + 1), //document id
                    "Schedule Departure Fixed Commission of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $startTotalCommission += $startFixCommission;
            }
            if($startAdjustCommission > 0)
            {
                // adjustment commission from cash in hand to kt company
                $this->updateSaleTransaction(
                    $handCashHead, // head
                    $startLedgers->adjustmentComHead->id,//other head id
                    $startAdjustCommission, //credit
                    0, //debit
                    ($document_id + 8), //document id
                    "Schedule Departure Adjustment Commission of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // adjustment commission from cash in hand to kt company terminal wise 
                $this->updateSaleTransaction(
                    $startLedgers->adjustmentComHead, // head
                    $handCashHead->id,//other head id
                    0, //credit
                    $startAdjustCommission, //debit
                    ($document_id + 8), //document id
                    "Schedule Departure Adjustment Commission of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
        }
        // total sale sum
        $this->updateSaleTransaction(
            $startLedgers->busSaleHead, // head
            $startLedgers->terminalSaleHead->id,//other head id
            $startSaleAmount, //credit
            0, //debit
            $document_id, //document id
            "Schedule Departure Total Sale Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
        // total driver amount
        $this->updateSaleTransaction(
            $startLedgers->busCashHead, // head
            $startLedgers->terminalSaleHead->id,//other head id
            0, //credit
            $startDriverAmount, //debit
            ($document_id + 2), //document id
            "Schedule Departure Total Amount Against Merge-$ticket_merge_id",
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
            // ticket price to terminal sale head
            $this->updateSaleTransaction(
                $endLedgers->terminalSaleHead, // head
                $endLedgers->busSaleHead->id,//other head id
                0, //credit
                $item->sum('seat_fare') + $item->sum('schedule_discount') + $item->sum('terminal_discount'), //debit
                ($document_id + 3), //document id
                "Schedule Return Ticket Amount to ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                $ticket_merge_id //posting id
            );
            $returnSaleAmount += $item->sum('seat_fare') + $item->sum('schedule_discount') + $item->sum('terminal_discount');
            // ticket price from terminal sale head to driver
            if($item[0]->online_terminal != 1)
            {
                $this->updateSaleTransaction(
                    $endLedgers->terminalSaleHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    $item->sum('seat_fare') - $item->sum('discount') - round($returnCommission) - round($returnFixCommission), //credit
                    0, //debit
                    ($document_id + 5), //document id
                    "Schedule Return Ticket Amount From ".$item[0]->terminal->name." To Driver Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnDriverAmount += $item->sum('seat_fare') - $item->sum('discount') - round($returnCommission) - round($returnFixCommission);
            }

            if($returnElt > 0)
            {
                // ticket elt to terminal elt head
                $this->updateSaleTransaction(
                    $endLedgers->terminalEltHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $returnElt, //debit
                    ($document_id + 3), //document id
                    "Schedule Return Ticket Elt Amount to ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnSaleAmount += $returnElt;
                // ticket elt from terminal elt head to driver
                $this->updateSaleTransaction(
                    $endLedgers->terminalEltHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    $returnElt, //credit
                    0, //debit
                    ($document_id + 5), //document id
                    "Schedule Return Ticket Elt Amount from ".$item[0]->terminal->name." To Driver Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnDriverAmount += $returnElt;
            }
            $returnRefund = $returnRefundTerminal->where("id",$item[0]->terminal->id)->first();
            if($returnRefund && $returnRefund->amount > 0)
            {
                // ticket refund charges to terminal refund head
                $this->updateSaleTransaction(
                    $endLedgers->refundChargesHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $returnRefund->amount, //debit
                    ($document_id + 3), //document id
                    "Schedule Departure Ticket Cancellation Charges to ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnSaleAmount += $returnRefund->amount;
                // ticket refund charges from terminal refund head to driver
                $this->updateSaleTransaction(
                    $endLedgers->refundChargesHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    $returnRefund->amount, //credit
                    0, //debit
                    ($document_id + 5), //document id
                    "Schedule Return Ticket Cancellation Charges From ".$item[0]->terminal->name." To Driver Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnDriverAmount += $returnRefund->amount;
            }
            if($item->sum('discount') > 0)
            {
                // ticket simple discount to terminal discount head
                $this->updateSaleTransaction(
                    $endLedgers->manualDiscHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    0, //credit
                    $item->sum('discount'), //debit
                    ($document_id + 3), //document id
                    "Schedule Return Discount To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket simple discount from terminal sale head to discount head
                $this->updateSaleTransaction(
                    $endLedgers->terminalSaleHead, // head
                    $endLedgers->busSaleHead->id,//other head id
                    $item->sum('discount'), //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Return Discount From ".$item[0]->terminal->name." To Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
            if($item->sum('schedule_discount') > 0)
            {
                // ticket schedule discount to terminal discount head
                $this->updateSaleTransaction(
                    $endLedgers->scheduleDiscHead, // head
                    $endLedgers->terminalSaleHead->id,//other head id
                    0, //credit
                    $item->sum('schedule_discount'), //debit
                    ($document_id + 3), //document id
                    "Schedule Return Schedule Discount To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket schedule discount from terminal sale head to discount head
                $this->updateSaleTransaction(
                    $endLedgers->terminalSaleHead, // head
                    $endLedgers->scheduleDiscHead->id,//other head id
                    $item->sum('schedule_discount'), //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Return Schedule Discount From ".$item[0]->terminal->name." To Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
            if($item->sum('terminal_discount') > 0)
            {
                // ticket schedule discount to terminal discount head
                $this->updateSaleTransaction(
                    $endLedgers->terminalDiscHead, // head
                    $endLedgers->terminalSaleHead->id,//other head id
                    0, //credit
                    $item->sum('terminal_discount'), //debit
                    ($document_id + 3), //document id
                    "Schedule Return Terminal Discount To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket schedule discount from terminal sale head to discount head
                $this->updateSaleTransaction(
                    $endLedgers->terminalSaleHead, // head
                    $endLedgers->terminalDiscHead->id,//other head id
                    $item->sum('terminal_discount'), //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Return Terminal Discount From ".$item[0]->terminal->name." To Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
            if($returnCommission > 0)
            {
                // per ticket commission to expense
                $this->updateSaleTransaction(
                    $endLedgers->perTicketComHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    0, //credit
                    round($returnCommission), //debit
                    ($document_id + 4), //document id
                    "Schedule Departure Per Ticket Commission To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // per ticket commission from terminal
                $this->updateSaleTransaction(
                    $endLedgers->terminalSaleHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    round($returnCommission), //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Return Per ticket Commission Of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnTotalCommission += round($returnCommission);
            }
            if($returnFixCommission > 0)
            {
                // terminal fixed commission to expense
                $this->updateSaleTransaction(
                    $endLedgers->fixedComHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    0, //credit
                    round($returnFixCommission), //debit
                    ($document_id + 4), //document id
                    "Schedule Return Fixed Commission To ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // ticket simple discount to terminal
                $this->updateSaleTransaction(
                    $endLedgers->terminalSaleHead, // head
                    $endLedgers->busCashHead->id,//other head id
                    round($returnFixCommission), //credit
                    0, //debit
                    ($document_id + 4), //document id
                    "Schedule Return Fixed Commission of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                $returnTotalCommission += round($returnFixCommission);
            }
            if($returnAdjustCommission > 0)
            {
                // adjustment commission from cash in hand to kt company
                $this->updateSaleTransaction(
                    $handCashHead, // head
                    $endLedgers->adjustmentComHead->id,//other head id
                    $returnAdjustCommission, //credit
                    0, //debit
                    ($document_id + 8), //document id
                    "Schedule Return Adjustment Commission of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // adjustment commission from cash in hand to kt company terminal wise
                $this->updateSaleTransaction(
                    $endLedgers->adjustmentComHead, // head
                    $handCashHead->id,//other head id
                    0, //credit
                    $returnAdjustCommission, //debit
                    ($document_id + 8), //document id
                    "Schedule Return Adjustment Commission of ".$item[0]->terminal->name." Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
            }
        }
        // total sale sum
        $this->updateSaleTransaction(
            $endLedgers->busSaleHead, // head
            $endLedgers->terminalSaleHead->id,//other head id
            $returnSaleAmount, //credit
            0, //debit
            ($document_id + 3), //document id
            "Schedule Return Total Sale Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
        // total driver amount
        $this->updateSaleTransaction(
            $endLedgers->busCashHead, // head
            $endLedgers->terminalSaleHead->id,//other head id
            0, //credit
            $returnDriverAmount, //debit
            ($document_id + 5), //document id
            "Schedule Return Total Amount Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
        // expense
        $expenseTotal = 0;
        foreach($data->expense as $item)
        {
            // expense ledgers opening
            $expenseLedger = $this->getExpenseLedger($item);
            if($item->ledger) // if user want entry should come in ledger so
            {
                $liableLedger = $this->getLiableLedger($item);
                // credit amount to liable ledger
                $this->updateSaleTransaction(
                    $liableLedger, // head
                    $expenseLedger->id,//other head id
                    $item->amount, //credit
                    0, //debit
                    ($document_id + 6), //document id
                    "Schedule Expense Against Merge-$ticket_merge_id",
                    $ticket_merge_id //posting id
                );
                // debit same ledger if we pay something
                if($item->paid > 0)
                {
                    $this->updateSaleTransaction(
                        $liableLedger, // head
                        $endLedgers->busCashHead->id,//other head id
                        0, //credit
                        $item->paid, //debit
                        ($document_id + 6), //document id
                        "Schedule Expense Against Merge-$ticket_merge_id",
                        $ticket_merge_id //posting id
                    );
                }
                $expenseTotal -= ($item->amount - $item->paid) ;
            }
          
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
       
        // driver cash
        $this->updateSaleTransaction(
            $endLedgers->busCashHead, // head
            $handCashHead->id,//cash in hand ledger
            $driverCredit > $driverDebit ? 0 : ($driverDebit - $driverCredit) , //credit
            $driverCredit > $driverDebit ? ($driverCredit - $driverDebit) : 0, //debit
            ($document_id + 7), //document id
            "Bus Cash Ledger Against Merge-$ticket_merge_id",
            $ticket_merge_id //posting id
        );
        // cash in hand
        $this->updateSaleTransaction(
            $handCashHead, // head
            $endLedgers->busCashHead->id,//cash in hand ledger
            $driverCredit > $driverDebit ? ($driverCredit - $driverDebit) : 0 , //credit
            $driverCredit > $driverDebit ? 0 : ($driverDebit - $driverCredit), //debit
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
        $merge = TicketClosingMerge::find($item[0]->ticket_merge_id);
        $bus = Bus::find($merge->bus_id);
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
           $bus->bus_number.'-'.$bus->id." |SALE GROUP",
            13, // SERVICE REVENUE, SALES
            56, // STATION WISE REVENUE
        );
        // sale side bus ledger at level five with bus number
        $busSaleHead = $this->accountHeadCreate(
            $bus->bus_number.'-'.$bus->id.' |SALE LEDGER',
            4, // REVENUE
            13, // SERVICE REVENUE, SALES
            56, // STATION WISE REVENUE
            $busSaleGroup->id, // NOW CREATED BUS SALE GROUP ID
        );

        // cash side bus group at level four with bus number
        $busCashGroup = $this->accountGroupFourthCreate(
            $bus->bus_number.'-'.$bus->id." |CASH GROUP",
            6, // CURRENT ASSETS
            29, // CASH AND BANK BALANCES
         );
        // cash side bus ledger at level five with bus number
        $busCashHead = $this->accountHeadCreate(
            $bus->bus_number.'-'.$bus->id.' |CASH LEDGER',
            1, // ASSETS
            6, // CURRENT ASSETS
            29, // CASH AND BANK BALANCES
            $busCashGroup->id, // NOW CREATED BUS CASH GROUP ID
        );

        //  cash group at level four with kt commission
        $commissionCashGroup = $this->accountGroupFourthCreate(
            "Adjustment Commission |CASH GROUP",
            6, // CURRENT ASSETS
            29, // CASH AND BANK BALANCES
        );
        // terminal wise kt commission ledger at level five
        $adjustmentComHead = $this->accountHeadCreate(
            $item[0]->terminal->name.'-'.$item[0]->terminal->id.'-ADJUSTMENT COMMISSION |CASH LEDGER',
            4, // REVENUE
            15, // OPERATING EXPENSES
            11, // VEHICLE SERVICE EXPENSE
            $commissionCashGroup->id, // NOW CREATED TERMINAL EXPENSE GROUP ID
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
    function getLiableLedger($item) 
    {
        // liable side ledger at level five with expense category name
        $expenseHead = $this->accountHeadCreate(
            $item->expense_category->name.'-'.$item->expense_category->id.' |LIABLE LEDGER',
            2, // LIABILITIES
            8, // CURRENT LIABILITIES
            58, // ACCOUNT PAYABLE
            59, // Service/Vendor Payable
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
