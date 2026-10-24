<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // role: 'admin' | 'registrador'
    protected $fillable = [
        'name', 'email', 'password', 'role', 'documento', 'estado', 'must_change_password',
    ];

    // Estos campos NUNCA deben salir en una respuesta JSON
    protected $hidden = ['password', 'remember_token', 'google2fa_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'google2fa_secret'       => 'encrypted',   // la clave del QR se guarda cifrada
            'google2fa_confirmed_at' => 'datetime',
            'estado'                 => 'boolean',
            'must_change_password'   => 'boolean',
        ];
    }

    /** Datos que el frontend necesita de la sesión */
    public function perfil(): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'role'                 => $this->role,
            'must_change_password' => $this->must_change_password,
        ];
    }
}
