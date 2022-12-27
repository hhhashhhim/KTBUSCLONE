<?php

use App\Models\City;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\RouteFare;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use charlieuki\ReceiptPrinter\ReceiptPrinter as ReceiptPrinter;


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

if (!function_exists('format_phone')) {
    function format_phone(string $phone_no)
    {
        return preg_replace(
            "/.*(\d{4})[^\d]{0,7}(\d{7})/",
            '$1-$2',
            $phone_no
        );
    }
}

if (!function_exists('format_cnic')) {
    function format_cnic(string $phone_no)
    {
        return preg_replace(
            "/.*(\d{5})[^\d]{0,7}(\d{7})[^\d]{0,7}(\d{1})/",
            '$1-$2-$3',
            $phone_no
        );
    }
}

if (!function_exists('format_uan')) {
    function format_uan(string $phone_no)
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
            return [
                'diff' => $new - $old,
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
        FareTable::where('from_city_id', $request->to)->where('to_city_id', $request->from)->where('fare_class', $request->fare_class)->update([
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
            $customerAll[] = Ticket::where('company_id', $company_id)->where('id', $single)->first(['customer_id'])->customer_id;
        }
        $updateId = Customer::where('company_id', $company_id)->where('id', array_unique($customerAll)[0])->first();
        $updateId->update([
            'cnic' => str_replace('-', '', $request->customerCNIC),
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

//Print Ticket  function
if (!function_exists('printTicket')) {
    function printTicket($ticketIds, $company_id, $duplicate)
    {
        $format = TicketsTemplate::where('company_id', $company_id)->where('status', 1)->first();
        $tickets = Ticket::with('customer', 'schedule', 'departure_city', 'destination_city')->where('company_id', $company_id)->whereIn('id', $ticketIds)->get();
        dd($format, $tickets);
        foreach ($tickets as $key => $single) {
// Set params
            $uan = 'UAN(24/7):' . ' ' . format_uan($format->uan);
            $company_name = 'Kainat Travels';
            $company_address = 'Address :'.' '.$format->address;
            $company_phone = 'Phone # :' . ' ' . format_phone($format->phone);
            $termsCondition = 'Terms & Condition :'.' '.$format->terms_condition;
            $checkDuplicate = $duplicate;
            $seatNo = 'Seat No:'.' '.$single[$key]->seat_no;
            $busClass = 'Bus Class'.' '.$single[$key]['schedule']['bus_class']->name ;
            $departureCity = 'Departure City:'.' '.$single[$key]['departure_city']->name;
            $destinationCity = 'Destination City:'.' '.$single[$key]['destination_city']->name;
            $departureDate = 'Departure Date:'.' '.date('d/m/Y', strtotime($single[$key]->date));
            $departureTime = 'Departure Time:'.' '.date('H:i A', strtotime($single[$key]['schedule']->time));
            $bookingDate = 'Booking Date:'.' '.date('d/m/Y H:i A', strtotime($single[$key]->created_at));
            $seatFare = 'Seat Fare:'.' '.$single[$key]->seat_fare;
            $customerName = 'Customer Name:'.' '.$single[$key]['customer']->name;
            $customerCnic = 'Customer CNIC:'.' '.format_cnic($single[$key]['customer']->cnic);
            $customerContact = 'Customer Contact:'.' '.format_phone($single[$key]['customer']->contact);
// Init printer
            $printer = new ReceiptPrinter;
            $printer->init(
                config('receiptprinter.connector_type'),
                config('receiptprinter.connector_descriptor')
            );

// Set store info
            $printer->setStore($uan, $company_name, $company_address, $company_phone, $termsCondition, $checkDuplicate, $seatNo, $customerContact, $customerCnic, $customerName, $seatFare, $bookingDate, $departureTime, $departureDate, $destinationCity, $busClass);

// Set currency
//        $printer->setCurrency($currency);

// Add items
//        foreach ($items as $item) {
//            $printer->addItem(
//                $item['name'],
//                $item['qty'],
//                $item['price']
//            );
//        }
// Set tax
//        $printer->setTax($tax_percentage);

// Calculate total
            $printer->calculateSubTotal();
            $printer->calculateGrandTotal();

// Set transaction ID
//        $printer->setTransactionID($transaction_id);

// Set logo
// Uncomment the line below if $image_path is defined
//$printer->setLogo($image_path);

// Set QR code
//        $printer->setQRcode([
//            'tid' => $transaction_id,
//        ]);

// Print receipt
//        $printer->printReceipt();
        }
    }
}
if (!function_exists('codeImage')) {
    function codeImage($data, $QrNmae)
    {
        $data = file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=$data");
        $nameToStore = $QrNmae . ".png";
        file_put_contents(public_path("Customers/Qrs/$nameToStore"), $data);
        return $nameToStore;
    }
}
