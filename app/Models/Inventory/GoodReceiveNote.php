<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceiveNote extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['po_id', 'supplier_id', 'received_by','added_by','company_id'];
    public function details()
    {
      return $this->hasMany(GoodReceiveNoteDetail::class, 'good_receive_note_id');
    }
    public function purchaseOrder()
    {
      return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function grnDetails()
    {
        return $this->hasMany(GoodReceiveNoteDetail::class, 'good_receive_note_id');
    }

  
}
