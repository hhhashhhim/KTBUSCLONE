<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreIssuanceNote extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['requested_by', 'reason', 'mr_id','status','added_by','company_id'];
    public function details()
    {
        return $this->hasMany(StoreIssuanceNoteDetail::class, 'store_issuance_note_id');
    }
    
}
