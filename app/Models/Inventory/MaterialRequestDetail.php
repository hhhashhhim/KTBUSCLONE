<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequestDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['mr_id', 'product_id', 'qty','store_Issued_qty', 'reason','company_id'];

    // MaterialRequestDetail.php
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


}
