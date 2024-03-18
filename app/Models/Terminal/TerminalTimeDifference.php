<?php

namespace App\Models\Terminal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Terminal;
use Illuminate\Database\Eloquent\SoftDeletes;

class TerminalTimeDifference extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }
}
