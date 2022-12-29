<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingCancel;
use App\Models\Booking\RescheduleExtraAmount;
use App\Models\Booking\TicketAdvancedBooked;
use App\Models\Booking\TicketELT;
use App\Models\Booking\TicketIsPartial;
use App\Models\Booking\TicketReschedule;
use App\Models\Booking\TicketsOverIssue;
use App\Models\City;
use App\Models\Customer;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Setting\Tickets\TicketsTemplate;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $bookings = Ticket::select('schedule_id', 'date', 'schedule_details_id', 'bus_class_id')->with('schedule:id,name', 'scheduleDetail', 'seatClass')->whereDate('date', isset($request->date) ? $request->date : date("Y-m-d"))
            ->where('company_id', $this->company_id)->get()->groupBy(['date', 'schedule_id']);
        $allBooking = [];
        foreach ($bookings as $i => $singleBooking) {
            $bookingWithDetails = $singleBooking->map(function ($booking) use ($i) {
                $booking[0]->count = $booking->count();
                $booking[0]->date = $i;
                return $booking[0];
            });
            $allBooking[] = $bookingWithDetails->first();
        }
        return $allBooking;
    }

    public function store(Request $request)
    {
        // this is for get actual schedule date
        $detail = ScheduleDetail::where("departure_id", $request->departureCity)
            ->where("destination_id", $request->destinationCity)
            ->where('schedule_id', $request->schedule)
            ->where('departure_date', $request->date)
            ->where('company_id', $this->company_id)
            ->first();

        $allTicket = [];
        if (isset($request->flag) && $request->flag == 1) {
            $allTicket[] = updateAdvancedSeat($request, $this->company_id);
        } else {
            $schedule = Schedule::where('id', $request->schedule)->where('company_id', $this->company_id)->select('id', 'fare_class_id', 'route_id', 'bus_class_id')->with('bus_class:id,seat_map', 'route:id,name', 'route.fares:id,route_id,departure_city_id,destination_city_id')->first();
            $departure_city_id = $schedule->route->fares->first()->departure_city_id;
            $destination_city_id = $schedule->route->fares->last()->destination_city_id;
            $isPartial = 0;
            if ($request->departureCity != $departure_city_id || $request->destinationCity != $destination_city_id) {
                $isPartial = 1;
            }
            $cnicFormat = str_replace('-', '', $request->customerCNIC);
            $phoneFormat = str_replace('-', '', $request->contact);

            $customer = Customer::where('cnic', $cnicFormat)->first();

            // Fare Fetching About the Schedule
            if (!$customer) {
                $customer = Customer::create([
                    'company_id' => $this->company_id,
                    'added_by' => Auth::user()->id,
                    'name' => $request->customerName,
                    'cnic' => is_null($request->customerCNIC) ? 0 : $cnicFormat,
                    'contact' => $phoneFormat,
                ]);
            }

            // Getting Already Booked Tickets

            if ($request->date == date('Y-m-d')) {
                $bookingNo = Ticket::where('date', $request->date)->latest()->first()->booking_no ?? 0;
                ++$bookingNo;
            } else {
                $bookingNo = Ticket::where('date', $request->date)->latest()->first()->booking_no ?? 0;
                ++$bookingNo;
            }
            $scheduleDetail = ScheduleDetail::where([
                'company_id' => $this->company_id,
                'departure_date' => $request->date,
                'departure_id' => $request->departureCity,
                'destination_id' => $request->destinationCity,
                'schedule_id' => $schedule->id,
            ])->first();
            $allTicket = [];
            foreach ($request->selectedSeats as $i => $seat) {
                $ticket = Ticket::create([
                    'company_id' => $this->company_id,
                    'departure_city_id' => $request->departureCity,
                    'destination_city_id' => $request->destinationCity,
                    'seat_no' => $seat,
                    'bus_class_id' => $request->selectedSeatsClass[$i],
                    'seat_fare' => $request->selectedSeatsFare[$i],
                    'is_partial' => $isPartial,
                    'booking_no' => $bookingNo,
                    'schedule_date' => $detail->schedule_date,
                    'date' => $request->date,
                    'customer_id' => $customer->id,
                    'schedule_id' => $schedule->id,
                    'schedule_details_id' => $scheduleDetail->id,
                    'remarks' => $request->remarks,
                    'gender' => $request->gender,
                    'type' => $request->type,
                    'added_by' => Auth::user()->id,
                    'discount' => $request->discount,
                ]);
                if ($isPartial == 1) {
                    TicketIsPartial::create([
                        'company_id' => $this->company_id,
                        'departure_city_id' => $ticket->departure_city_id,
                        'destination_city_id' => $ticket->destination_city_id,
                        'ticket_id' => $ticket->id,
                        'seat_no' => $ticket->seat_no,
                        'seat_fare' => $ticket->seat_fare,
                        'booking_no' => $ticket->booking_no,
                        'date' => $ticket->date,
                        'customer_id' => $ticket->customer_id,
                        'schedule_id' => $ticket->schedule_id,
                        'gender' => $ticket->gender,
                        'type' => $ticket->type,
                        'added_by' => Auth::user()->id,
                    ]);
                }

                if ($request->type == 'advance booking') {
                    TicketAdvancedBooked::create([
                        'company_id' => $this->company_id,
                        'departure_city_id' => $ticket->departure_city_id,
                        'destination_city_id' => $ticket->destination_city_id,
                        'ticket_id' => $ticket->id,
                        'seat_no' => $ticket->seat_no,
                        'seat_fare' => $ticket->seat_fare,
                        'booking_no' => $ticket->booking_no,
                        'date' => $ticket->date,
                        'customer_id' => $ticket->customer_id,
                        'schedule_id' => $ticket->schedule_id,
                        'gender' => $ticket->gender,
                        'type' => $ticket->type,
                        'added_by' => Auth::user()->id,
                    ]);
                }
                $allTicket[] = $ticket->id;
            }
            // printTicket($allTicket, $this->company_id);
        }
        return [
            'data' => implode('-', $allTicket),
            'ticket' => Ticket::where('company_id', $this->company_id)->whereIn('id', $allTicket)->get(),
        ];
    }


    public function reschedule(Request $request)
    {
        $ticket = $request->dataAll;
        if ($request->existingDate == $request->rescheduleDate) {
            $bookingNo = Ticket::where('date', $request->existingDate)->latest()->first()->booking_no ?? 0;
            ++$bookingNo;
        } else {
            $bookingNo = Ticket::where('date', $request->rescheduleDate)->latest()->first()->booking_no ?? 0;
            ++$bookingNo;
        }
        $scheduleDetail = ScheduleDetail::where([
            'company_id' => $this->company_id,
            'departure_date' => $ticket['date'],
            'departure_id' => $ticket['departure_city_id'],
            'destination_id' => $ticket['destination_city_id'],
            'schedule_id' => $ticket['schedule_id'],
        ])->first();
        if ($request->selected_seatFare != $request->dataAll['seat_fare']) {
            RescheduleExtraAmount::create([
                'company_id' => $this->company_id,
                'old_ticket_id' => $request->dataAll['id'],
                'old_seat_no' => $ticket['seat_no'],
                'new_seat_no' => $request->selected_seatNo,
                'old_seat_class' => $ticket['bus_class_id'],
                'new_seat_class' => $request->selected_seatClass,
                'old_seat_fare' => $ticket['seat_fare'],
                'new_seat_fare' => $request->selected_seatFare,
                'type' => priceDiff($ticket['seat_fare'], $request->selected_seatFare)['type'],
                'diff_amount' => priceDiff($ticket['seat_fare'], $request->selected_seatFare)['diff'],
                'old_departure_city_id' => $ticket['departure_city_id'],
                'new_departure_city_id' => $request->dataDepartureCity,
                'old_destination_city_id' => $ticket['destination_city_id'],
                'new_destination_city_id' => $request->rescheduleDestinationCity,
                'old_schedule_id' => $ticket['schedule_id'],
                'new_schedule_id' => $request->rescheduleSchedule,
                'old_booking_date' => $ticket['date'],
                'new_booking_date' => $request->rescheduleDate,
            ]);
        }
        TicketReschedule::create([
            'company_id' => $this->company_id,
            'schedule_id' => $ticket['schedule_id'],
            'reSchedule_id' => $request->rescheduleSchedule,
            'customer_id' => $ticket['customer_id'],
            'date' => $ticket['date'],
            'reschedule_date' => $request->rescheduleDate,
            'departure_city_id' => $ticket['departure_city_id'],
            'reschedule_departure_city_id' => $request->dataDepartureCity,
            'reschedule_destination_city_id' => $request->rescheduleDestinationCity,
            'destination_city_id' => $ticket['destination_city_id'],
            'reason' => $request->reason,
            'added_by' => Auth::user()->id,
        ]);
        Ticket::create([
            'company_id' => $this->company_id,
            'departure_city_id' => $request->dataDepartureCity,
            'destination_city_id' => $request->rescheduleDestinationCity,
            'seat_no' => $ticket['seat_no'],
            'bus_class_id' => $ticket['bus_class_id'],
            'seat_fare' => $ticket['seat_fare'],
            'is_partial' => $ticket['is_partial'],
            'booking_no' => $bookingNo,
            'date' => $request->rescheduleDate,
            'schedule_details_id' => $scheduleDetail->id,
            'customer_id' => $request->dataCustomer,
            'schedule_id' => $request->rescheduleSchedule,
            'remarks' => $ticket['remarks'],
            'gender' => $ticket['gender'],
            'type' => $ticket['type'],
            'added_by' => Auth::user()->id,
            'discount' => $ticket['discount'],
        ]);
        $old_ticket = Ticket::where('company_id', $this->company_id)->where('id', $ticket['id'])->first();
        $old_ticket->update([
            'type' => 'reschedule'
        ]);
        return $old_ticket->delete();
    }

    public function deleteBooking(Request $request)
    {
        return Ticket::find($request->id)->delete();
    }

    public function fetchSpecificSchedule(Request $request)
    {
        if (!$request->date) {
            return "Date is Required";
        }
        $allSchedules = ScheduleDetail::with('schedule')->where(['departure_id' => $request->departure_city_id, 'destination_id' => $request->destination_city_id, 'departure_date' => $request->date])->get();
        foreach ($allSchedules as $key => $single) {
            $single->departure_date = date("m/d/Y", strtotime($single->departure_date));
            $single->departure_time = date("h:i A", strtotime($single->departure_time));
        }
        return $allSchedules;

    }

    public function fetchSpecificDestination(Request $request)
    {
        $depart_city = RouteFare::where('departure_city_id', $request->id)->where('company_id', $this->company_id)->pluck('destination_city_id')->toArray();
        return City::whereIn('id', array_unique($depart_city))->where('company_id', $this->company_id)->get(['id', 'name']);
    }

    public function fetchSpecificOverIssueSeat(Request $request)
    {
        $ticket = Ticket::where(["company_id" => $this->company_id, "date" => $request->date, "seat_no" => $request->seat_no, "schedule_id" => $request->schedule_id, "destination_city_id" => $request->destination_id, "departure_city_id" => $request->departure_id])->select('booking_no', 'customer_id', 'gender', 'is_partial', 'type', 'remarks')->first();
        $customer = Customer::where(["company_id" => $this->company_id, "id" => $ticket->customer_id])->first();
        $ticket = json_decode(json_encode($ticket), true);
        $fare = ["fare" => $request->seat_fare, "seat_no" => $request->seat_no];
        $ticket = array_merge($ticket, $fare);
        $customer = json_decode(json_encode($customer), true);
        return [
            'ticket' => $ticket,
            'customer' => $customer,
        ];
    }

    public function overIssueAddNew(Request $request)
    {
        $ticket = Ticket::where([
            'company_id' => $this->company_id,
            'date' => $request->date,
            'schedule_id' => $request->schedule_id,
            'customer_id' => $request->customer_id,
            'departure_city_id' => $request->departure_id,
            'destination_city_id' => $request->destination_id,
            'seat_no' => $request->seat_no,

        ])->first();
        $ticket->update([
            'type' => 'over-issue',
        ]);
        TicketsOverIssue::create([
            'company_id' => $this->company_id,
            'ticket_id' => $ticket->id,
            'percentage' => $request->percentage,
            'reason' => $request->remarks,
            'added_by' => Auth::user()->id,
        ]);
        return $ticket->delete();
    }

    public function getCnic(Request $request)
    {
        if ($request->status == 'addFormCNIC') {
            $cnicFormat = str_replace('-', '', $request['cnicNumber']);
            return Customer::where('company_id', $this->company_id)->where('cnic', $cnicFormat)->first();
        }
        if ($request->status == 'addFormContact') {
            $phoneFormat = str_replace('-', '', $request['phoneNumber']);
            return Customer::where('company_id', $this->company_id)->where('contact', $phoneFormat)->first();
        }
    }

    public function detailTicket(Request $request)
    {
        return Ticket::with('addedBy', 'customer')->where('company_id', $this->company_id)
            ->whereDate('date', $request->date)
            ->where('schedule_id', $request->schedule_id)
            ->get();
//        $allBooking = $bookings->map(function($booking){
//            $booking[0]->count=$booking->count();
//            return $booking[0];
//        });
//        return $allBooking;
    }

    public function advanceData(Request $request)
    {
        return Ticket::with('scheduleDetail', 'schedule', 'customer', 'company', 'destination_city', 'departure_city', 'seatClass')->where('company_id', $this->company_id)->whereIn('seat_no', $request->seatNO)->where('schedule_id', $request->scheduleId)->where('date', $request->date)->get()->groupBy('seat_no');
    }

    public function bookingElt(Request $request)
    {
        $ticket = Ticket::where([
            'company_id' => $this->company_id,
            'date' => $request->date,
            'schedule_id' => $request->schedule_id,
            'customer_id' => $request->customer_id,
            'departure_city_id' => $request->departure_id,
            'destination_city_id' => $request->destination_id,
            'seat_no' => $request->seat_no,
        ])->first();
        $elt = TicketELT::create([
            'company_id' => $this->company_id,
            'ticket_id' => $ticket->id,
            'customer_id' => $request->customer_id,
            'departure_city' => $request->departure_id,
            'destination_city' => $request->destination_id,
            'schedule_id' => $request->schedule_id,
            'seat_no' => $request->seat_no,
            'date' => $request->date,
            'elt_price' => $request->totalPrice,
            'seat_fare' => $request->singleFare,
            'elt_weight' => $request->eltWeight,
            'elt_description' => $request->eltDescription,
            'added_by' => Auth::user()->id,
        ]);
        return TicketELT::with('addedBy', 'departure', 'destination', 'departure', 'updated_by', 'company', 'ticket', 'customer', 'schedule')->where('id', $elt->id)->first();
    }

    public function cancelingBooking(Request $request)
    {
//        dd($request->all());
        $ticket = Ticket::where([
            'company_id' => $this->company_id,
            'date' => $request->date,
            'schedule_id' => $request->schedule_id,
            'customer_id' => $request->customer_id,
            'departure_city_id' => $request->departure_id,
            'destination_city_id' => $request->destination_id,
            'seat_no' => $request->seat_no,

        ])->first();
        $ticket->update([
            'type' => 'canceled',
        ]);
        BookingCancel::create([
            'company_id' => $this->company_id,
            'ticket_id' => $ticket->id,
            'percentage' => $request->percentage,
            'reason' => $request->remarks,
            'added_by' => Auth::user()->id,
        ]);
        return $ticket->delete();
    }

    public function pdf($id)
    {
        $ticket = Ticket::with('schedule.bus_class', 'customer', 'company', 'destination_city', 'departure_city', 'addedBy')->whereIn('id', explode('-', $id))->get();
        $format = TicketsTemplate::where('company_id', 1)->where('status', 1)->first();
        $pdf = PDF::loadView('pdf/pdf', ['data' => $ticket, 'data_terms' => $format, 'duplicate' => 0]);
        $output = $pdf->output();
        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function duplicatePdf($id)
    {
        $ticket = Ticket::with('schedule.bus_class', 'customer', 'company', 'destination_city', 'departure_city', 'addedBy')->whereIn('id', explode('-', $id))->get();
        $format = TicketsTemplate::where('company_id', 1)->where('status', 1)->first();
        $pdf = PDF::loadView('pdf/pdf', ['data' => $ticket, 'data_terms' => $format, 'duplicate' => 1]);
        $output = $pdf->output();
        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function getPassengersList(Request $request)
    {
        $customers_id = Ticket::where([
            'company_id' => $this->company_id,
            'schedule_id' => $request->schedule_id,
            'departure_city_id' => $request->departure_city_id,
            'destination_city_id' => $request->destination_city_id,
            'date' => $request->date,
        ])->pluck('customer_id')->toArray();
        return implode('-', array_unique($customers_id));
    }

    public function passengerListPdf(Request $request)
    {
        $customers_data = Ticket::with('customer', 'schedule.route', 'schedule.bus_class', 'destination_city', 'departure_city')->where([
            'company_id' => $this->company_id,
            'schedule_id' => $request->schedule_id,
            'schedule_date' => $request->date,
            'type' => 'booked',
        ])->get()->groupBy('schedule_id');
        $format = TicketsTemplate::where('company_id', $this->company_id)->where('status', 1)->first();
        $format->countPassenger = count($customers_data[$request->schedule_id]);
        return view('pdf/passengerList', ['data' => $customers_data, 'data_terms' => $format]);
        //        $pdf = PDF::loadView('pdf/passengerList', ['data' => $customers_data, 'data_terms'=> $format]);
//            $output = $pdf->output();
//        return new Response($output, 200, [
//            'Content-Type' => 'application/pdf',
//            ]);
    }

}
