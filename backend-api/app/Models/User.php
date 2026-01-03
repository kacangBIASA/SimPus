<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi mass assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // admin/member
    ];

    /**
     * Kolom yang disembunyikan dari JSON response.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast attributes (Laravel 10/11 style).
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
