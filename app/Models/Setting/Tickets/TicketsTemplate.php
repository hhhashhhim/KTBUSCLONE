<?php

namespace App\Models\Setting\Tickets;

use App\Models\Company;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketsTemplate extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function addedBy()
    {
        return $this->hasOne( User::class, 'id', 'added_by');
    }
    public function updatedBy()
    {
        return $this->hasOne( User::class, 'id', 'updated_by');
    }
    public function company(){
        return $this->hasOne( Company::class,'id','company_id' );
    }
    public function terminal(){
        return $this->hasOne( Terminal::class,'id','terminal_id' );
    }
    public function template_terminals(){
        return $this->hasMany( TicketTemplateTerminal::class,'ticket_template_id','id' );
    }

}
