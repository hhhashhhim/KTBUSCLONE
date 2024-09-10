<?php

namespace App\Http\Controllers\Account\Pdf;

use App\Models\Account\Account;
use App\Models\Account\Bank;
use App\Models\Account\AccountGroup;
use App\Models\Account\AccountTransaction;
use App\Models\Company;
use App\Models\Account\BankTransaction;
use App\Models\Account\AccountReceipt;
use App\Models\Account\CashTransaction;
use App\Models\Terminal;
use App\Models\Account\AccountHead;
use TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class TransactionPdfController extends BaseController
{
    public function transactionPdf(Request $request)
    {   
        $transactions = AccountTransaction::
        where(['type'=>$request->type,"company_id"=>Auth::user()->company_id,"document_id"=>$request->id])
        ->orderBy("id",'ASC')
        ->with("account_head.level_four:id,name,code","account_head.level_three:id,name,code","account_head.level_two:id,name,code","account_head.level_one:id,name,code")
        ->with("added_by_name:id,name","updated_by_name:id,name","approved_by_name:id,name")
        ->with("other_head_name:id,name","terminal:id,name")
        ->get();

        $company_name = Company::where("id",Auth::user()->company_id)->first()->name ?? "Green Advisor";

        if($transactions->count() > 0)
        {
            $amount   = $transactions->sum('credit');
            $dateTime = $transactions[0]->created_at;
            $heading  = '';
    
            if($request->type == 'BP')
            {
                $vocher = $transactions->where('debit', 0);
                $heading = "Bank Payment Voucher";
                $particular = $transactions->where('debit', 0)->first()->other_head_name->name;
            }
            else if ($request->type == 'BR') {
                $vocher = $transactions->where('credit', 0);
                $heading = "Bank Receipt Voucher";
                $particular = $transactions->where('credit', 0)->first()->other_head_name->name;
            }
            elseif ($request->type == 'CP') {
                $vocher = $transactions->where('debit', 0);
                $heading = "Cash Payment Voucher";
                $particular = $transactions->where('debit', 0)->first()->other_head_name->name;
            }
            elseif ($request->type == 'CR') {
                $vocher = $transactions->where('credit', 0);
                $heading = "Cash Receipt Voucher";
                $particular = $transactions->where('credit', 0)->first()->other_head_name->name;
            }
            elseif ($request->type == 'JV') 
            {
                $vocher = $transactions->where('credit', 0);
                $heading = "Journal Voucher";
                if($transactions->where('credit', 0)->first()->other_head_name ) {
                    $particular = 'N/A';
                }else{
                    $particular = 'N/A';
                }
            }
        }
        else
        {
            return redirect()->back()->with('danger', 'Data not Found');
        }
    
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
            $pdf->heading = $heading;
            // set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('GA');
            $pdf->SetTitle('Jornal Entries');
    
            // set default header data
            // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);
    
            // set header and footer fonts
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
            // set margins
            $pdf->SetMargins(4, PDF_MARGIN_TOP, 5);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
            // set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
            // set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
            // set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
                require_once(dirname(__FILE__) . '/lang/eng.php');
                $pdf->setLanguageArray($l);
            }
            // ---------------------------------------------------------
    
    
    
            // set font
            $pdf->setPrintFooter(false);
            // add a page
            $pdf->AddPage('P', 'A4');
            // set color for background
    
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            // set color for background
            $pdf->SetFillColor(255, 255, 0);
            $pdf->SetFont('times', 'B', 16);
            $pdf->Ln(-18);
            $pdf->Cell(0, 0, strtoupper($company_name), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->SetFont('times', 'B', 13);
            $pdf->Cell(0, 0,strtoupper($transactions[0]->terminal->name??"General"), 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->SetFont('times', '', 12);
            $pdf->Ln(8);
            $pdf->Cell(0, 0, $heading, 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Ln();
            $pdf->Ln();
    
            $pdf->SetFont('times', '', 11);
            // ---------------------------------------------------------
            
            $pdf->MultiCell(130, 0, 'Particular  : ' . strtoupper( $particular ), 'B', 'L', 0, 0, '', '', true, 0, false, true, 13, 'T');
            
            $pdf->MultiCell(10, 5, '', 0, 'R', 0, 0, '', '', true, 0, false, true, 5, 'M');
            $pdf->MultiCell(60, 5, 'Amount : ' . number_format($amount, 2, ".", ','), 'B', 'L', 0, 1, '', '', true, 0, false, true, 5, 'T');
    
            $pdf->MultiCell(130, 7, 'Voucher # : ' . $request->type.'-'.$request->id, 'B', 'L', 0, 0, '', '', true, 0, false, true, 7, 'B');
            $pdf->MultiCell(10, 5, '', 0, 'R', 0, 0, '', '', true, 0, false, true, 5, 'M');
    
    
            $pdf->MultiCell(60, 7, 'Dated :  ' . date("d-m-Y", strtotime($dateTime)), 'B', 'L', 0, 1, '', '', true, 0, false, true, 7, 'B');
            $reference =  $transactions[0]->posting_type . '-' . $transactions[0]->posting_id;
            $pdf->MultiCell(130, 0, 'Reference # : ' . strtoupper($reference), 'B', 'L', 0, $request->type == 'BP' || $request->type == 'BR' ? 0 : 1, '', '', true, 0, false, true, 7, 'B');
            $pdf->MultiCell(10, 5, '', 0, 'R', 0, 0, '', '', true, 0, false, true, 5, 'M');
       
            if ($request->type == 'BP' || $request->type == 'BR' ) { 
                
                $pdf->MultiCell(60, 0, 'Cheque # '. $transactions[0]->cheque . '', 'B', 'L', 0, 1, '', '', true, 0, false, true, 7, 'B');
                
            }
           
            $pdf->Ln(5);
            $pdf->SetFont('times', 'B', 10);
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
            $pdf->MultiCell(8, 10, 'Sr No', 'TLR', 'L', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $pdf->MultiCell(20, 10, 'Code', 'TR', 'L', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $pdf->MultiCell(70, 10, 'Account Title', 'TR', 'L', 0, 0, '', '', true, 0, false, true, 10, 'M');
            //$pdf->MultiCell(15, 10, 'Account Code', 'TR', 'L', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $pdf->MultiCell(70, 10, 'Narration', 'TR', 'L', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $pdf->MultiCell(16, 10, 'Debit', 'TR', 'R', 0, 0, '', '', true, 0, false, true, 10, 'M');
            $pdf->MultiCell(16, 10, 'Credit', 'TR', 'R', 0, 1, '', '', true, 0, false, true, 10, 'M');
    
            $narration_length = strlen($transactions[0]->narration);
            if ($narration_length > 50) {
                $narration_length = 18;
            } else {
                $narration_length = 12;
            }
           
            foreach ($transactions as $i => $transaction) {
                // try{
                    $pdf->SetFont('times', '', 9);
                    $pdf->MultiCell(8, $narration_length, $i + 1, 1, 'L', 0, 0, '', '', true, 0, false, true, $narration_length, 'M');
                    
                    $head_name = strtoupper($transaction->account_head->name);
                    $group = $transaction->account_head->level_three->name;
                    $code = $transaction->account_head->level_one->code.'-'.$transaction->account_head->level_two->code.'-'.$transaction->account_head->level_three->code.'-'.$transaction->account_head->level_four->code.'-'.$transaction->account_head->code;
                    $pdf->MultiCell(20, $narration_length,  $code, 'TBR', 'L', 0, 0, '', '', true, 0, false, true, $narration_length, 'M');
        
                    $pdf->MultiCell(70, $narration_length, $head_name . ' - ( ' . $group . ' )', 'TBR', 'L', 0, 0, '', '', true, 0, false, true, $narration_length, 'M');
                    //$pdf->MultiCell(15, $narration_length, $transaction->head_name->code, 'TBR', 'L', 0, 0, '', '', true, 0, false, true, $narration_length, 'M');
                    $pdf->MultiCell(70, $narration_length,strtoupper( $transaction->narration ), 'TBR', 'L', 0, 0, '', '', true, 0, false, true, $narration_length, 'M');
                    $pdf->MultiCell(16, $narration_length, number_format($transaction->debit, 2), 'TBR', 'R', 0, 0, '', '', true, 0, false, true, $narration_length, 'M');
                    $pdf->MultiCell(16, $narration_length, number_format($transaction->credit, 2 ), 'TBR', 'R', 0, 1, '', '', true, 0, false, true, $narration_length, 'M');
                // }catch( Exception $e){
                    // return $transaction;
                // }
            }
            $pdf->MultiCell(8, 12, '', 1, 'L', 0, 0, '', '', true, 0, false, true, 12, 'M');
            $pdf->MultiCell(20, 12, '', 'TBR', 'L', 0, 0, '', '', true, 0, false, true, 0, 'M');
            $pdf->MultiCell(70, 12, '', 'TBR', 'L', 0, 0, '', '', true, 0, false, true, 12, 'M');
            $pdf->MultiCell(70, 12, 'Total : ', 'TBR', 'R', 0, 0, '', '', true, 0, false, true, 12, 'M');
            $pdf->MultiCell(16, 12, number_format($transactions->sum('debit') , 2), 'TBR', 'R', 0, 0, '', '', true, 0, false, true, 12, 'M');
            $pdf->MultiCell(16, 12, number_format($transactions->sum('credit'), 2), 'TBR', 'R', 0, 1, '', '', true, 0, false, true, 12, 'M');
    
            $pdf->Ln(5);
            $pdf->SetFont('times', '', 12);
            $Amount = $transactions->sum('debit');
    
            // $f = new NumberFormatter("PKR", NumberFormatter::SPELLOUT);
    
            $date_now = date('d-M-Y h:i A', strtotime(now()));
    
            $pdf->Ln(15);
    
            $pdf->SetFont('times', '', 11);
          
            $pdf->Cell(30, 0, '__________________', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(15, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, $transactions[0]->added_by_name->name, 'B', 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(15, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, ' __________________', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, '__________________', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, $transactions[0]->approved_by_name->name ?? '', 'B', 1, 'C', 0, '', 0, false, 'T', 'M');
    
            $pdf->Cell(30, 0, 'Received By ', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(15, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, 'Prepared By', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(15, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, 'Checked By', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, 'Finance Manager', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(30, 0, 'Approved By', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
    
            $pdf->Ln(10);
            $pdf->SetFont('times', 'B', 9);
            $status = $transactions[0]->approved ? "Approved" : "Pending";
            $pdf->Cell(0, 0, 'Printed By : ' . Auth::user()->name . ' || Dated : ' . $date_now . ' || Status : ' . $status, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
            $pdf->SetFont('times', '', 8);
            $pdf->Ln();
            $pdf->Cell(0, 0,'"Errors and omissions excepted" (E&OE)', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
    
            return $pdf->Output('voucher.pdf', 'I');
    }

    public function receiptPdf(Request $request)
    {
        $heading = '';
        $payment = AccountTransaction::with('added_by_name:id,name')
        ->where('id', $request->posting_id)
        ->with('account_head:id,name')
        ->with('other_head_name:id,name')
        ->first();
        
        $company_name = Company::where("id",Auth::user()->company_id)->first()->name ?? "Green Advisor";
        $terminal = Terminal::where("id",$request->terminal)->first();
          
        if ($request->type == 'BP') {
            $heading = "Receipt # ". $payment->receipt_id;
            $paid = 'Paid To : ';
            $Instruments = 'Online';
        } else if ($request->type == 'BR') {
            $heading = "Receipt # ". $payment->receipt_id;
            $paid = 'Receipt From : ';
            $Instruments = 'Online';
        } elseif ($request->type == 'CP') {
            $heading = "Receipt # ". $payment->receipt_id;
            $paid = 'Paid To : ';
            $Instruments = 'Cash';
        } else if ($request->type == 'CR') {
            $heading = "Receipt # ". $payment->receipt_id;
            $paid = 'Receipt From : ';
            $Instruments = 'Cash';
        }


        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GCH');
        $pdf->SetTitle('Transaction');


        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 9);

        // add a page
        $pdf->AddPage();
        // set color for background
        $pdf->SetFillColor(255, 255, 127);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setPrintFooter(false);


        // set color for background
        $pdf->SetFillColor(255, 255, 127);
        $pdf->Ln(-4);

        // ---------------------------------------------------------
        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 0, strtoupper($company_name), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        if($terminal)
        {
            $pdf->SetFont('times', '', 10);
            $pdf->Cell(0, 0, 'Terminal : '.strtoupper($terminal->name), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        }
        
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(100, 5, '', 0, 0, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->SetFont('times', '', 9);
        $pdf->Cell(77, 5, 'Customer Copy', 0, 1, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(2);

        $pdf->Ln();

        $pdf->SetFont('times', '', 10);


        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(50, 0, $heading, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');

        $date = date('d-m-Y', strtotime($payment->created_at));

        $pdf->Cell(78, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(30, 0, 'Dated : ' . $date, 'B', 1, 'R', 0, '', 0, false, 'T', 'M');

        $pdf->Ln(3);

        $received_name = $payment->account_head->name;

        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(85, 0, $paid . ' ' . $received_name, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');

        $pdf->Ln();

        $ChequeNo = $payment->cheque;

        $pdf->Ln();
        $pdf->SetFont('times', 'B', 10);

        $pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(15, 0, 'Sr No.', 'TL', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 0, 'Instruments', 'T', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(60, 0, 'Narration', 'T', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 0, 'Cheque #', 'T', 0, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(49, 0, 'Amount ', 'TR', 1, 'R', 0, '', 0, false, 'T', 'M');

        $pdf->SetFont('times', '', 9);
        $Amount = (float)$payment->debit > 0 ? $payment->debit : $payment->credit;
        
        $pdf->Cell(8, 8, '', 0, 0, '', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(15, 8, 1, 'BL', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 8, $Instruments, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(60, 8, $payment->narration, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 8, $ChequeNo, 'B', 0, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(49, 8, (float)$payment->debit > 0 ? number_format($payment->debit) : number_format($payment->credit), 'BR', 1, 'R', 0, '', 0, false, 'T', 'M');
        
        $pdf->Cell(175, 7, 'Total Amount : ' . $Amount, 0, 0, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(8);


        
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(40, 0, 'Amount : ' . number_format($Amount), 0, 1, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        

        $pdf->Ln(3);

        //Close and output PDF document

        $pdf->SetFont('times', '', 9);
        $pdf->Cell(9, 0, '', 0, 1, 'L', 0, '', 0, false, 'T', 'M');
        $received_name = $payment->account_head->name;

        
        $pdf->SetFont('times', 'B', 9);
        $added_by = $payment->added_by_name->name; 
        $pdf->Cell(160, 0, '', 0, 0, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(19, 0, $added_by, 'B', 1, 'C', 0, '', 0, false, 'T', 'M');
        //Close and
        $user_name = Auth::user()->name;
        $date_now = date('d-M-Y h:i A', strtotime(now()));
        $pdf->SetFont('times', '', 9);
        $pdf->Cell(9, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(0, 0, 'Prepared By', 0, 1, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(0, 0,'"Errors and omissions excepted" (E&OE)', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);
        $pdf->SetFont('times', 'B', 9);
        $pdf->Cell(180, 0, 'Printed By : ' . $user_name . ' || ' . $date_now, 0, 0, 'C', 0, '', 0, false, 'T', 'M');


        // *********************** Office Copy
        // add a page
        $pdf->AddPage();
        // set color for background
        $pdf->SetFillColor(255, 255, 127);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setPrintFooter(false);


        // set color for background
        $pdf->SetFillColor(255, 255, 127);
        $pdf->Ln(-4);

        // ---------------------------------------------------------
        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 0, strtoupper($company_name), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        if($terminal)
        {
            $pdf->SetFont('times', '', 10);
            $pdf->Cell(0, 0, 'Terminal : '.strtoupper($terminal->name), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        }
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(100, 5, '', 0, 0, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->SetFont('times', '', 9);
        $pdf->Cell(77, 5, 'Office Copy', 0, 1, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(2);


        $pdf->Ln();

        $pdf->SetFont('times', '', 10);


        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(50, 0, $heading, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');

        $date = date('d-m-Y', strtotime($payment->created_at));

        $pdf->Cell(75, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(30, 0, 'Dated : ' . $date, 'B', 1, 'R', 0, '', 0, false, 'T', 'M');

        $pdf->Ln(3);

        $received_name = $payment->account_head->name;


        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(85, 0, $paid . ' ' . $received_name, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(10, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');

        $pdf->Ln();

        $ChequeNo = $payment->cheque;
        // $Amount = (float)$vocher->sum('debit') > 0 ? $vocher->sum('debit') : $vocher->sum('credit');

        $pdf->Ln();
        $pdf->SetFont('times', 'B', 10);

        // $style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
        // $pdf->Line(50, 40, 50, 68, $style);
        // $pdf->Line(265, 40, 265, 68, $style);
        // //$pdf->Line(50, 40, 50, 70, $style);
        $pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(15, 0, 'Sr No.', 'TL', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 0, 'Instruments', 'T', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(60, 0, 'Narration', 'T', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 0, 'Cheque #', 'T', 0, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(49, 0, 'Amount ', 'TR', 1, 'R', 0, '', 0, false, 'T', 'M');

        $pdf->SetFont('times', '', 9);
        $Amount = (float)$payment->debit > 0 ? $payment->debit : $payment->credit;
        
        $pdf->Cell(8, 8, '', 0, 0, '', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(15, 8, 1, 'BL', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 8, $Instruments, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(60, 8, $payment->narration, 'B', 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(22, 8, $ChequeNo, 'B', 0, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(49, 8, (float)$payment->debit > 0 ? number_format($payment->debit) : number_format($payment->credit), 'BR', 1, 'R', 0, '', 0, false, 'T', 'M');
        
        $pdf->Cell(175, 7, 'Total Amount : ' . $Amount, 0, 0, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(8);

        // $f = new NumberFormatter("PKR", NumberFormatter::SPELLOUT);

        $pdf->SetFont('times', '', 10);
        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(40, 0, 'Amount : ' . number_format($Amount), 0, 1, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(8, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        

        $pdf->Ln(3);

        //Close and output PDF document

        $pdf->SetFont('times', '', 9);
        $pdf->Cell(9, 0, '', 0, 1, 'L', 0, '', 0, false, 'T', 'M');
        $received_name = $payment->account_head->name;

        
        $pdf->SetFont('times', 'B', 9);
        $added_by = $payment->added_by_name->name; 
        $pdf->Cell(160, 0, '', 0, 0, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(19, 0, $added_by, 'B', 1, 'C', 0, '', 0, false, 'T', 'M');
        //Close and
        $user_name = Auth::user()->name;
        $date_now = date('d-M-Y h:i A', strtotime(now()));
        $pdf->SetFont('times', '', 9);
        $pdf->Cell(9, 0, '', 0, 0, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(0, 0, 'Prepared By', 0, 1, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(0, 0,'"Errors and omissions excepted" (E&OE)', 0, 0, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);
        $pdf->SetFont('times', 'B', 9);
        $pdf->Cell(180, 0, 'Printed By : ' . $user_name . ' || ' . $date_now, 0, 0, 'C', 0, '', 0, false, 'T', 'M');

        $pdf->Output('receipt.pdf', 'I');
    }
    
    public function generalLedgerPdf(Request $request)
    {
        $heads = AccountHead::where(["company_id"=>Auth::user()->company_id,"group_id"=>$request->level_four])->pluck('id');
        
        // to get name
        $level_four = AccountGroup::where("id",$request->level_four)->first();
        $terminal = Terminal::where("id",$request->terminal)->first();

        // to get sum of previous debits
        $previous_debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of previous credits
        $previous_credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // to get sum of debits
        $debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of credits
        $credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->whereIn("account_head_id",$heads)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->with("account_head:id,name,code","other_head_name:id,name,code","terminal:id,name")
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->get();

        $general_ledgers = (object)[];
        $general_ledgers->terminal = $terminal ? $terminal->name : null;
        $general_ledgers->debits = $debits;
        $general_ledgers->credits = $credits;
        $general_ledgers->record = $record;
        $general_ledgers->level_four = $level_four;
        $general_ledgers->from = $request->from;
        $general_ledgers->to = $request->to;
        $general_ledgers->previous_debits = $previous_debits;
        $general_ledgers->previous_credits = $previous_credits;
        // Initialize TCPDF object
       
        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GA');
        $pdf->SetTitle('Transaction');


        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(3, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 9);
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'b', 15);
        $pdf->Ln(-10);
        // Title
        $pdf->Cell(0, 0, strtoupper('General Ledger'), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        // Date range and level
        $pdf->Cell(0, 7, 'Account : ' . $level_four->name, 0, 1);
        $pdf->Cell(0, 7, 'From : ' . $request->from, 0, 1);
        $pdf->Cell(0, 7, 'To : ' . $request->to, 0, 1);
        if ($terminal) {
            $pdf->Cell(0, 7, 'Terminal : ' . strtoupper($terminal->name), 0, 1);
        }
        // Add some line break
        $pdf->Ln(10);

        // Table header
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(18, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(15, 10, 'V #', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Account', 1, 0, 'C', true);
        $pdf->Cell(49, 10, 'Narration', 1, 0, 'C', true);
        $pdf->Cell(24, 10, 'Debit', 1, 0, 'C', true);
        $pdf->Cell(24, 10, 'Credit', 1, 0, 'C', true);
        $pdf->Cell(24, 10, 'Balance', 1, 1, 'C', true);

        // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetTextColor(255, 0, 0); // RGB for red
        $pdf->Cell(184, 10, 'Opening', 1, 0, 'C', true);
        $balance = $previous_credits - $previous_debits;
        $sign = $balance > 0 ? ' (Cr)' : ' (Dr)';
        $pdf->Cell(20, 10, abs($balance) . $sign, 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        // Table rows
        $pdf->SetFont('helvetica', '', 8);

        foreach ($record as $ledger) {
            $balance += $ledger->credit - $ledger->debit;

            $pdf->Cell(18, 10, $ledger->created_at->format('Y-m-d'), 1);
            $pdf->Cell(15, 10, $ledger->type . '-' . $ledger->document_id, 1);
            $pdf->Cell(50, 10, $ledger->account_head->name, 1);

            // Change to MultiCell for Narration
            $pdf->MultiCell(49, 10, $ledger->narration, 1, 'L', false, 0);

            $pdf->Cell(24, 10, number_format($ledger->debit, 2), 1);
            $pdf->Cell(24, 10, number_format($ledger->credit, 2), 1);
            $pdf->Cell(24, 10, number_format(abs($balance), 2) . $sign, 1, 1);
        }

        // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(230, 230, 230); 
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(132, 10, 'Balance', 1, 0, 'C', true);
        $sign = $balance > 0 ? ' (Cr)' : ' (Dr)';
        $pdf->Cell(24, 10, abs($debits), 1, 0, 'C', true);
        $pdf->Cell(24, 10, abs($credits), 1, 0, 'C', true);
        $pdf->Cell(24, 10, abs($balance) . $sign, 1, 0, 'C', true);
        $pdf->SetTextColor(0, 0, 0);

        // Output PDF
        $pdf->Output('general_ledger_report.pdf', 'I');
    }
    public function ledgerPdf(Request $request)
    {
        // to get name
        $head = AccountHead::with("level_four:id,name")->where("id",$request->head)->first();
        $terminal = Terminal::where("id",$request->terminal)->first();

        // to get sum of previous debits
        $previous_debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of previous credits
        $previous_credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // to get sum of debits
        $debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of credits
        $credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->where("account_head_id",$request->head)
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->get();

        $ledgers = (object)[];
        $ledgers->terminal = $terminal ? $terminal->name : null;
        $ledgers->debits = $debits;
        $ledgers->credits = $credits;
        $ledgers->record = $record;
        $ledgers->head = $head;
        $ledgers->from = $request->from;
        $ledgers->to = $request->to;
        $ledgers->previous_debits = $previous_debits;
        $ledgers->previous_credits = $previous_credits;
        // Initialize TCPDF object
       
        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GA');
        $pdf->SetTitle('Transaction');


        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(3, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 9);
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'b', 15);
        $pdf->Ln(-10);
        // Title
        $pdf->Cell(0, 0, strtoupper('Ledger'), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        // Date range and level
        $pdf->Cell(0, 7, 'Account : ' . $head->name.' ('.$head->level_four->name.')', 0, 1);
        $pdf->Cell(0, 7, 'From : ' . $request->from, 0, 1);
        $pdf->Cell(0, 7, 'To : ' . $request->to, 0, 1);
        if ($terminal) {
            $pdf->Cell(0, 7, 'Terminal : ' . strtoupper($terminal->name), 0, 1);
        }
        // Add some line break
        $pdf->Ln(10);

        // Table header
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(18, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(15, 10, 'V #', 1, 0, 'C', true);
        $pdf->Cell(79, 10, 'Narration', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Debit', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Credit', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Balance', 1, 1, 'C', true);

        // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetTextColor(255, 0, 0); // RGB for red
        $pdf->Cell(172, 10, 'Opening', 1, 0, 'C', true);
        $balance = $previous_credits - $previous_debits;
        $sign = $balance > 0 ? ' (Cr)' : ' (Dr)';
        $pdf->Cell(30, 10, abs($balance) . $sign, 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        // Table rows
        $pdf->SetFont('helvetica', '', 8);

        foreach ($record as $ledger) {
            $balance += $ledger->credit - $ledger->debit;

            $pdf->Cell(18, 10, $ledger->created_at->format('Y-m-d'), 1);
            $pdf->Cell(15, 10, $ledger->type . '-' . $ledger->document_id, 1);

            // Change to MultiCell for Narration
            $pdf->MultiCell(79, 10, $ledger->narration, 1, 'L', false, 0);

            $pdf->Cell(30, 10, number_format($ledger->debit, 2), 1);
            $pdf->Cell(30, 10, number_format($ledger->credit, 2), 1);
            $pdf->Cell(30, 10, number_format(abs($balance), 2) . $sign, 1, 1);
        }

        // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(230, 230, 230); 
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(112, 10, 'Balance', 1, 0, 'C', true);
        $sign = $balance > 0 ? ' (Cr)' : ' (Dr)';
        $pdf->Cell(30, 10, abs($debits), 1, 0, 'C', true);
        $pdf->Cell(30, 10, abs($credits), 1, 0, 'C', true);
        $pdf->Cell(30, 10, abs($balance) . $sign, 1, 0, 'C', true);
        $pdf->SetTextColor(0, 0, 0);

        // Output PDF
        $pdf->Output('general_ledger_report.pdf', 'I');
    }
    
    public function journalPdf(Request $request)
    {
        $terminal = Terminal::where("id",$request->terminal)->first();

        // to get sum of previous debits
        $previous_debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of previous credits
        $previous_credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // to get sum of debits
        $debits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("debit");
        
        // to get sum of credits
        $credits = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->sum("credit");

        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->with("account_head:id,name,code,group_id","other_head_name:id,name,code","account_head.level_four:id,name,code")
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->orderBy("created_at",'ASC')
        ->get();

        $journals = (object)[];
        $journals->terminal = $terminal ? $terminal->name : null;
        $journals->debits = $debits;
        $journals->credits = $credits;
        $journals->record = $record;
        $journals->from = $request->from;
        $journals->to = $request->to;
        $journals->previous_debits = $previous_debits;
        $journals->previous_credits = $previous_credits;
        // Initialize TCPDF object
       
        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GA');
        $pdf->SetTitle('Transaction');


        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(3, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 9);
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'b', 15);
        $pdf->Ln(-10);
        // Title
        $pdf->Cell(0, 0, strtoupper('General Journal'), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        // Date range and level
        $pdf->Cell(0, 7, 'From : ' . $request->from, 0, 1);
        $pdf->Cell(0, 7, 'To : ' . $request->to, 0, 1);
        if ($terminal) {
            $pdf->Cell(0, 7, 'Terminal : ' . strtoupper($terminal->name), 0, 1);
        }
        // Add some line break
        $pdf->Ln(10);

        // Table header
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(18, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(15, 10, 'V #', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Account', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'General Ledger', 1, 0, 'C', true);
        $pdf->Cell(49, 10, 'Narration', 1, 0, 'C', true);
        $pdf->Cell(24, 10, 'Debit', 1, 0, 'C', true);
        $pdf->Cell(24, 10, 'Credit', 1, 0, 'C', true);
        $pdf->Cell(24, 10, 'Balance', 1, 1, 'C', true);

        // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetTextColor(255, 0, 0); // RGB for red
        $pdf->Cell(180, 10, 'Opening', 1, 0, 'C', true);
        $balance = $previous_credits - $previous_debits;
        $sign = $balance > 0 ? ' (Cr)' : ' (Dr)';
        $pdf->Cell(24, 10, abs($balance) . $sign, 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        // Table rows
        $pdf->SetFont('helvetica', '', 8);

        foreach ($record as $ledger) {
            $balance += $ledger->credit - $ledger->debit;

            $pdf->Cell(18, 10, $ledger->created_at->format('Y-m-d'), 1);
            $pdf->Cell(15, 10, $ledger->type . '-' . $ledger->document_id, 1);
            $pdf->Cell(25, 10, $ledger->account_head->name, 1);
            $pdf->Cell(25, 10, $ledger->account_head->level_four->name, 1);

            // Change to MultiCell for Narration
            $pdf->MultiCell(49, 10, $ledger->narration, 1, 'L', false, 0);

            $pdf->Cell(24, 10, number_format($ledger->debit, 2), 1);
            $pdf->Cell(24, 10, number_format($ledger->credit, 2), 1);
            $pdf->Cell(24, 10, number_format(abs($balance), 2) . $sign, 1, 1);
        }

        // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(230, 230, 230); 
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(132, 10, 'Balance', 1, 0, 'C', true);
        $sign = $balance > 0 ? ' (Cr)' : ' (Dr)';
        $pdf->Cell(24, 10, abs($debits), 1, 0, 'C', true);
        $pdf->Cell(24, 10, abs($credits), 1, 0, 'C', true);
        $pdf->Cell(24, 10, abs($balance) . $sign, 1, 0, 'C', true);
        $pdf->SetTextColor(0, 0, 0);

        // Output PDF
        $pdf->Output('general_ledger_report.pdf', 'I');
    }
    
    public function generalTrialPdf(Request $request)
    {
        $terminal = Terminal::where("id",$request->terminal)->first();
        // this is for get previous trial
        $previous_transactions = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '<', $request->from);
        })
        ->get()
        ->groupBy(["account_id","group_id"]);

        // this is to get duration trial
        $current_transactions = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->from, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->from." 00:00:00");
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->get()
        ->groupBy(["account_id","group_id"]);

        // this loop for if any previous trial or current trial does't respond then this loop will handle and show    
        $total_transactions = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->to, function ($q) use ($request) {
            $q->where('created_at', '<=', $request->to." 23:59:59");
        })
        ->with("level_two:id,name,code","level_four:id,name")
        ->get()
        ->groupBy(["account_id","group_id"]);


        $main = [];
        // two mean level two
        foreach($total_transactions as $keyTwo=>$two)
        {
            $single = [];
            $fourData = [];
            // four mean four level
            foreach($two as $keyFour=>$four)
            {
                $temp = (object)[];

                $temp->level_four_id =  $four[0]->level_four->id;
                $temp->level_four_name =  $four[0]->level_four->name;


                $temp->current_debits = isset($current_transactions[$keyTwo][$keyFour]) ? $current_transactions[$keyTwo][$keyFour]->sum('debit') : 0;
                $temp->current_credits = isset($current_transactions[$keyTwo][$keyFour]) ? $current_transactions[$keyTwo][$keyFour]->sum('credit') : 0;

                $temp->previous_debits = isset($previous_transactions[$keyTwo][$keyFour]) ? $previous_transactions[$keyTwo][$keyFour]->sum('debit') : 0;
                $temp->previous_credits = isset($previous_transactions[$keyTwo][$keyFour]) ? $previous_transactions[$keyTwo][$keyFour]->sum('credit') : 0;

                $temp->closing_debits = $temp->current_debits + $temp->previous_debits;
                $temp->closing_credits =  $temp->current_credits + $temp->previous_credits;

                $fourData[] = $temp;
            } 
            
            $single['level_two_id'] = $two->first()[0]->level_two->id;
            $single['level_two_name'] = $two->first()[0]->level_two->name;
            $single['level_four'] = (object)$fourData;
            $main[] = (object)$single;
        }

        $data = (object)[];
        $data->record = (object)$main;
        $data->terminal = $terminal ? $terminal->name : null;
        $data->from = $request->from;
        $data->to = $request->to;
        // Initialize TCPDF object
       
        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GA');
        $pdf->SetTitle('Transaction');


        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(3, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 9);
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'b', 15);
        $pdf->Ln(-10);
        // Title
        $pdf->Cell(0, 0, strtoupper('Trial Report'), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        // Date range and level
        $pdf->Cell(0, 7, 'General Ledger | According', 0, 1);
        $pdf->Cell(0, 7, 'From : ' . $request->from, 0, 1);
        $pdf->Cell(0, 7, 'To : ' . $request->to, 0, 1);
        if ($terminal) {
            $pdf->Cell(0, 7, 'Terminal : ' . strtoupper($terminal->name), 0, 1);
        }
        // Add some line break
        $pdf->Ln(10);

        // Table header
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(54, 10, 'Account', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Debit', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Credit', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Debit', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Credit', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Debit', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Credit', 1, 0, 'C', true);
        $pdf->ln();

        $total_previous_debits = 0;
        $total_previous_credits = 0;
        $total_current_debits = 0;
        $total_current_credits = 0;
        foreach ($data->record as $level_two) {
            $pdf->SetFillColor(230, 230, 230);
            $pdf->SetTextColor(255, 0, 0); // RGB for red
            $pdf->Cell(204, 10, $level_two->level_two_name, 1, 0, 'L', true);
            $pdf->ln();
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0); // RGB for red
            foreach ($level_two->level_four as $single) {
                $pdf->Cell(54, 10, $single->level_four_name, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $single->previous_debits, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $single->previous_credits, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $single->current_debits, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $single->current_credits, 1, 0, 'C', true);
                $balance = ($single->closing_credits -$single->closing_debits);
                if($balance > 0)
                {
                    $pdf->Cell(25, 10, 0, 1, 0, 'C', true);
                    $pdf->Cell(25, 10, abs($balance), 1, 0, 'C', true);
                }
                else
                {
                    $pdf->Cell(25, 10, abs($balance), 1, 0, 'C', true);
                    $pdf->Cell(25, 10, 0, 1, 0, 'C', true);
                }
                $pdf->ln();

                $total_previous_debits += $single->previous_debits;
                $total_previous_credits += $single->previous_credits;
                $total_current_debits += $single->current_debits;
                $total_current_credits += $single->current_credits;
            }
        }

        // // Set fill color to Bootstrap secondary
        $pdf->SetFillColor(200, 220, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(54, 10, 'Total', 1, 0, 'C', true);
        $pdf->Cell(25, 10, $total_previous_debits, 1, 0, 'C', true);
        $pdf->Cell(25, 10, $total_previous_credits, 1, 0, 'C', true);
        $pdf->Cell(25, 10, $total_current_debits, 1, 0, 'C', true);
        $pdf->Cell(25, 10, $total_current_credits, 1, 0, 'C', true);
        $pdf->Cell(25, 10, '-', 1, 0, 'C', true);
        $pdf->Cell(25, 10, '-', 1, 0, 'C', true);

        // Output PDF
        $pdf->Output('general_ledger_report.pdf', 'I');
    }
    
    public function dailyReportPdf(Request $request)
    {
        $terminal = Terminal::where("id",$request->terminal)->first();
        // main record
        $record = AccountTransaction::where(["company_id"=>Auth::user()->company_id,"approved"=>1])
        ->when($request->terminal, function ($q) use ($request) {
            $q->where('terminal_id',$request->terminal);
        })
        ->when($request->current, function ($q) use ($request) {
            $q->where('created_at', '>=', $request->current." 00:00:00");
            $q->where('created_at', '<=', $request->current." 23:59:59");
        })
        ->with("account_head:id,name,code","level_four:id,name,code")
        ->orderBy("id",'ASC')
        ->get()
        ->groupBy(["type","document_id"]);

        $main = [];
        foreach($record as $outerKey=>$type)
        {
            $single = [];
            foreach($type as $innerKey=>$document)
            {
                $temp = [];
                $temp['document_id'] = $document[0]->document_id;
                $temp['type'] = $document[0]->type;
                $temp['amount'] = $document->sum('credit');
                $temp['narration'] = $document[0]->narration;
                $temp['account'] = $document[0]->account_head->name;
                $temp['parent_account'] = $document[0]->level_four->name;
                $temp['created_at'] = $document[0]->created_at;
                $single[] = (object)$temp;
            }
            $main[$outerKey] = (object)$single;
        }
        
        $daily_data = (object)[];
        $daily_data->terminal = $terminal ? $terminal->name : null;
        $daily_data->current = $request->current;
        $daily_data->record = (object)$main;
        // Initialize TCPDF object
       
        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GA');
        $pdf->SetTitle('Transaction');


        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 005', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(3, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 9);
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', 'b', 15);
        $pdf->Ln(-10);
        // Title
        $pdf->Cell(0, 0, strtoupper('Trial Report'), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        // Date range and level
        $pdf->Cell(0, 7, 'Date : ' . $request->current, 0, 1);
        if ($terminal) {
            $pdf->Cell(0, 7, 'Terminal : ' . strtoupper($terminal->name), 0, 1);
        }
        // Add some line break
        $pdf->Ln(10);

        // Table header
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(54, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Voucher', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Account', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Narration', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Amount', 1, 0, 'C', true);
        $pdf->ln();

        // Bank Payments
        if(isset($daily_data->record->BP))
        {
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(255, 255, 255); 
    
            $pdf->Cell(204, 10,'Bank Payments', 1, 0, 'C', true);
            $pdf->ln();
    
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0); 
            foreach ($daily_data->record->BP as $bp) {
                $pdf->Cell(54, 10, date("d-M-Y",strtotime($bp->created_at)), 1, 0, 'C', true);
                $pdf->Cell(25, 10, $bp->type.'-'.$bp->document_id, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $bp->account, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $bp->narration, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $bp->amount, 1, 0, 'C', true);
                $pdf->ln();
            }
        }
        // Bank Receipts
        if(isset($daily_data->record->BR))
        {
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(255, 255, 255); 
    
            $pdf->Cell(204, 10,'Bank Receipts', 1, 0, 'C', true);
            $pdf->ln();
    
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0); 
            foreach ($daily_data->record->BR as $br) {
                $pdf->Cell(54, 10, date("d-M-Y",strtotime($br->created_at)), 1, 0, 'C', true);
                $pdf->Cell(25, 10, $br->type.'-'.$br->document_id, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $br->account, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $br->narration, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $br->amount, 1, 0, 'C', true);
                $pdf->ln();
            }
        }
        // Cash Payments
        if(isset($daily_data->record->CP))
        {
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(255, 255, 255); 
    
            $pdf->Cell(204, 10,'Cash Payments', 1, 0, 'C', true);
            $pdf->ln();
    
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0); 
            foreach ($daily_data->record->CP as $cp) {
                $pdf->Cell(54, 10, date("d-M-Y",strtotime($cp->created_at)), 1, 0, 'C', true);
                $pdf->Cell(25, 10, $cp->type.'-'.$cp->document_id, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $cp->account, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $cp->narration, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $cp->amount, 1, 0, 'C', true);
                $pdf->ln();
            }
        }
        // Cash Receipts
        if(isset($daily_data->record->CR))
        {
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(255, 255, 255); 
    
            $pdf->Cell(204, 10,'Cash Receipts', 1, 0, 'C', true);
            $pdf->ln();
    
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0); 
            foreach ($daily_data->record->CR as $cr) {
                $pdf->Cell(54, 10, date("d-M-Y",strtotime($cr->created_at)), 1, 0, 'C', true);
                $pdf->Cell(25, 10, $cr->type.'-'.$cr->document_id, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $cr->account, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $cr->narration, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $cr->amount, 1, 0, 'C', true);
                $pdf->ln();
            }
        }
        // Journal Voucher
        if(isset($daily_data->record->JV))
        {
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(255, 255, 255); 
    
            $pdf->Cell(204, 10,'Journal Voucher', 1, 0, 'C', true);
            $pdf->ln();
    
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0); 
            foreach ($daily_data->record->JV as $jv) {
                $pdf->Cell(54, 10, date("d-M-Y",strtotime($jv->created_at)), 1, 0, 'C', true);
                $pdf->Cell(25, 10, $jv->type.'-'.$jv->document_id, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $jv->account, 1, 0, 'C', true);
                $pdf->Cell(50, 10, $jv->narration, 1, 0, 'C', true);
                $pdf->Cell(25, 10, $jv->amount, 1, 0, 'C', true);
                $pdf->ln();
            }
        }

        // Output PDF
        $pdf->Output('general_ledger_report.pdf', 'I');
    }
}

include(public_path().'/assets/tcpdf/tcpdf.php');
class MYPDF extends TCPDF
{
    public $heading, $terminal;

    public function Header()
    {
    }


    public function Footer()
    {
    }
}
