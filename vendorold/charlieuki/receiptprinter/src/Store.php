<?php

namespace charlieuki\ReceiptPrinter;

class Store
{
    private $uan = '';
    private $company_name = '';
    private $company_address = '';
    private $company_phone = '';
    private $termsCondition = '';
    private $checkDuplicate = '';
    private $seatNo = '';
    private $customerContact = '';
    private $customerCNIC = '';
    private $customerName = '';
    private $seatFare = '';
    private $bookingDate = '';
    private $departureTime = '';
    private $departureDate = '';
    private $destinationCity = '';
    private $departureCity = '';
    private $busClass = '';


    function __construct($uan, $company_name, $company_address, $company_phone, $termsCondition, $checkDuplicate, $seatNo, $customerContact, $customerCNIC, $customerName, $seatFare, $bookingDate, $departureTime, $departureDate, $departureCity, $destinationCity, $busClass)
    {
        $this->uan = $uan;
        $this->company_name = $company_name;
        $this->company_address = $company_address;
        $this->company_phone = $company_phone;
        $this->termsCondition = $termsCondition;
        $this->checkDuplicate = $checkDuplicate;
        $this->seatNo = $seatNo;
        $this->customerContact = $customerContact;
        $this->customerCnic = $customerCNIC;
        $this->customerName = $customerName;
        $this->seatFare = $seatFare;
        $this->bookingDate = $bookingDate;
        $this->departureTime = $departureTime;
        $this->departureDate = $departureDate;
        $this->destinationCity = $destinationCity;
        $this->departureCity = $departureCity;
        $this->busClass = $busClass;
    }

    public function getUAN()
    {
        return $this->uan;
    }

    public function getCompanyName()
    {
        return $this->company_name;
    }

    public function getCompanyAddress()
    {
        return $this->company_address;
    }

    public function getCompanyPhone()
    {
        return $this->company_phone;
    }

    public function getTerms()
    {
        return $this->termsCondition;
    }

    public function getDuplicate()
    {
        return $this->checkDuplicate;
    }

    public function getSeatNo()
    {
        return $this->seatNo;
    }

    public function getCustomerContact()
    {
        return $this->customerContact;
    }

    public function getCustomerCNIC()
    {
        return $this->customerCNIC;
    }

    public function getCustomerName()
    {
        return $this->customerName;
    }

    public function getSeatFare()
    {
        return $this->seatFare;
    }

    public function getBookingDate()
    {
        return $this->bookingDate;
    }

    public function getDepartureTime()
    {
        return $this->departureTime;
    }

    public function getDepartureDate()
    {
        return $this->departureDate;
    }

    public function getDepartureCity()
    {
        return $this->departureCity;
    }

    public function getDestinationCity()
    {
        return $this->busClass;
    }

    public function getBusClass()
    {
        return $this->busClass;
    }
}
