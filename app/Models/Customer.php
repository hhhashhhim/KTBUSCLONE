<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static where(string $string, $company_id)
 */
class Customer extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id', 'id');
    }
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
}
