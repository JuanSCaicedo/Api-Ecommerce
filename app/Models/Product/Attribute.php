<?php

namespace App\Models\Product;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type_attribute',
        'state'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attribute) {
            $attribute->properties()->delete();
        });
    }

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

    public function properties() {
        return $this->hasMany(Propertie::class);
    }
}
