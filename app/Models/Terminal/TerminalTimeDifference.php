<?php

namespace App\Models\Terminal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TerminalTimeDifference extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];
}
