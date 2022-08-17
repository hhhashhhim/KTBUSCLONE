<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalAvailableSeat extends Model
{
    use HasFactory;
    protected $fillable = ['terminal_id','seats','added_by'];

}
