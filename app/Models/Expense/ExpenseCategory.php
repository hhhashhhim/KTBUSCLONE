<?php

namespace App\Models\Expense;

use App\Models\ReportsHeader;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

   
    public function addedBy()
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }
    public function reportHeader()
{
    return $this->belongsTo(ReportsHeader::class, 'report_header_id');
}

}
