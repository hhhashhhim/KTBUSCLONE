<?php

namespace App\Models;

use App\Models\admin\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserPassword extends Authenticatable
{
    use HasFactory;
    protected $guarded = [];
}
