<?php

use App\Models\Booking\TicketELT;
use App\Models\City;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Hrm\Employee\Employee;
use App\Models\Route\RouteFare;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use charlieuki\ReceiptPrinter\ReceiptPrinter as ReceiptPrinter;
use Illuminate\Support\Facades\File;


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
            'added_by' => auth()->user()->id,
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
            'added_by' => auth()->user()->id,
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
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $input_string)) {
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
            'added_by' => auth()->user()->id,
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
            'added_by' => auth()->user()->id,
        ]);
    }
}

//Updated Already advanced Booked Seat
if (!function_exists('updateAdvancedSeat')) {
    function updateAdvancedSeat($request, $company_id)
    {
        $customerAll = [];
        foreach ($request->alreadyBookedId as $key => $single) {
            $customer_id = Ticket::where('company_id', $company_id)->where('id', $single)->first();
            $customer_id->update([
                'type' => 'booked',
            ]);
            $customerAll[] = Ticket::where('company_id', $company_id)->where('id',
                $single)->first(['customer_id'])->customer_id;
        }
        $updateId = Customer::where('company_id', $company_id)->where('id', array_unique($customerAll)[0])->first();
        $updateId->update([
            'cnic' => plainContactAndCnic($request->customerCNIC),
        ]);
        return $request->alreadyBookedId[0];
    }
}

