<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Bus\Bus;
use App\Models\Route\Route;
use App\Models\Terminal;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\RefundLog;
use Illuminate\Support\Facades\Log;
use Throwable;

class AllBookingController extends Controller
{
    public function routes()
    {
        if (!checkForSubmenu("all-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Route::where(['company_id' => Auth::user()->company_id, "hide" => 0])->get();
    }

    public function terminals()
    {
        if (!checkForSubmenu("all-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Terminal::where(['company_id' => Auth::user()->company_id, "hide" => 0])->get();
    }

    public function buses()
    {
        if (!checkForSubmenu("all-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return Bus::where('company_id', Auth::user()->company_id)->get();
    }

    public function filter(Request $request)
    {
        if (!checkForSubmenu("all-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $data = Ticket::where(["tickets.company_id" => Auth::user()->company_id])

            // Within Customer Table
            ->where("customers.cnic", 'like', '%' . str_replace("-", "", $request->cnicFilter) . '%')
            ->where("customers.contact", 'like', '%' . str_replace("-", "", $request->phoneFilter) . '%')
            ->where("customers.name", 'like', '%' . $request->nameFilter . '%')
            ->join("customers", "customers.id", "tickets.customer_id")

            ->where(function ($q) use ($request) {
                // Within Ticket Table
                if ($request->terminalFilter) {
                    $q->where("terminal_id", $request->terminalFilter);
                }
                if ($request->invoiceFilter) {
                    $q->where("invoice_id", 'like', '%' . $request->invoiceFilter . '%');
                }
                if ($request->busFilter) {
                    $q->where("bus_id", $request->busFilter);
                }
                if ($request->fromDateFilter) {
                    $q->where("date", '>=', $request->fromDateFilter);
                }
                if ($request->toDateFilter) {
                    $q->where("date", '<=', $request->toDateFilter);
                }
                if ($request->routeFilter) {
                    $q->where("route_id", $request->routeFilter);
                }
                if ($request->statusFilter == "reschedule") {
                    $q->where("reschedule_type", '!=', $request->statusFilter);
                } elseif ($request->statusFilter) {
                    $q->where("type", $request->statusFilter);
                }
                return $q;
            });


        // Within Ticket Table
        if ($request->statusFilter == "canceled" || $request->statusFilter == "over-issue") {
            $data->where("type", $request->statusFilter)->withTrashed();
        }

        return [
            "data" => $data->with("schedule:id,route_id", "schedule.route:id,name", "bus:id,bus_number", "terminal:id,name", "addedBy:id,name", "scheduleDetail:id,departure_time", "cancel_ticket:id,ticket_id,added_by,created_at", "cancel_ticket.added_by_name:id,name", "overIssueSeats:id,ticket_id,added_by,created_at", "overIssueSeats.overissue_by:id,name")->select("tickets.*", "customers.name", "customers.cnic", "customers.contact")->get(),
            "total_fare" => $data->with("schedule:id,route_id", "schedule.route:id,name", "bus:id,bus_number", "terminal:id,name", "addedBy:id,name", "scheduleDetail:id,departure_time", "cancel_ticket:id,ticket_id,added_by,created_at", "cancel_ticket.addedBy:id,name")->select("tickets.*", "customers.name", "customers.cnic", "customers.contact")->sum("seat_fare")
        ];
    }
    public function jazzcashfilter(Request $request)
    {
        if (!checkForSubmenu("all-booking")) {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }

        $data = Ticket::where(["tickets.company_id" => Auth::user()->company_id])
            // ✅ Always show only terminal ID = 14
            ->where("tickets.terminal_id", 14)
            ->whereNotNull("tickets.transaction_id")
            // Join customer table
            ->join("customers", "customers.id", "tickets.customer_id")

            // Customer filters
            ->where("customers.cnic", 'like', '%' . str_replace("-", "", $request->cnicFilter) . '%')
            ->where("customers.contact", 'like', '%' . str_replace("-", "", $request->phoneFilter) . '%')
            ->where("customers.name", 'like', '%' . $request->nameFilter . '%')

            ->where(function ($q) use ($request) {
                // Ticket table filters
                if ($request->invoiceFilter) {
                    $q->where("invoice_id", 'like', '%' . $request->invoiceFilter . '%');
                }
                if ($request->busFilter) {
                    $q->where("bus_id", $request->busFilter);
                }
                if ($request->fromDateFilter) {
                    $q->where("date", '>=', $request->fromDateFilter);
                }
                if ($request->toDateFilter) {
                    $q->where("date", '<=', $request->toDateFilter);
                }
                if ($request->routeFilter) {
                    $q->where("route_id", $request->routeFilter);
                }
                if ($request->statusFilter == "reschedule") {
                    $q->where("reschedule_type", '!=', $request->statusFilter);
                } elseif ($request->statusFilter) {
                    $q->where("type", $request->statusFilter);
                }
                return $q;
            });

        // Include canceled or over-issue with trashed
        if ($request->statusFilter == "canceled" || $request->statusFilter == "over-issue") {
            $data->where("type", $request->statusFilter)->withTrashed();
        }

        return [
            "data" => $data->with(
                "schedule:id,route_id",
                "schedule.route:id,name",
                "bus:id,bus_number",
                "terminal:id,name",
                "addedBy:id,name",
                "scheduleDetail:id,departure_time",
                "cancel_ticket:id,ticket_id,added_by,created_at",
                "cancel_ticket.added_by_name:id,name",
                "overIssueSeats:id,ticket_id,added_by,created_at",
                "overIssueSeats.overissue_by:id,name"
            )->select(
                "tickets.*",
                "customers.name",
                "customers.cnic",
                "customers.contact"
            )->get(),

            "total_fare" => $data->sum("seat_fare")
        ];
    }
    public function refund(Request $request)
    {
        $validated = $request->validate([
            'ticket_id'         => 'required|integer|exists:tickets,id',
            'refund_reason'     => 'required|string',
            'refund_percentage' => 'required|numeric|min:1|max:100',
            'refund_amount'     => 'required|numeric|min:1',
        ]);

        $ticket = Ticket::withTrashed()->find($request->ticket_id);

        if (!$ticket || empty($ticket->transaction_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction reference not found for this ticket.',
            ], 404);
        }

        // 🏦 JazzCash credentials
        $merchantID    = '00151726';
        $password      = 'vs8z12syy0';
        $merchantMPIN  = '7863';
        $integritySalt = '8335zz8zuu';
        // 🧾 Refund data (dynamically generated)
        $refundAmount = (float)$request->refund_amount * 100; // Convert to paisa

        $data = [
            'pp_MerchantID'      => $merchantID,
            'pp_Password'        => $password,
            'pp_MerchantMPIN'    => $merchantMPIN,
            'pp_TxnCurrency'     => 'PKR',
            'pp_TxnRefNo'        => $ticket->transaction_id,
            'pp_Amount'          => (string)$refundAmount,
        ];

        // 🔐 Generate Secure Hash
        ksort($data);
        $hashString = $integritySalt;
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $hashString .= '&' . $value;
            }
        }

        $data['pp_SecureHash'] = hash_hmac('sha256', $hashString, $integritySalt);
        return $data;
        // Log::info('🎯 JazzCash Refund Payload', $data);

        // 🌐 Send refund request
        $response = Http::asForm()->post(
            'https://payments.jazzcash.com.pk/ApplicationAPI/API/Purchase/domwalletrefundtransaction',
            $data
        );

        $responseData = $response->json();
        // Log::info('💬 JazzCash Refund Response', $responseData ?? []);

        // 🧠 Handle response
        $ppMessage = $responseData['pp_ResponseMessage'] ?? '';
        $isSuccess = stripos($ppMessage, 'successful') !== false;

        if ($isSuccess) {
            // ✅ Save refund details in the database
            $ticket->update([
                'refund_amount'     => $request->refund_amount,
                'refund_percentage' => $request->refund_percentage,
                'refund_reason'     => $request->refund_reason,
            ]);
        }

        return response()->json([
            'success'  => true,
            'request'  => $data,
            'response' => $responseData,
            'message'  => $isSuccess ? 'Refund Successful' : 'Refund Failed',
        ]);
    }









    private function generateSecureHashRefund($data, $integritySalt)
    {
        ksort($data); // Sort alphabetically by field name
        $hashString = $integritySalt;

        foreach ($data as $key => $value) {
            if (!empty($value) && $key !== 'pp_SecureHash') {
                $hashString .= '&' . $value;
            }
        }

        return strtoupper(hash_hmac('sha256', $hashString, $integritySalt));
    }






    public function jazzcash2()
    {
        return (object) [
            "returnUrl" => 'http://localhost/whatsapp/payment/verification/jazzcash',
            "redirectionUrl" => 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform',
            "walletUrl" => 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/domwallettransaction',
            "statusUrl" => 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/PaymentInquiry/Inquire',
            "salt" => 'e03h2y252v',
            "merchant" => 'MC151337',
            "password" => 'e83xu9d389',
        ];
    }

    public function jazzcashPayment(Request $request)
    {

        $integritySalt = $this->jazzcash2()->salt;
        $txnDateTime = date('YmdHis');
        $txnRefNo = 'T' . $txnDateTime;
        $txnExpiryDateTime = now()->addDays(1)->format("YmdHis");

        $data = [
            "pp_Amount" => 100 * 100,
            "pp_BillReference" => "billref",
            "pp_CNIC" => substr('345678', -6),
            "pp_Description" => "Transaction",
            "pp_Language" => "EN",
            "pp_MerchantID" => $this->jazzcash2()->merchant,
            "pp_MobileNumber" => '03123456789',
            "pp_Password" => $this->jazzcash2()->password,
            "pp_TxnCurrency" => "PKR",
            "pp_TxnDateTime" => $txnDateTime,
            "pp_TxnExpiryDateTime" => $txnExpiryDateTime,
            "pp_TxnRefNo" => $txnRefNo,
            "ppmpf_1" => "a",
            "ppmpf_2" => "a",
            "ppmpf_3" => "a",
            "ppmpf_4" => "a",
            "ppmpf_5" => "a",
        ];
        // Sort data keys by ASCII
        ksort($data);
        // Concatenate
        $concatenatedString = $integritySalt;
        foreach ($data as $key => $value) {
            $concatenatedString .= "&$value";
        }

        // return $concatenatedString;
        $secureHash = hash_hmac('sha256', $concatenatedString, $integritySalt);

        $data["pp_SecureHash"] = $secureHash;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post($this->jazzcash2()->walletUrl, $data);

            $response = json_decode(json_encode($response->json()));

            if ($response->pp_ResponseCode === "000") {
                return (object)[
                    "verified" => "success",
                    "id" => $response->pp_TxnRefNo,
                    "message" => $response->pp_ResponseMessage,
                    "response" => $response,
                ];
            } else if ($response->pp_ResponseCode === "157") {
                return (object)[
                    "verified" => "pending",
                    "id" => $response->pp_TxnRefNo ?? null,
                    "message" => $response->pp_ResponseMessage ?? 'Unknown error',
                    "response" => $response,
                ];
            } else {
                return (object)[
                    "verified" => "reject",
                    "id" => $response->pp_TxnRefNo ?? null,
                    "message" => $response->pp_ResponseMessage ?? 'Unknown error',
                    "response" => $response,
                ];
            }
        } catch (\Exception $e) {
            return (object)[
                "verified" => "reject",
                "id" => null,
                "message" => "API request failed: " . $e->getMessage(),
                "response" => null,
            ];
        }
    }
}
