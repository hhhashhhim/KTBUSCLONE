<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['name','added_by','company_id'];
    public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
    

}
