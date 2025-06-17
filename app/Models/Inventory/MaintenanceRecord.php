<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id', 'qty', 'maintenance_type', 'description',
        'maintenance_date', 'next_due_date','added_by','company_id'
    ];
    
}
