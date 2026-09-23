<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerminalSalesRouteGroup extends Model
{
    protected $guarded = [];
    protected $casts = ['route_ids' => 'array'];
}
