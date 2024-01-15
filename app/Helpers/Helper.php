<?php

use App\Models\Booking\TicketELT;
use App\Models\City;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Hrm\Employee\Employee;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Booking\TicketIsPartial;
use App\Models\Schedule\ScheduleDetail;
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
    function updateAdvancedSeat($request, $company_id)
    {
        // $customerData =  Customer::where('company_id', $company_id)->where('cnic', plainContactAndCnic($request->customerCNIC))->orWhere("contact",plainContactAndCnic($request->contact))->first();
        $customerData =  Customer::where('company_id', $company_id)->where('cnic', plainContactAndCnic($request->customerCNIC))->first();
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
                'discount' => $request->discount ? round($request->discount / count($request->alreadyBookedId)) : 0,
                'customer_id' => $customerData->id,
                'updated_by' => Auth::user()->id,
            ]);

            // online terminal request will be differrent so it is in if condition
            if($request->destinationCity)
            {
            // checking partial
                $schedule = Schedule::where('id', $customer_id->schedule_id)->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
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
//Updated Already advanced Booked Seat Api
if (!function_exists('updateAdvancedSeatApi')) {
    function updateAdvancedSeatApi($request, $company_id)
    {
        $customerData =  Customer::where('company_id', $company_id)->where('cnic', plainContactAndCnic($request->customer_cnic))->orWhere("contact",plainContactAndCnic($request->contact))->first();
        // $customerAll = [];
        if($customerData)
        {
            $customerData->update([
                'name' => $request->customer_name,
                'cnic' => is_null($request->customerCNIC) ? 0 : plainContactAndCnic($request->customerCNIC),
                'contact' => plainContactAndCnic($request->contact),
            ]);
        }
        else
        {
            $customerData = Customer::create([
                'company_id' => Auth::user()->company_id,
                'added_by' => Auth::user()->id,
                'name' => $request->customer_name,
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
                $schedule = Schedule::where('id', $customer_id->schedule_id)->where('company_id', Auth::user()->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
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
        $result = $value % 100;
        if($result < 25)
        {
            $round = 0;
        }
        elseif($result >= 25 && $result < 75)
        {
            $round = 50;
        }
        elseif($result >= 75 )
        {
            $round = 100;
        }
        $result = $value - $result + $round;
        
        return $result;
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
