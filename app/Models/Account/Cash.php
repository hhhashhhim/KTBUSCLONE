<?php

namespace App\Models\account;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cash extends Model
{
    use HasFactory, softDeletes;
    protected $guarded = [];
    protected $table  = "cash";

    // public function level_one(){
    //     return $this->belongsTo( Account::class, 'parent_account_id', 'id');
    // }
}