//updated Fare Table for first time
if (!function_exists('updateFareTable')) {
    function updateFareTable($company_id)
    {
        $fareClasses = FareClass::where('company_id', $company_id)->get();
        $cities = City::where('company_id', $company_id)->get();
        foreach ($fareClasses as $fareClass) {
            foreach ($cities as $firstCity) {
                foreach ($cities as $secondCity) {
                    if ($firstCity->id != $secondCity->id) {
                        $oldFare = FareTable::where([
                            'company_id' => $company_id,
                            "fare_class" => $fareClass->id,
                            "from_city_id" => $firstCity->id,
                            "to_city_id" => $secondCity->id,
                        ])->first();
                        if (!$oldFare) {
                            FareTable::create([
                                "fare" => 0,
                                "fare_class" => $fareClass->id,
                                "from_city_id" => $firstCity->id,
                                "to_city_id" => $secondCity->id,
                                "company_id" => $company_id,
                                "added_by" => Auth::user()->id,
                            ]);
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
            // Set params
            $uan = formatUAN($format->uan);
            $company_name = 'Kainat Travels';
            $company_address = $format->address;
            $company_phone = formatContact($format->phone);
            $termsCondition = $format->terms_condition;
            $checkDuplicate = $duplicate;
            $seatNo = $single->seat_no;
            $busClass = $single['schedule']['bus_class']->name;
            $departureCity = $single['departure_city']->name;
            $destinationCity = $single['destination_city']->name;
            $departureDate = date('d/m/Y', strtotime($single->date));
            $departureTime = date('H:i A', strtotime($single['schedule']->time));
            $bookingDate = date('d/m/Y H:i A', strtotime($single->created_at));
            $seatFare = $single->seat_fare;
            $bookingId = $single->id;
            $busClass = $single['seatClass']->name;
            $customerName = $single['customer']->name;
            $customerCNIC = formatCNIC($single['customer']->cnic);
            $customerContact = formatContact($single['customer']->contact);
            //Code for Qr code
            $code = 'Name: ' . $customerName . '| ' .
                'CNIC: ' . $customerCNIC . '| ' .
                'Contact: ' . $customerContact . '| ' .
                'SeatNo: ' . $seatNo . '| ' .
                'Bus: ' . $busClass . '| ' .
                'From: ' . $departureCity . '| ' .
                'To: ' . $destinationCity . '| ' .
                'Dept Date: ' . $departureDate . '| ' .
                'Dept Time: ' . $departureTime . '| ' .
                'Booking DateTime: ' . $bookingDate . '| ' .
                'boookingId: ' . $bookingId . '| ' .
                'Fare: ' . $seatFare;

            $image = codeImage($code);
            // Init printer
            $printer = new ReceiptPrinter;
            $printer->init(config('receiptprinter.connector_type'), config('receiptprinter.connector_descriptor'));

            // Set store info
            $printer->setStore($uan, $company_name, $company_address, $company_phone, $termsCondition, $checkDuplicate,
                $seatNo, $customerContact, $customerCNIC, $customerName, $seatFare, $bookingDate, $departureTime,
                $departureDate, $departureCity, $destinationCity, $busClass);
            // Print Function passes Array to it
            $printer->printReceipt([
                'companyName' => $company_name,
                'companyAddress' => $company_address,
                'uan' => $uan,
                'phone' => $company_phone,
                'qr' => $image,
                'customerName' => $customerName,
                'seatNo' => $seatNo,
                'busClass' => $busClass,
                'from' => $departureCity,
                'to' => $destinationCity,
                'departDate' => $departureDate,
                'departTime' => $departureTime,
                'bookingDate' => $bookingDate,
                'fare' => $seatFare,
                'terms' => $termsCondition,
                'cnic' => $customerCNIC,
                'contact' => $customerContact,
                'duplicate' => $checkDuplicate,
            ]);
        }
    }
}

//Elt Ticket Details
if (!function_exists('printEltTicket')) {
    function printEltTicket($eltId, $company_id)
    {
        $format = TicketsTemplate::where(['company_id' => 1, 'status' => 1, 'terminal_id' => Auth::user()->terminal_id])->first();
        $eltTicket = TicketELT::with('departure:id,name', 'destination:id,name', 'company', 'ticket.seatClass', 'customer', 'schedule', 'schedule.bus_class:id,name')->where(['company_id' => $company_id, 'id' => $eltId])->first();
        // Set params
        $uan = formatUAN($format->uan);
        $company_name = 'Kainat Travels';
        $company_address = $format->address;
        $company_phone = formatContact($format->phone);
        $seatNo = $eltTicket->seat_no;
        $busClass = $eltTicket['schedule']['bus_class']->name;
        $departureCity = $eltTicket['departure']->name;
        $destinationCity = $eltTicket['destination']->name;
        $departureDate = date('d/m/Y', strtotime($eltTicket['ticket']->date));
        $departureTime = date('H:i A', strtotime($eltTicket['schedule']->time));
        $bookingDate = date('d/m/Y H:i A', strtotime($eltTicket->created_at));
        $seatFare = $eltTicket->seat_fare;
        $elt_price = $eltTicket->elt_price;
        $bookingId = $eltTicket->id;
        $customerName = $eltTicket['customer']->name;
        $customerCNIC = formatCNIC($eltTicket['customer']->cnic);
        $customerContact = formatContact($eltTicket['customer']->contact);
        $termsCondition = "test";
        $checkDuplicate = 0;
        $weight = $eltTicket->elt_weight . ' ' . "Kg";
        $totalFare = ((int)$eltTicket->elt_price) + ((int)$eltTicket->seat_fare);


        //Code for Qr code
        $code = 'Name: ' . $customerName . '| ' .
            'CNIC: ' . $customerCNIC . '| ' .
            'Contact: ' . $customerContact . '| ' .
            'SeatNo: ' . $seatNo . '| ' .
            'Bus: ' . $busClass . '| ' .
            'From: ' . $departureCity . '| ' .
            'To: ' . $destinationCity . '| ' .
            'Dept Date: ' . $departureDate . '| ' .
            'Dept Time: ' . $departureTime . '| ' .
            'Booking DateTime: ' . $bookingDate . '| ' .
            'boookingId: ' . $bookingId . '| ' .
            'Weight: ' . $weight . '| ' .
            'seatFare: ' . $seatFare . '| ' .
            'eltPrice: ' . $elt_price . '| ' .
            'totalFare: ' . $totalFare;
        $image = codeImageElt($code);
        // Init printer
        $printer = new ReceiptPrinter;
        $printer->init(config('receiptprinter.connector_type'), config('receiptprinter.connector_descriptor'));

        // Set store info
        $printer->setStore($uan, $company_name, $company_address, $company_phone, $termsCondition, $checkDuplicate,
            $seatNo, $customerContact, $customerCNIC, $customerName, $seatFare, $bookingDate, $departureTime,
            $departureDate, $departureCity, $destinationCity, $busClass);
        // Print Function passes Array to it
        $printer->printRequest([
            'companyName' => $company_name,
            'companyAddress' => $company_address,
            'uan' => $uan,
            'phone' => $company_phone,
            'qr' => $image,
            'customerName' => $customerName,
            'seatNo' => $seatNo,
            'busClass' => $busClass,
            'from' => $departureCity,
            'to' => $destinationCity,
            'departDate' => $departureDate,
            'departTime' => $departureTime,
            'bookingDate' => $bookingDate,
            'fare' => $seatFare,
            'cnic' => $customerCNIC,
            'weight' => $weight,
            'contact' => $customerContact,
            'elt_price' => $elt_price,
            'total_price' => $totalFare,
        ]);
    }
}

//Upload Image  API
if (!function_exists('codeImage')) {
    function codeImage($code)
    {
        $data = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?data=$code&size=350x350");
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

//Upload ELt Image  API
if (!function_exists('codeImageElt')) {
    function codeImageElt($code)
    {
        $data = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?data=$code&size=250x250");
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
        $dataMember = TicketClosingMember::where([
            'company_id' => $company_id,
            'ticket_closing_id' => $data[0]->ticket_closing_id,
            'type' => $type,
        ])->pluck('user_id');
        return Employee::where('company_id', $company_id)->whereIn('user_id', $dataMember)->get(['name', 'contact']);
    }
}
