<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
        protected $fillable = [
            'po_id', 'product_id', 'qty', 'rate', 'sub_total',
            'tax','tax_amount', 'delivery', 'discount', 'net_amount',
            'gate_receive_note', 'store_received','company_id'
        ];
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
}
