<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];


    /**
     * Os atributos que devem ser ocultados para serialização.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Define as transformações de tipos de atributos do modelo.
     *
     * Este método especifica como os atributos do modelo devem ser convertidos
     * para seus tipos apropriados ao serem recuperados do banco de dados,
     * e como devem ser armazenados.
     *
     * @return array<string, string> Um array associativo onde as chaves são nomes
     *                               de atributos e os valores são os tipos de casting.
     *                               - 'email_verified_at': converte para DateTime
     *                               - 'password': aplica hash automáticamente
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
