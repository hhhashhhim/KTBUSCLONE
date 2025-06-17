<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceiveNoteDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'good_receive_note_details';

    protected $fillable = [
        'good_receive_note_id', 'product_id', 'qty', 'rate',
        'total', 'tax', 'delivery_charges', 'discount', 'net_amount','company_id'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    } 
    
}
