<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnStoreIssuanceNote extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_issuance_note_id', 'return_date', 'reason',
        'returned_by', 'approved_by','added_by','company_id'
    ];
    
}
