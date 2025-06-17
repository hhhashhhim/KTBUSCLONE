<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnGoodReceiveNote extends Model
{
    use HasFactory;
    protected $fillable = ['good_receive_note_id', 'return_date', 'reason', 'handled_by','added_by','company_id'];

}
