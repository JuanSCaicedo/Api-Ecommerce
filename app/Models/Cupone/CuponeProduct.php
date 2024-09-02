<?php

namespace App\Models\Cupone;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuponeProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "cupone_id",
        "product_id",
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
}
