<?php

use App\Models\Booking\TicketELT;
use App\Models\City;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Hrm\Employee\Employee;
use Illuminate\Support\Facades\Http;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Discount\Discount;
use App\Models\Schedule\Schedule;
use App\Models\Route\SubRoute;
use App\Models\Terminal;
use App\Models\Booking\TicketIsPartial;
use App\Models\Company;
use App\Models\Terminal\TerminalTimeDifference;
use App\Models\Schedule\ScheduleDetail;
use App\Models\TerminalDiscount;
use App\Models\Schedule\TicketClosing;
use App\Http\Resources\CreatedResource;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Schedule\TicketClosingMerge;
use App\Models\admin\Role;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Rawilk\Printing\Facades\Printing;
use Rawilk\Printing\Receipts\ReceiptPrinter;


if (!function_exists('checkForSubmenu')) {
    function checkForSubmenu($moduleName) {
        $permissions = Role::find(Auth::user()->role_id)->permissions;
        $valid = false;
    
        // Loop through each permission item
        foreach ($permissions as $permission) {
            // Check if 'childs' key exists and is an array
            if (isset($permission['childs']) && is_array($permission['childs'])) {
                // Loop through each submenu item
                foreach ($permission['childs'] as $subMenuItem) {
                    // Check if 'name' matches the moduleName
                    if ($subMenuItem['name'] == $moduleName) {
                        // Set valid to the 'allow' value of the matching submenu item
                        $valid = $subMenuItem['allow'];
                        // Exit the loop since we found the match
                        break 2; // Exit both foreach loops
                    }
                }
            }
        }
    
        return $valid;
    }
}
if (!function_exists('checkPermissionButtons')) {
    function checkPermissionButtons($name)
    {
        $permissions = Role::find(Auth::user()->role_id)->permissions;
        foreach($permissions as $menu)
        {
            foreach($menu['childs'] as $submenu)
            {
                if(isset($submenu['buttons']))
                {
                    foreach($submenu['buttons'] as $button)
                    {
                        if($button['name'] == $name)
                        {
                            return $button['allow'];
                        }
                    }
                }
            }
        }
    }
}
if (!function_exists('storeFare')) {
    function storeFare($request, $company_id)
    {
        $fare1Side = FareTable::create([
            'fare' => $request->fare,
            'from_city_id' => $request->from,
            'to_city_id' => $request->to,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'added_by' => Auth::user()->id,
        ]);
        /*Creating Route Fares those fare added after creating the route of one side*/
        $routeFares1Side = RouteFare::where('departure_city_id', $request->from_city_id)
            ->where('destination_city_id', $request->to_city_id)
            ->get();
        foreach ($routeFares1Side as $i => $routeFare) {
            $routeFare->fare_id = $fare1Side->id;
            RouteFare::create($routeFare->toArray());
        }

        $fare2Side = FareTable::create([
            'fare' => $request->fare,
            'from_city_id' => $request->to,
            'to_city_id' => $request->from,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'added_by' => Auth::user()->id,
        ]);

        $routeFares2Side = RouteFare::where('departure_city_id', $fare2Side->from_city_id)
            ->where('destination_city_id', $fare2Side->to_city_id)
            ->get();
        foreach ($routeFares2Side as $i => $routeFare) {
            RouteFare::create([
                ...$routeFare,
                'fare_id' => $fare2Side->id
            ]);
        }
    }
}

if (!function_exists('plainContactAndCnic')) {
    function plainContactAndCnic(string $input_string)
    {
        if (preg_match('/[\'^£$%&*()}{@#~?><,|=_+¬-]/', $input_string)) {
            return str_replace('-', '', $input_string);
        }
        return $input_string;
    }
}

if (!function_exists('formatContact')) {
    function formatContact(string $phone_no)
    {
        return preg_replace(
            "/.*(\d{4})[^\d]{0,7}(\d{7})/",
            '$1-$2',
            $phone_no
        );
    }
}

if (!function_exists('formatCNIC')) {
    function formatCNIC(string $phone_no)
    {
        return preg_replace(
            "/.*(\d{5})[^\d]{0,7}(\d{7})[^\d]{0,7}(\d{1})/",
            '$1-$2-$3',
            $phone_no
        );
    }
}

