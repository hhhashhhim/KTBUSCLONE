<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['requested_by', 'status', 'added_by','company_id','store_Issued_qty'];


    public function details()
    {
    return $this->hasMany(MaterialRequestDetail::class, 'mr_id');
    }
    public function storeIssuance()
    {
        return $this->hasMany(StoreIssuanceNote::class, 'mr_id')->with('details');
    }    
        // MaterialRequest.php
    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

}
