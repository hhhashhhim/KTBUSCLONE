<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
use App\Models\Customer;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Route\Route;
use App\Models\Route\RouteFare;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use stdClass;
use Illuminate\Http\Response;
use TCPDF;


class AuthController extends Controller
{

    public function index(Request $request)
    {
//        $ticket = Customer::with('tickets','tickets.schedule','tickets.customer','tickets.company','tickets.destination_city','tickets.departure_city','tickets.addedBy',)->where('id', 1)->first();

        $ticket = Ticket::with('schedule', 'customer', 'company', 'destination_city', 'departure_city', 'addedBy')->where('customer_id', 1)->get();
//         $qr = QrCode::size(100)->format('png')->style('round')->generate('1 - Test User - 03157053558 - 3320216516699 - 11/22/2022 11:32:38 AM');

//        $image = \QrCode::format('png')->size(100)->errorCorrection('H')->generate('1 - Test User - 03157053558 - 3320216516699 - 11/22/2022 11:32:38 AM');
////        $output_file = '/img/qr-code/img-' . time() . '.png';
//        $imageProfile = pathinfo($image->getClientOriginalName() , PATHINFO_FILENAME) . "_" . time() . '.' .  $image->extension();
//         $image->move(public_path('uploads/QR/Booking'), $imageProfile);
// return 'Done';
//            $data = [
//                'title' => 'Ticket',
//                'date' => date('m/d/Y')
//            ];
//
//            $pdf = PDF::loadView('pdf/pdf', ['data' => $ticket]);
//
//            $output = $pdf->output();
//
//        return new Response($output, 200, [
//            'Content-Type' => 'application/pdf',
//        ]);


//        Ticket::with('schedule', 'customer', 'company', 'destination_city', 'departure_city')->where('company_id',1)->whereIn('seat_no', [1,2,3])->where('schedule_id', 1)->where('date','2022-11-24')->get()->groupBy('seat_no')->dd();

        if (!Auth::check() && $request->path() != "login") {
            return redirect('/login');
        }
        if (Auth::check() && $request->path() == "login") {
            return redirect('/');
        }
        return view('admin.index');
    }


    public function checkForPermission($user, $request)
    {
        $permission = collect($user->role
            ->permissions);
        return $permission->where('name', $request->path())
            ->where('read', true)
            ->first();
    }

    public function logout()
    {
        Auth::logout();
        return redirect("/");
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // return $request;
        $attempt = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
        if ($attempt) {
            return response()->json([
                'message' => 'You are Logged In Successfully',
                'success' => true,
            ]);
        } else {
            return response()->json([
                'message' => 'Invalid Credentials !!!!',
                'success' => false,
            ], 401);
        }
    }

    public function doubleCheck(Request $request)
    {

        $request->validate([
            'password' => 'required',
        ]);
        if (Hash::check($request->password, auth()->user()->password)) {
            return response()->json([], 200);
        } else {
            return response()->json([], 403);
        }
    }
}
