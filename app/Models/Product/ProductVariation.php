<?php

namespace App\Models\Product;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "product_id",
        "attribute_id",
        "propertie_id",
        "value_add",
        "add_price",
        "stock",
        "state",
        "product_variation_id",
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function propertie()
    {
        return $this->belongsTo(Propertie::class);
    }

    public function variation_father()
    {
        return $this->belongsTo(ProductVariation::class, "product_variation_id");
    }

    public function variation_children()
    {
        return $this->hasMany(ProductVariation::class, "product_variation_id");
    }
}
