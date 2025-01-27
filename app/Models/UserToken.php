<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    use HasFactory;

    // Definir la tabla asociada al modelo
    protected $table = 'user_tokens';

    // Definir los campos que son asignables de forma masiva
    protected $fillable = [
        'user_id',
        'token',
        'user_agent',
        'ip_address'
    ];

    // Definir la relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
