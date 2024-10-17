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
    protected $guarded = [];

   
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
