<?php

namespace charlieuki\ReceiptPrinter;

use charlieuki\ReceiptPrinter\Item as Item;
use charlieuki\ReceiptPrinter\Store as Store;
use Mike42\Escpos\Printer;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

class ReceiptPrinter
{
    private $printer;
    private $logo;
    private $store;
    private $items;
    private $currency = 'PKR';
    private $subtotal = 0;
    private $tax_percentage = 10;
    private $tax = 0;
    private $grandtotal = 0;
    private $request_amount = 0;
    private $qr_code = [];
    private $transaction_id = '';

    function __construct()
    {
        $this->printer = null;
        $this->items = [];
    }

    public function init($connector_type, $connector_descriptor, $connector_port = 9100)
    {
        switch (strtolower($connector_type)) {
            case 'cups':
                $connector = new CupsPrintConnector($connector_descriptor);
                break;
            case 'windows':
                $connector = new WindowsPrintConnector($connector_descriptor);
                break;
            case 'network':
                $connector = new NetworkPrintConnector($connector_descriptor);
                break;
            default:
                $connector = new FilePrintConnector("php://stdout");
                break;
        }

        if ($connector) {
            // Load simple printer profile
            $profile = CapabilityProfile::load("default");
            // Connect to printer
            $this->printer = new Printer($connector, $profile);
        } else {
            throw new Exception('Invalid printer connector type. Accepted values are: cups');
        }
    }

    public function close()
    {
        if ($this->printer) {
            $this->printer->close();
        }
    }

    public function setStore($uan, $company_name, $company_address, $company_phone, $termsCondition, $checkDuplicate, $seatNo, $customerContact, $customerCNIC, $customerName, $seatFare, $bookingDate, $departureTime, $departureDate, $departureCity, $destinationCity, $busClass)
    {
        $this->store = new Store($uan, $company_name, $company_address, $company_phone, $termsCondition, $checkDuplicate, $seatNo, $customerContact, $customerCNIC, $customerName, $seatFare, $bookingDate, $departureTime, $departureDate, $departureCity, $destinationCity, $busClass);
    }

    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function addItem($name, $qty, $price)
    {
        $item = new Item($name, $qty, $price);
        $item->setCurrency($this->currency);

        $this->items[] = $item;
    }

    public function setRequestAmount($amount)
    {
        $this->request_amount = $amount;
    }

    public function setTax($tax)
    {
        $this->tax_percentage = $tax;

        if ($this->subtotal == 0) {
            $this->calculateSubtotal();
        }

        $this->tax = (int)$this->tax_percentage / 100 * (int)$this->subtotal;
    }

    public function calculateSubtotal()
    {
        $this->subtotal = 0;

        foreach ($this->items as $item) {
            $this->subtotal += (int)$item->getQty() * (int)$item->getPrice();
        }
    }

    public function calculateGrandTotal()
    {
        if ($this->subtotal == 0) {
            $this->calculateSubtotal();
        }

        $this->grandtotal = (int)$this->subtotal + (int)$this->tax;
    }

    public function setTransactionID($transaction_id)
    {
        $this->transaction_id = $transaction_id;
    }

    public function setQRcode($content)
    {
        $this->qr_code = $content;
    }

    public function setTextSize($width = 1, $height = 1)
    {
        if ($this->printer) {
            $width = ($width >= 1 && $width <= 8) ? (int)$width : 1;
            $height = ($height >= 1 && $height <= 8) ? (int)$height : 1;
            $this->printer->setTextSize($width, $height);
        }
    }

    public function getPrintableQRcode()
    {
        return json_encode($this->qr_code);
    }

    public function getPrintableHeader($left_text, $right_text, $is_double_width = false)
    {
        $cols_width = $is_double_width ? 8 : 16;

        return str_pad($left_text, $cols_width) . str_pad($right_text, $cols_width, ' ', STR_PAD_LEFT);
    }

    public function getPrintableSummary($label, $value, $is_double_width = false)
    {
        $left_cols = $is_double_width ? 6 : 17;
        $right_cols = $is_double_width ? 10 : 24;

        $formatted_value = $value;

        return str_pad($label, $left_cols) . str_pad($formatted_value, $right_cols, ' ', STR_PAD_LEFT);
    }

    public function feed($feed = NULL)
    {
        $this->printer->feed($feed);
    }

    public function cut()
    {
        $this->printer->cut();
    }

