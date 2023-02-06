<?php

namespace App\Models\Terminal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalTimeDifference extends Model
{
    use HasFactory, softDelete;

    protected $guarded = [];
}
