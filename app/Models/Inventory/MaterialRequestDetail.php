<?php

namespace App\Models\Inventory;

use App\Models\Bus\Bus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequestDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['mr_id', 'product_id', 'qty','store_Issued_qty', 'reason','company_id','bus_id',];

    // MaterialRequestDetail.php
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
        public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }


}