if (!function_exists('formatUAN')) {
    function formatUAN(string $phone_no)
    {
        return preg_replace(
            "/.*(\d{2})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{3})/",
            '$1-$2-$3-$4',
            $phone_no
        );
    }
}

if (!function_exists('priceDiff')) {
    function priceDiff(int $old, int $new)
    {
        if ($new == $old) {
            return [
                'diff' => $new - $old,
                'type' => 'same',
            ];
        }
        if ($new > $old) {
            return [
                'diff' => $new - $old,
                'type' => 'receivable by customer',
            ];
        }
        if ($new < $old) {
            return ['diff' => $new - $old,
                'type' => 'refund to customer',
            ];
        }
    }
}

if (!function_exists('checkDiscountAmount')) {
    function checkDiscountAmount($detail,$terminalId,$fare_class)
    {

        $scheduleDiscount = Discount::where('id', $detail->schedule->discount_id)
        ->where('is_active', 1)
        ->whereHas("discount_terminals", function ($q) use ($terminalId) {
            $q->where("terminal_id", $terminalId ?? Auth::user()->terminal_id);
        })
        ->first();

        $terminalDiscount = TerminalDiscount::where(["terminal_id" => $terminalId ?? 0, "route_id" => $detail->schedule->route_id])
        ->where('start_date', '<=', date("Y-m-d"))
        ->where('end_date', '>=', date("Y-m-d"))->first();

        $fare = FareTable::where('from_city_id', $detail->departure_id)
            ->where('to_city_id', $detail->destination_id)
            ->where('fare_class', $fare_class)
            ->where('company_id', Auth::user()->company_id)
            ->first()->fare;

        $discounted_fare = $fare;
        if ($scheduleDiscount) {
            if ($scheduleDiscount->type == "percentage") {
                $number = $scheduleDiscount->percentage / 100;
                $percentage = (int)$fare * $number;
                $discounted_fare = round((int)$fare - $percentage);
            } else {
                $discounted_fare = (int)$fare - (int)$scheduleDiscount->flat;
            }
        }
        if ($terminalDiscount) {
            $tdiscount = ((int)$fare / 100) * (float)$terminalDiscount->discount;
            $discounted_fare = $discounted_fare - $tdiscount;
        }
        
        $discount = round($fare - $discounted_fare);
        $result = $discount % 10;
        if($result == 0)
        {
            return $discount;
        }
        else
        {
            return $discount - $result;
        }
    }
}

if (!function_exists('updateFare')) {
    function updateFare($request, $company_id)
    {
        FareTable::where('id', $request->id)->update([
            'fare' => $request->fare,
            'from_city_id' => $request->from,
            'to_city_id' => $request->to,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'updated_by' => Auth::user()->id,
        ]);
        FareTable::where('from_city_id', $request->to)->where('to_city_id', $request->from)->where('fare_class',
            $request->fare_class)->update([
            'fare' => $request->fare,
            'from_city_id' => $request->to,
            'to_city_id' => $request->from,
            'fare_class' => $request->fare_class,
            'company_id' => $company_id,
            'time_difference' => $request->time_difference,
            'distance_in_km' => $request->distance_in_km,
            'updated_by' => Auth::user()->id,
        ]);
        // this is for automatic store time diffrence against all fare classes
        FareTable::where('from_city_id', $request->from)->where('to_city_id', $request->to)
            ->where('company_id', $company_id)->update([
                'time_difference' => $request->time_difference,
                'distance_in_km' => $request->distance_in_km,
            ]);
        FareTable::where('from_city_id', $request->to)->where('to_city_id', $request->from)
            ->where('company_id', $company_id)->update([
                'time_difference' => $request->time_difference,
                'distance_in_km' => $request->distance_in_km,
            ]);
    }
}

