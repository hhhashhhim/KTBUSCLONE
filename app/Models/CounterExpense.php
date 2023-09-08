<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    public function added_by()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    public function added_by_data()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }


}
