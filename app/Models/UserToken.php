<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    // Definir la tabla asociada al modelo
    protected $table = 'user_tokens';

    // Definir los campos que son asignables de forma masiva
    protected $fillable = [
        'id',
        'user_id',
        'type_user',
        'token',
        'user_agent',
        'ip_address',
        'expires_at',
    ];

    // Definir la relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
