<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CityToCity;
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
use stdClass;
use Illuminate\Http\Response;
use TCPDF;


class AuthController extends Controller
{

    public function index(Request $request)
    {
//        //    CAsh Closing start
//
//        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
//     // set document information
//     $pdf->SetCreator(PDF_CREATOR);
//     $pdf->SetAuthor('GCH');
//     $pdf->SetTitle('Journal Voucher');
//     $pdf->SetSubject('Accounts');
//
//
//     // set default header data
//     // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);
//
//     // set header and footer fonts
//     $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
//     $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
//
//     // set default monospaced font
//     $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
//
//     // set margins
//     $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
//     $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
//     $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
//
//     // set auto page breaks
//     $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
//
//     // set image scale factor
//     $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//
//     // add a page
//     $pdf->AddPage();
//
//         $pdf->setJPEGQuality(75);
//     $pdf->SetFillColor(255, 255, 127);
//     $pdf->Ln(-5);
//
//     $pdf->SetFont('times', 'B', 18);
//
//     $pdf->Cell(0, 0, 'KAINAT TRAVELS', 0, 1, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->SetFont('times', '', 16);
//
//     $pdf->Cell(0, 0, 'Main Pirwadhai Mor Peshawar ', 0, 1, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->Cell(0, 0, 'Road Rawalpindi', 0, 1, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->SetFont('times', 'B', 16);
//     $pdf->Ln(2);
//     $pdf->Cell(55, 0, ' ', 0, 0, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->Cell(30, 0, 'UAN(24/7) : ', 0, 0, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->SetFont('times', '', 15);
//     $pdf->Cell(20, 0, ' 03-111-777-333', 0, 1, 'L', 0, '', 0,false, 'T', 'M');
//
//     $pdf->Ln(2);
//     $pdf->Cell(55, 0, ' ', 0, 0, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->SetFont('times', 'B', 16);
//     $pdf->Cell(30, 0, 'Phone :', 0, 0, 'C', 0, '', 0,false, 'T', 'M');
//     $pdf->SetFont('times', '', 15);
//     $pdf->Cell(20, 0, ' 03108886286', 0, 1, 'L', 0, '', 0,false, 'T', 'M');
//
//     $pdf->Ln(2);
//     $pdf->Cell(20, 0, ' 03108886286', 0, 1, 'L', 0, '', 0,false, 'T', 'M');
//
//
//
//
//
//
//
//        $pdf->Output('closing_report.pdf', 'I');
//
//    }

//            $data = [
//                'title' => 'Ticket',
//                'date' => date('m/d/Y')
//            ];
//
//            $pdf = PDF::loadView('pdf/pdf', $data);
//
//            $output = $pdf->output();
//
//        return new Response($output, 200, [
//            'Content-Type' => 'application/pdf',
//        ]);

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

include('public/tcpdf/tcpdf.php');

class MYPDF extends TCPDF
{

    //Page header

    public function Header()
    {

        // // Logo

        // $image_file = K_PATH_IMAGES.'';
        // $this->Image($image_file, 5, 10, 15, '', '', '', 'T', false, 0,'R', false, false, 0, false, false, false);
        // // Set font
        // $this->SetFont('helvetica', '', 18);
        // $this->Ln(15);
        // // Title
        // $this->SetFont('helvetica', '', 18);
        // $this->Cell(0, 0, ' GATWALA COMMERCIAL HUB ', 0, false, 'L', 0, '', 0, false, 'M', 'M');
        // $this->Cell(0, 0, ' Image ', 0, false, 'R', 0, '', 0, false, 'M', 'M');
        // $this->Ln(4);
        // $tbl = <<< EOD
        // <hr>
        // EOD;
        // $this->writeHTML($tbl, true, false, false, false, '');
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom

        // $this->Ln(-40);

        // // Set font
        // $this->SetFont('helvetica', '', 8);
        // // Page number
        // $line = '_____________________';
        // $buyerSign='CEO';
        // $CompanyName='Cheque Prepaid & Documents';
        // $buyerName='Zeeshan Shah';
        // $OwnerName='Checked By Ail Asadullah';

        // $this->SetFont('', 'B', 10);
        // $this->Cell(0, 1,$line, 0, false, 'L', 0, '', 0, false, 'T',);
        // $this->Cell(0, 1, $line, 0, false, 'R', 0, '', 0, false, 'T');
        // $this->Ln();
        // $this->Ln();
        // $this->SetFont('', '', 12);
        // $this->Cell(0, 1,$buyerSign, 0, false, 'L', 0, '', 0, false, 'T',);
        // $this->Cell(0, 1, $CompanyName, 0, false, 'R', 0, '', 0, false, 'T');
        // $this->Ln();
        // $this->Cell(0, 1,'Name :'.$buyerName, 0, false, 'L', 0, '', 0, false, 'T',);
        // $this->Cell(0, 1,$OwnerName, 0, false, 'R', 0, '', 0, false, 'T');
        // $this->Ln();


    }

}