    public function printDashedLine()
    {
        $line = '';

        for ($i = 0; $i < 32; $i++) {
            $line .= '-';
        }

        $this->printer->text($line);
    }

    public function printLogo($qr, $flag)
    {
        if ($flag == "ELT") {
            $image = EscposImage::load(public_path("Customers/Elt/$qr"), false);
            $this->printer->feed();
            $this->printer->bitImage($image);
            $this->printer->feed();
        }
        if($flag == "Ticket") {
        $image = EscposImage::load(public_path("Customers/Qrs/$qr"), false);
        $this->printer->feed();
        $this->printer->bitImage($image);
        $this->printer->feed();
        }

    }

    public function printQRcode()
    {
        if (!empty($this->qr_code)) {
            $this->printer->qrCode($this->getPrintableQRcode(), Printer::QR_ECLEVEL_L, 8);
        }
    }

    //For  Ticket Printing Code
    public function printReceipt($array_items = true)
    {
        if ($this->printer) {
            //Set Varaible
            $heading = $array_items['companyName'];
            $terminal_address = $array_items['companyAddress'];
            $uan = 'UAN(24/7) : ' . $array_items['uan'];
            $phone = 'Phone : ' . $array_items['phone'];
            $custName = $array_items['customerName'];
            $seatNo = $array_items['seatNo'];
            $busClass = $array_items['busClass'];
            $deptdate = $array_items['departDate'];
            $depttime = $array_items['departTime'];
            $bookDate = $array_items['bookingDate'];
            $from = $array_items['from'];
            $to = $array_items['to'];
            $fare = $array_items['fare'];
            $terms = $array_items['terms'];
            $cnic = $array_items['cnic'];
            $contact = $array_items['contact'];
            $duplicate = $array_items['duplicate'] == 1 ? 'Duplicate Ticket' : '';

            //Demo Print
            $this->printer->initialize();
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("{$heading}\n");
            //For Space
            $this->printer->selectPrintMode();
            $this->printer->text("{$terminal_address}\n\n");
            $this->printer->text("{$uan}\n\n");
            $this->printer->text("{$phone}\n\n");
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $this->printer->text("{$duplicate}\n\n");
            // Print logo
            $this->printLogo($array_items['qr'], "Ticket");

            $row_one = $this->getPrintableSummary(
                "Customer Name :",
                $custName,
                false
            );

            $row_two = $this->getPrintableSummary(
                "Seat No :",
                $seatNo,
                false
            );

            $row_three = $this->getPrintableSummary(
                "Bus Class :",
                $busClass,
                false
            );

            $row_from = $this->getPrintableSummary(
                "From :",
                $from,
                false
            );

            $row_to = $this->getPrintableSummary(
                "To :",
                $to,
                false
            );

            $row_four = $this->getPrintableSummary(
                "Departure Date :",
                $deptdate,
                false

            );

            $row_five = $this->getPrintableSummary(
                "Departure Time :",
                $depttime,
                false

            );

            $row_six = $this->getPrintableSummary(
                "Booking Date :",
                $bookDate,
                false
            );

            $row_seven = $this->getPrintableSummary(
                "Fare :",
                $fare,
                false
            );

            $this->printer->selectPrintMode();
            $this->printer->setEmphasis(true);
            $this->printer->text("\n\n" . $row_one . "\n\n");
            $this->printer->text($row_two . "\n\n");
            $this->printer->text($row_three . "\n\n");
            $this->printer->text($row_from . "\n\n");
            $this->printer->text($row_to . "\n\n");
            $this->printer->text($row_four . "\n\n");
            $this->printer->text($row_five . "\n\n");
            $this->printer->text($row_six . "\n\n");
            $this->printer->text($row_seven . "\n\n");
            $this->printDashedLine();
            $this->printer->feed();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("Terms & Conditions Applied\n\n");
            $this->printer->feed();
            $this->printer->text($terms . "\n\n");
            $this->printer->feed(2);
            $this->printer->text("© Right Reserved By Kainat Travels");
            $this->printer->feed();
            $this->printer->cut();


            //page 2
            $row_ticketHolder = $this->getPrintableSummary(
                "Ticket Holder Name :",
                $custName,
                false
            );

            $row_cnic = $this->getPrintableSummary(
                "CNIC Number :",
                $cnic,
                false
            );

            $row_contact = $this->getPrintableSummary(
                "Contact# :",
                $contact,
                false
            );
            $this->printer->selectPrintMode();
            $this->printer->setEmphasis(true);
            $this->printer->text("\n\n");
            $this->printer->text("\n\n" . $row_two . "\n\n");
            $this->printer->text($row_three . "\n\n");
            $this->printer->text($row_from . "\n\n");
            $this->printer->text($row_to . "\n\n");
            $this->printer->text($row_four . "\n\n");
            $this->printer->text($row_one . "\n\n");
            $this->printer->text($row_ticketHolder . "\n\n");
            $this->printer->text($row_cnic . "\n\n");
            $this->printer->text($row_contact . "\n\n");
            $this->printer->cut();
            $this->printer->close();


            return;

        } else {
            throw new Exception('Printer has not been initialized.');
        }
    }

//For  Elt Ticket Format
    public function printRequest($array_items = true)
    {
        if ($this->printer) {
            //Set Varaible
            $elt_Heading = 'ELT Receipt';
            $fare = $array_items['fare'];
            $heading = $array_items['companyName'];
            $terminal_address = $array_items['companyAddress'];
            $uan = 'UAN(24/7) : ' . $array_items['uan'];
            $phone = 'Phone : ' . $array_items['phone'];
            $custName = $array_items['customerName'];
            $seatNo = $array_items['seatNo'];
            $busClass = $array_items['busClass'];
            $deptdate = $array_items['departDate'];
            $depttime = $array_items['departTime'];
            $from = $array_items['from'];
            $to = $array_items['to'];
            $eltFare = $array_items['elt_price'];
            $customerContact = $array_items['contact'];
            $bookDate = $array_items['bookingDate'];
            $weight = $array_items['weight'];
            $total = $array_items['total_price'];


            //Demo Print
            $this->printer->initialize();
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("{$elt_Heading}");
            $row_one = $this->getPrintableSummary(
                "Customer Name :",
                $custName,
                false
            );

            $row_two = $this->getPrintableSummary(
                "Customer Contact :",
                $customerContact,
                false
            );

            $row_three = $this->getPrintableSummary(
                "Seat No :",
                $seatNo,
                false
            );

            $row_four = $this->getPrintableSummary(
                "Bus Class :",
                $busClass,
                false

            );

            $row_five = $this->getPrintableSummary(
                "Booking Date :",
                $bookDate,
                false

            );

            $row_six = $this->getPrintableSummary(
                "Departure City :",
                $from,
                false
            );

            $row_seven = $this->getPrintableSummary(
                "Destination City :",
                $to,
                false
            );

            $row_eight = $this->getPrintableSummary(
                "Elt Price :",
                $eltFare,
                false
            );
            $row_weight = $this->getPrintableSummary(
                "Elt Weight :",
                $weight,
                false
            );

            $row_nine = $this->getPrintableSummary(
                "SUB TOTAL :",
                $eltFare,
                false
            );

            $row_ten = $this->getPrintableSummary(
                "Seat Fare :",
                $fare,
                false
            );

            $row_eleven = $this->getPrintableSummary(
                "TOTAL :",
                $total,
                false
            );

            $this->printer->selectPrintMode();
            $this->printer->setEmphasis(true);
            $this->printer->text("\n");
            $this->printer->text("\n\n" . $row_one . "\n\n");
            $this->printer->text($row_two . "\n\n");
            $this->printDashedLine();
            $this->printer->feed();
            $this->printer->text($row_three . "\n\n");
            $this->printer->text($row_four . "\n\n");
            $this->printer->text($row_five . "\n\n");
            $this->printer->text($row_six . "\n\n");
            $this->printer->text($row_seven . "\n\n");
            $this->printer->text($row_eight . "\n\n");
            $this->printer->text($row_weight . "\n\n");
            $this->printDashedLine();
            $this->printer->feed();
            $this->printer->text($row_nine . "\n\n");
            $this->printer->text($row_ten . "\n\n");
            $this->printer->text($row_eleven . "\n\n");
            $this->printDashedLine();
            $this->printer->feed();
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            //Footer
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->text("{$heading}\n");
            //For Space
            $this->printer->selectPrintMode();
            $this->printer->text("{$terminal_address}\n\n");
            $this->printer->text("{$uan}\n\n");
            $this->printer->text("{$phone}\n\n");
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            // Print logo
            $this->printLogo($array_items['qr'], "ELT");
            //Cut page
            $this->printer->cut();
            $this->printer->close();
            return;

        } else {
            throw new Exception('Printer has not been initialized.');
        }
    }
}
