<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnStoreIssuanceNoteItem extends Model
{
    use HasFactory;
    protected $fillable = ['return_store_issuance_note_id', 'product_id', 'qty','company_id'];

}
