<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreIssuanceNoteDetail extends Model
{
    use HasFactory;
    protected $fillable = ['store_issuance_note_id', 'product_id', 'qty', 'rate', 'total','company_id'];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function storeIssuanceNote()
    {
        return $this->belongsTo(StoreIssuanceNote::class, 'store_issuance_note_id');
    }
    
   
    
}
