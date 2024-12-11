<?php

namespace App\Models\Discount;

use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "code",
        "type_discount",
        "discount",
        "start_date",
        "end_date",
        "discount_type",
        "type_campaing",
        "state",
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('America/Bogota');
        $this->attributes['created_at'] = Carbon::now();
    }
    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('America/Bogota');
        $this->attributes['updated_at'] = Carbon::now();
    }

    public function categories()
    {
        return $this->hasMany(DiscountCategorie::class);
    }

    public function products()
    {
        return $this->hasMany(DiscountProduct::class);
    }

    public function brands()
    {
        return $this->hasMany(DiscountBrand::class);
    }

    // Evento deleting para eliminar relaciones asociadas
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($discount) {
            $discount->products()->delete();   // Elimina los productos asociados
            $discount->categories()->delete(); // Elimina las categorías asociadas
            $discount->brands()->delete();     // Elimina las marcas asociadas
        });
    }
}
