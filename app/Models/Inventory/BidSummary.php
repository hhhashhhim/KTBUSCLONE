<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BidSummary extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = [
        'prn_id', 'mr_id', 'status', 'supplier_id', 'total_amount', 'total', 'tax_amount',
        'tax', 'advance', 'after_delivery', 'credit_days', 'discount', 
        'delivery_charges', 'contact_person', 'contact_person_contact', 
        'terms_condition', 'quotation_date', 'quotation_ref','advance_amount','after_delivery_amount','added_by','company_id'
    ];
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function prn()
    {
        return $this->belongsTo(PurchaseRequisitionNote::class, 'prn_id');
    }
    public function products() {
        return $this->hasMany(Product::class); // or whatever your model is
    }
    public function details() {
        return $this->hasMany(BidDetail::class, 'bid_id');
    }
    public function bidDetails()
    {
        return $this->hasMany(BidDetail::class);
    }
    public function mr()
    {
        return $this->belongsTo(MaterialRequest::class, 'mr_id');
    }
}    

