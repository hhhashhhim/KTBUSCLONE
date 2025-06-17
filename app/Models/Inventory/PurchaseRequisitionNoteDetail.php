<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionNoteDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['prn_id', 'product_id', 'qty','company_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    
}
