<?php

namespace App\Models\Inventory;

use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreIssuanceNoteDetail extends Model
{
    use HasFactory;
    protected $fillable = ['store_issuance_note_id', 'product_id', 'qty', 'rate', 'total','company_id','bus_id'];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function storeIssuanceNote()
    {
        return $this->belongsTo(StoreIssuanceNote::class, 'store_issuance_note_id');
    }
        public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }
      public function materialRequest()
    {
        return $this->hasMany(MaterialRequest::class);
    }
public function materialRequestDetail()
{
    return $this->hasOne(MaterialRequestDetail::class, 'product_id', 'product_id')
                ->whereColumn('material_request_details.bus_id', 'store_issuance_note_details.bus_id')
                ->latest(); // pick latest if multiple
}

   
    
}
