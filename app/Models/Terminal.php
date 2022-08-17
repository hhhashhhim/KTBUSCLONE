<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    use HasFactory;
    protected $fillable = [
        'name','contact','address','longitude','latitude','active_sms','city_id','company_id','online_terminal_name','status','added_by',
    ];
}
