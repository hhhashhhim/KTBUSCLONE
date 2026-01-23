<?php

namespace App\Models\Expense;

use App\Models\Schedule\TicketClosingMerge;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TicketMergeExpense extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'ticket_merge_id',
        'start_route_id',
        'return_route_id',
        'bus_id',
        'expense_category_id',
        'description',
        'amount', // Total Expense Amount
        'paid', // if paid 0 then it is credit
        'ledger',
        'invoice',
        'added_by',
        'company_id',
        'time',
        'updated_by',
        'updated_at'
    ];

   
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    
    public function expense_category()
    {
        return $this->hasOne(ExpenseCategory::class, 'id', 'expense_category_id');
    }
    
    public function merge()
    {
        return $this->belongsTo(TicketClosingMerge::class, 'ticket_merge_id', 'id');
    }

}
