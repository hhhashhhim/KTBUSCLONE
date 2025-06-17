<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnGoodReceiveNoteItem extends Model
{
    use HasFactory;
    protected $fillable = ['return_good_receive_note_id', 'product_id', 'qty','company_id'];

}
