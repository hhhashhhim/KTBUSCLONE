<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalCommission extends Model
{
    use HasFactory;
    protected $fillable = ['terminal_id','amount','per_seat','added_by'];
}