//Updated Already advanced Booked Seat
if (!function_exists('updateAdvancedSeat')) {
    function updateAdvancedSeat($request, $invoice,$finalAmountDiscount)
    {
        // $customerData =  Customer::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request->customerCNIC))->orWhere("contact",plainContactAndCnic($request->contact))->first();
        $customerData =  Customer::where('company_id', Auth::user()->company_id)->where('cnic', plainContactAndCnic($request->customerCNIC))->first();
        // $customerAll = [];
        if($customerData)
        {
            $customerData->update([
                'name' => $request->customerName,
                'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
                'contact' => plainContactAndCnic($request->contact),
            ]);
        }
        else
        {
            $customerData = Customer::create([
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
                'name' => $request->customerName,
                'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
                'contact' => plainContactAndCnic($request->contact),
            ]);
        }

        $detail = ScheduleDetail::where("departure_id", $request->departureCity)
            ->where("destination_id", $request->destinationCity)
            ->where('schedule_id', $request->schedule)
            ->where('departure_date', $request->date)
            ->where('company_id', Auth::user()->company_id)
            ->where('departure_time', date("H:i:s",strtotime($request->departure_time)))
            ->first();

        foreach ($request->alreadyBookedId as $key => $single) {
            $customer_id = Ticket::where('company_id', Auth::user()->company_id)->where('id', $single)->first();
            $checkDiscount =  checkDiscountAmount($detail,$request->terminalId,$request->advanceSeatClass[$key]);
            $customer_id->update([
                'type' => 'booked',
                'schedule_time' => $request->departure_time,
                'invoice_id' => $invoice->id,
                'seat_fare' => $request->reservedFare[$key],
                'discount' => ($request->discount ? round($request->discount / count($request->alreadyBookedId)) : ($finalAmountDiscount ? ($finalAmountDiscount / count($request->alreadyBookedId)) : 0)),
                'display_discount' => $checkDiscount,
                'remarks' => $request->remarks,
                'customer_id' => $customerData->id,
                'updated_by' => Auth::user()->id,
                'booked_time' => date("Y-m-d H:i:s"),
                'discount_type'       => $request->usagePoints ? 'card' : null,
                'points_usage' => $request->pointsUseInput / count($request->alreadyBookedId),
            ]);

            // online terminal request will be differrent so it is in if condition
            if($request->destinationCity)
            {
            // checking partial
                $schedule = Schedule::where('id', $customer_id->schedule_id)->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
                $departure_city_id = $schedule->route->fares->first()->departure_city_id;
                $destination_city_id = $schedule->route->fares->last()->destination_city_id;
                $isPartial = 0;
                if ($request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id) {
                    $isPartial = 1;
                }
                
                $customer_id->update([
                    'terminal_id' => $request->terminalId,
                    'departure_city_id' => $request->departureCity,
                    'terminal_name' => "",
                    'online_terminal' => 0,
                    'is_partial' => $isPartial,
                    'destination_city_id' => $request->destinationCity,
                ]);
                

                if ($isPartial == 1) {
                    TicketIsPartial::create([
                        'company_id' => Auth::user()->company_id,
                        'departure_city_id' => $customer_id->departure_city_id,
                        'destination_city_id' => $customer_id->destination_city_id,
                        'ticket_id' => $customer_id->id,
                        'seat_no' => $customer_id->seat_no,
                        'seat_fare' => $customer_id->seat_fare,
                        'booking_no' => $customer_id->booking_no,
                        'date' => $customer_id->date,
                        'customer_id' => $customer_id->customer_id,
                        'schedule_id' => $customer_id->schedule_id,
                        'gender' => $customer_id->gender,
                        'type' => $customer_id->type,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                else
                {
                    TicketIsPartial::where([
                        'ticket_id' => $customer_id->id,
                    ])->delete();
                }
            }
            // $customerAll[] = Ticket::where('company_id', Auth::user()->company_id)->where('id',
            //     $single)->first(['customer_id'])->customer_id;
        }
        // $updateId = Customer::where('company_id', Auth::user()->company_id)->where('id', array_unique($customerAll)[0])->first();
        // $updateId->update([
        //     'name' => $request->customerName,
        //     'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
        //     'contact' => plainContactAndCnic($request->contact),
        // ]);
        return $request->alreadyBookedId;
    }
}

if (!function_exists('terminalTimes')) {
    function terminalTimes($detail,$route)
    {
        $departure_times = [];
        $terminalTime = TerminalTimeDifference::where(['company_id' => Auth::user()->company_id, 'city_id' => $detail->departure_id, 'route_id' => $route,'show'=>1])->with("terminal:id,name")->get();
        if($terminalTime->count() > 0)
        {
            foreach($terminalTime as $single)
            {
                $time = (object)[];
                $sub = 0;
                $sub = $single->time_difference * 60;
                $time->terminal_name = $single->display_name ? $single->display_name : 'time';
                $time->terminal_time = date("h:i A", strtotime($detail->departure_date . " " . $detail->departure_time) + $sub);
                $departure_times[] = $time;
            }
        }
        else
        {
            $time = (object)[];
            $time->terminal_name = 'time';
            $time->terminal_time = date("h:i A", strtotime($detail->departure_time));
            $departure_times[] = $time;
        }

        return $departure_times;
    }
}


if (!function_exists('ticketConfirmedMessage')) {
    function ticketConfirmedMessage($tickets,$type)
    {
        $auth_key = Company::where("id",Auth::user()->company_id)->first()->whatsapp_auth_key;
        $message_allow = Terminal::where("id",Auth::user()->terminal_id)->first()->send_message;
        if($auth_key && $message_allow)
        {
        $seats = implode(",",Ticket::whereIn("id",$tickets)->pluck("seat_no")->toArray());
        $detail = Ticket::where("id",$tickets[0])->with("departure_city:id,name","destination_city:id,name","customer:id,name,contact","terminal:id,name")->first();
        $cancelMessage = SubRoute::where(["from_city"=>$detail->departure_city_id,"to_city"=>$detail->destination_city_id])->first()->cancel_message??'';
        // this is for timing from different terminal
        $html = "";
        $terminalTime = TerminalTimeDifference::where(['company_id' => $detail->company_id, 'city_id' => $detail->departure_city_id, 'route_id' => $detail->route_id,'show'=>1])->with("terminal:id,name")->get();
        if($terminalTime->count() > 0)
        {
            foreach($terminalTime as $single)
            {
                $sub = 0;
                $sub = $single->time_difference * 60;
                $html .= "*".($single->display_name ? $single->display_name : 'Time').":* ".date("h:i A", strtotime($detail->date . " " . $detail->schedule_time) + $sub)."\n";
            }
        }
        else
        {
            $html .= "*Time:* ".date("h:i A", strtotime($detail->schedule_time));
        }

        
        // to choose random device
        // Define an array of names
        $names = [
            1 => 'Hamza_4-Device1',
            2 => 'Hamza_4-Device2',
            3 => 'Hamza_4-Device3',
            4 => 'Hamza_4-Device4',
            5 => 'Hamza_4-Device-5'
        ];
        $randomNumber = rand(1, 5);


        
         
        $url = "https://whatsapp.sarzone.com/api/send-messages";
        $mobile = "92".substr($detail->customer->contact, -10);
        $session = $names[$randomNumber];
        $messageConfirmed = "Dear ".$detail->customer->name.",
Seat# $seats, ".$detail->departure_city->name." to ".$detail->destination_city->name."
Date ".$detail->date."
has been Confirmed
Departure at:
$html

For any inquiries/Complains Dial UAN 03111777333

Terms & conditions applied
1:Arrive terminal 30 before departure Bus will not delayed for passenger.
2: Per person allowed luggage is upto 30kg only,Commercial or additional luggage will booked additionally.
3: Wifi upto 350mb,Refreshment/Food & Multimedia services are Complementary & non claimable.
4:For passenger safety Bus will not pick/drop passengers from Roadside or outside Company Terminal
5: Keep your personal belongings Safe Company is not responsible for any loss or damage.";

        $messageReserved = "Dear ".$detail->customer->name.",
Seat# $seats,
".$detail->departure_city->name." to ".$detail->destination_city->name."
$html
Date ".$detail->date." Is Reserved
".$cancelMessage."

Terms & conditions applied.";

        $response = Http::withHeaders([
            'X-Api-Key'=>$auth_key,
        ])->post($url, [
            "session" => $session,
            "message_type" =>  'text',
            "receiver_number" => $mobile, 
            "message_body" => $type == "advance booking" ? $messageReserved : $messageConfirmed
        ]);
        return $response;
        }
    }
}
//Updated Already advanced Booked Seat Api
if (!function_exists('updateAdvancedSeatApi')) {
    function updateAdvancedSeatApi($request, $company_id)
    {
        $customerData =  Customer::where('company_id', $company_id)->where('cnic', plainContactAndCnic($request->customer_cnic))->orWhere("contact",plainContactAndCnic($request->contact))->first();
        // $customerAll = [];
        if($customerData)
        {
            $customerData->update([
                'name' => $request->customerName,
                'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
                'contact' => plainContactAndCnic($request->contact),
            ]);
        }
        else
        {
            $customerData = Customer::create([
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
                'name' => $request->customerName,
                'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
                'contact' => plainContactAndCnic($request->contact),
            ]);
        }
        foreach ($request->alreadyBookedId as $key => $single) {
            $customer_id = Ticket::where('company_id', $company_id)->where('id', $single)->first();
            $customer_id->update([
                'type' => 'booked',
                'customer_id' => $customerData->id,
                'updated_by' => Auth::user()->id,
            ]);

            // online terminal request will be differrent so it is in if condition
            if($request->destinationCity)
            {
            // checking partial
                $schedule = Schedule::where('id', $customer_id->schedule_id)->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
                $departure_city_id = $schedule->route->fares->first()->departure_city_id;
                $destination_city_id = $schedule->route->fares->last()->destination_city_id;
                $isPartial = 0;
                if ($request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id) {
                    $isPartial = 1;
                }
                
                $customer_id->update([
                    'terminal_id' => $request->terminalId,
                    'departure_city_id' => $request->departureCity,
                    'is_partial' => $isPartial,
                    'destination_city_id' => $request->destinationCity,
                ]);
                

                if ($isPartial == 1) {
                    TicketIsPartial::create([
                        'company_id' => Auth::user()->company_id,
                        'departure_city_id' => $customer_id->departure_city_id,
                        'destination_city_id' => $customer_id->destination_city_id,
                        'ticket_id' => $customer_id->id,
                        'seat_no' => $customer_id->seat_no,
                        'seat_fare' => $customer_id->seat_fare,
                        'booking_no' => $customer_id->booking_no,
                        'date' => $customer_id->date,
                        'customer_id' => $customer_id->customer_id,
                        'schedule_id' => $customer_id->schedule_id,
                        'gender' => $customer_id->gender,
                        'type' => $customer_id->type,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                else
                {
                    TicketIsPartial::where([
                        'ticket_id' => $customer_id->id,
                    ])->delete();
                }
            }
            // $customerAll[] = Ticket::where('company_id', $company_id)->where('id',
            //     $single)->first(['customer_id'])->customer_id;
        }
        // $updateId = Customer::where('company_id', $company_id)->where('id', array_unique($customerAll)[0])->first();
        // $updateId->update([
        //     'name' => $request->customerName,
        //     'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
        //     'contact' => plainContactAndCnic($request->contact),
        // ]);
        return $request->alreadyBookedId;
    }
}

//updated Fare Table for first time
if (!function_exists('updateFareTable')) {
    function updateFareTable($company_id)
    {
        $fareClasses = FareClass::where('company_id', $company_id)->get();
        // Loop through each fare class
        foreach ($fareClasses as $fareClass) {
            // Get all cities for the company
            $cities = City::where('company_id', $company_id)->get();
            // Loop through each city as the first city
            foreach ($cities as $firstCity) {
                // Loop through each city as the second city
                foreach ($cities as $secondCity) {
                    // Check if the first city and second city are different
                    if ($firstCity->id != $secondCity->id) {
                        // Check if there is an existing fare for the fare class, first city, and second city
                        $oldFare = FareTable::where([
                            'company_id' => $company_id,
                            "fare_class" => $fareClass->id,
                            "from_city_id" => $firstCity->id,
                            "to_city_id" => $secondCity->id,
                        ])->first();
                        // If there is no existing fare, create a new one with a fare of 0
                        if (!$oldFare) {
                            FareTable::create([
                                "fare" => 0,
                                "fare_class" => $fareClass->id,
                                "from_city_id" => $firstCity->id,
                                "to_city_id" => $secondCity->id,
                                "company_id" => $company_id,
                                "time_difference" => $fareClass->time_difference,
                                "distance_in_km" => $fareClass->distance_in_km,
                                "added_by" => Auth::user()->id,
                            ]);
                            // If the fare class is not the first fare class, update the new fare with the fare from the first fare class
                            if ($fareClass->id != $fareClasses->first()->id) {
                                $firstFare = FareTable::where([
                                    'company_id' => $company_id,
                                    "fare_class" => $fareClasses->first()->id,
                                    "from_city_id" => $firstCity->id,
                                    "to_city_id" => $secondCity->id,
                                ])->first();
                                if ($firstFare) {
                                    $newFare = FareTable::where([
                                        'company_id' => $company_id,
                                        "fare_class" => $fareClass->id,
                                        "from_city_id" => $firstCity->id,
                                        "to_city_id" => $secondCity->id,
                                    ])->first();
                                    $newFare->time_difference = $firstFare->time_difference;
                                    $newFare->distance_in_km = $firstFare->distance_in_km;
                                    $newFare->save();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

//Print Ticket function
if (!function_exists('printTicket')) {
    function printTicket($ticketIds, $company_id, $duplicate = 0)
    {
        $format = TicketsTemplate::where(['company_id' => $company_id, 'status' => 1])->first();
        $tickets = Ticket::with('customer', 'schedule', 'departure_city', 'destination_city', 'seatClass')->where('company_id', $company_id)->whereIn('id', $ticketIds)->get();
        foreach ($tickets as $single) {
            $receipt = (string)(new ReceiptPrinter)
                ->centerAlign()
                ->text('Kainat Travels')
                ->feed()
                ->text($format->address)
                ->feed()
                ->text('UAN(24/7) : ' . formatUAN($format->uan))
                ->feed()
                ->text('Phone : ' . formatContact($format->phone))
                ->feed(2)
                ->twoColumnText('Customer Name : ', $single['customer']->name)
                ->feed()
                ->twoColumnText('Seat No : ', $single->seat_no)
                ->feed()
                ->twoColumnText('Bus Class : ', $single['schedule']['bus_class']->name)
                ->feed()
                ->twoColumnText('From : ', $single['departure_city']->name)
                ->feed()
                ->twoColumnText('To : ', $single['destination_city']->name)
                ->feed()
                ->twoColumnText('Departure Date : ', date('d/m/Y', strtotime($single->date)))
                ->feed()
                ->twoColumnText('Departure Time : ', date('H:i A', strtotime($single['schedule']->time)))
                ->feed()
                ->twoColumnText('Booking Date : ', date('d/m/Y H:i A', strtotime($single->created_at)))
                ->feed()
                ->twoColumnText('Fare : ', $single->seat_fare)
                ->feed()
                ->line()
                ->centerAlign()
                ->text('Terms and Condition Applied')
                ->feed()
                ->text($format->terms_condition)
                ->feed(3)
                ->text('© Rights Reserved By Kainat Travels')
                ->cut()
                ->twoColumnText('Seat No : ', $single->seat_no)
                ->feed()
                ->twoColumnText('Bus Class : ', $single['schedule']['bus_class']->name)
                ->feed()
                ->twoColumnText('From : ', $single['departure_city']->name)
                ->feed()
                ->twoColumnText('To : ', $single['destination_city']->name)
                ->feed()
                ->twoColumnText('Departure Date : ', date('d/m/Y', strtotime($single->date)))
                ->feed()
                ->twoColumnText('Customer Name : ', $single['customer']->name)
                ->feed()
                ->twoColumnText('Customer CNIC : ', formatCNIC($single['customer']->cnic))
                ->feed()
                ->twoColumnText('Customer Contact : ', formatContact($single['customer']->contact))
                ->feed()
                ->cut();
            // Now send the string to your receipt printer
            Printing::newPrintTask()
                ->printer(Session('printerId'))
                ->content($receipt)
                ->send();
        }
    }
}

//Elt Ticket Details
if (!function_exists('printEltTicket')) {
    function printEltTicket($eltId, $company_id)
    {
        $format = TicketsTemplate::where(['company_id' => 1, 'status' => 1, 'terminal_id' =>
            Auth::user()->terminal_id])->first();
        $eltTicket = TicketELT::with('departure:id,name', 'destination:id,name', 'company', 'ticket.seatClass',
            'customer', 'schedule', 'schedule.bus_class:id,name')->where(['company_id' => $company_id, 'id' =>
            $eltId])->first();

        $receipt = (string)(new ReceiptPrinter)
            ->centerAlign()
            ->text('Kainat Travels')
            ->text($format->address)
            ->text('UAN(24/7) : ' . formatUAN($format->uan))
            ->text('Phone : ' . formatContact($format->phone))
            ->feed(2)
            ->twoColumnText('Customer Name : ', $eltTicket['customer']->name)
            ->feed()
            ->twoColumnText('Seat No : ', $eltTicket->seat_no)
            ->feed()
            ->twoColumnText('Bus Class : ', $eltTicket['schedule']['bus_class']->name)
            ->feed()
            ->twoColumnText('From : ', $eltTicket['departure_city']->name)
            ->feed()
            ->twoColumnText('To : ', $eltTicket['destination_city']->name)
            ->feed()
            ->twoColumnText('Departure Date : ', date('d/m/Y', strtotime($eltTicket->date)))
            ->feed()
            ->twoColumnText('Departure Time : ', date('H:i A', strtotime($eltTicket['schedule']->time)))
            ->feed()
            ->twoColumnText('Booking Date : ', date('d/m/Y H:i A', strtotime($eltTicket->created_at)))
            ->feed()
            ->twoColumnText('Fare : ', $eltTicket->seat_fare)
            ->feed()
            ->line()
            ->centerAlign()
            ->text('Terms and Condition Applied')
            ->feed()
            ->text($format->terms_condition)
            ->feed(3)
            ->text('© Rights Reserved By Kainat Travels')
            ->feed(2)
            ->cut()
            ->twoColumnText('Seat No :', $eltTicket->seat_no)
            ->feed()
            ->twoColumnText('Bus Class :', $eltTicket['schedule']['bus_class']->name)
            ->feed()
            ->twoColumnText('From :', $eltTicket['departure_city']->name)
            ->feed()
            ->twoColumnText('To :', $eltTicket['destination_city']->name)
            ->feed()
            ->twoColumnText('Departure Date :', date('d/m/Y', strtotime($eltTicket->date)))
            ->feed()
            ->twoColumnText('Customer Name :', $eltTicket['customer']->name)
            ->feed()
            ->twoColumnText('Customer CNIC :', formatCNIC($eltTicket['customer']->cnic))
            ->feed()
            ->twoColumnText('Customer Contact :', formatContact($eltTicket['customer']->contact))
            ->feed()
            ->cut();

        // Now send the string to your receipt printer
        Printing::newPrintTask()
            ->printer(Session('printerId'))
            ->content($receipt)
            ->send();
    }
}

//Upload Image API
if (!function_exists('codeImage')) {
    function codeImage($code)
    {
        $codeEncode = urlencode($code);
        $data = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?data=$codeEncode&size=350x350");
        $id = explode("| ", $code)[10];
        $nameToStore = "ticketId" . "-" . (int)explode(":", $id)[1] . "-" . time() . ".png";
        $path = public_path() . '/Customers/Qrs/';
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        file_put_contents(public_path("Customers/Qrs/$nameToStore"), $data);
        return $nameToStore;
    }
}

//Custom round value function
if (!function_exists('customRound')) {
    function customRound($value)
    {
        // multiple of 10
        $value = round($value);
        $result = $value % 10;
        // $new = 10 - ($result==0 ? 10 : $result);
        if($result == 0)
        {
            return $value;
        }
        else
        {
            return $value - $result + 10;
        }
        
        // round 50 multiple
        // $result = $value % 100;
        // if($result < 25)
        // {
        //     $round = 0;
        // }
        // elseif($result >= 25 && $result < 75)
        // {
        //     $round = 50;
        // }
        // elseif($result >= 75 )
        // {
        //     $round = 100;
        // }
        // $result = $value - $result + $round;
        
        // return $result;
    }
}

//Upload ELt Image API
if (!function_exists('codeImageElt')) {
    function codeImageElt($code)
    {
        $codeEncode = urlencode($code);
        $data = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?data=$codeEncode&size=250x250");
        $id = explode("| ", $code)[10];
        $nameToStore = "ticketId" . "-" . (int)explode(":", $id)[1] . "-" . time() . ".png";
        $path = public_path() . '/Customers/Elt/';
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        file_put_contents(public_path("Customers/Elt/$nameToStore"), $data);
        return $nameToStore;
    }
}
//Get Drivers
if (!function_exists('getMembers')) {
    function getMembers($data, $company_id, $type)
    {
//        dd($data, $company_id, $type);
        if ($data && $company_id && $type) {
            $dataMember = TicketClosingMember::where([
                'company_id' => $company_id,
                'ticket_closing_id' => $data->ticket_closing_id,
                'type' => $type,
            ])->pluck('user_id');
//            for drivers
            if ($type == 1) {
                return Employee::where('company_id', $company_id)->whereIn('id', $dataMember)->get(['name', 'contact']) ?? [];
            } else {
                return Employee::where('company_id', $company_id)->whereIn('user_id', $dataMember)->get(['name', 'contact']) ??
                    [];
            }
        }
        return [];
    }
}

//Get Buses
if (!function_exists('getBusName')) {
    function getBusName($id)
    {
        return \App\Models\Bus\Bus::where('id', $id)->first()->bus_number;
    }
}


//Get route name
if (!function_exists('routeName')) {
    function routeName($id)
    {
        $routeId = Schedule::where('id', $id)->first(['route_id'])->route_id;
        return Route::where('id', $routeId)->first(['id', 'name'])->name;
    }
}

//Get online Terminals
if (!function_exists('getTerminals')) {
    function getTerminals()
    {
        return \App\Models\Terminal::where('is_online_terminal', 1)->where('company_id', Auth::user()->company_id)->get();
    }
}

//Get Dynamic Headers
if (!function_exists('getDynamicHeaders')) {
    function getDynamicHeaders()
    {
        return \App\Models\ReportsHeader::where('company_id', Auth::user()->company_id)->get(['id', 'name']);
    }
}

//Get getRowBadgeColor
if (!function_exists('getRowBadgeColor')) {
    function getRowBadgeColor($departureTime, $cancellationTime)
    {
        $secDepart = strtotime($departureTime);
        $secCancellation = strtotime($cancellationTime);
        $threeHoursBefore = $secDepart - 10800;
        $oneHoursBefore = $threeHoursBefore - 3600;

        if ($secCancellation > $secDepart) {
            return "red";
        }

        if ($secCancellation > $threeHoursBefore) {
            return "yellow";
        }
        if ($secCancellation > $oneHoursBefore) {
            return "green";
        }
        return "white";
    }
}

//Update Closed Schedule Function
if (!function_exists('updateCloseSchedule')) {
    function updateCloseSchedule($request)
    {
        // this is for get route id that will be followed by schedule
        $route = Schedule::find($request->schedule)->route_id;
        // this is for get schedule start city
        $departure = RouteFare::where("route_id", $route)->orderBy('id', 'ASC')->first();
        // this is for get schedule end city
        $destination = RouteFare::where("route_id", $route)->orderBy('id', 'DESC')->first();
        // this is for get schedule departure time
        $depTime = ScheduleDetail::where(["schedule_id" => $request->schedule,
            "departure_id" => $departure->departure_city_id,
            "destination_id" => $departure->destination_city_id,
            "departure_date" => $request->date,
            "company_id" => Auth::user()->company_id
        ])->first();
        $bookingAvailable = Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, 'schedule_date' => $depTime->schedule_date])->get();
        if (count($bookingAvailable) == 0) {
            return response()->json(["errors" => ["Tickets Error" => ["No Booking Found! \n\n Booked Any Single Seat First"]]], 422);
        } else {
            foreach ($bookingAvailable as $key => $single) {
                $single->ticket_closing_id = null;
                $single->bus_id = null;
                $single->save();
            }
        }
        $checkMergeRecord = TicketClosingMerge::where(["company_id" => Auth::user()->company_id, "id" => $request->ticket_merge_id])->first();
        if ($checkMergeRecord) {
            TicketClosingMerge::where(["company_id" => Auth::user()->company_id, "id" => $request->ticket_merge_id])->update([
                "schedule_return_date" => null,
                "schedule_complete" => 0,
            ]);
        }
        $closings = TicketClosing::where('id', $request->ticket_closing_id)->first();
        $members = TicketClosingMember::where([
            "ticket_closing_id" => $closings->id,
            "bus_id" => $request->bus,
            'company_id' => Auth::user()->company_id,
            'added_by' => Auth::user()->id,
        ])->get();
        foreach ($members as $key => $singleMember) {
            $singleMember->delete();
        }
        Ticket::where(["company_id" => Auth::user()->company_id, "schedule_id" => $request->schedule, "schedule_date" => $request->date])->update([
            "bus_id" => null,
            "ticket_closing_id" => null,
            "ticket_merge_id" => null,
        ]);
        $closings->delete();
    }
}
