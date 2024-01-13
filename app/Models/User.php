<?php

namespace App\Models;

use App\Models\admin\Role;
use App\Models\Hrm\Employee\Employee;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'departure_city_ids' => 'array',
        'destination_city_ids' => 'array',
    ];

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function terminal()
    {
        return $this->hasOne(Terminal::class, 'id', 'terminal_id');
    }

    public function departureCity()
    {
        return $this->hasOne(City::class, 'id', 'departure_city_id');
    }

    public function destinationCity()
    {
        return $this->hasOne(City::class, 'id', 'destination_city_id');
    }

    public function userpass()
    {
        return $this->hasOne(UserPassword::class, 'user_id', 'id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }
}
