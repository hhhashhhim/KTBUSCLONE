<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = [
        'bid_id', 'mr_id', 'prn_id', 'supplier_id',
        'total', 'remaining', 'status','added_by','company_id'
    ];
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function prn()
    {
        return $this->belongsTo(PurchaseRequisitionNote::class, 'prn_id');
    }
    public function mr()
    {
        return $this->belongsTo(MaterialRequest::class, 'mr_id');
    }
    public function bid()
    {
        return $this->belongsTo(BidSummary::class, 'bid_id');
    }
    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    public function poDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'po_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    // PurchaseOrder.php
    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    } 
    public function goodReceiveNotes() {
        return $this->hasMany(GoodReceiveNote::class, 'po_id');
    }   
    public function details()
    {
        return $this->hasMany(GoodReceiveNoteDetail::class, 'good_receive_note_id');
    } 


}

