<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleAddres extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "sale_id",
        "name",
        "surname",
        "company",
        "country_region",
        "address",
        "street",
        "city",
        "postcode_zip",
        "phone",
        "email"
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set("America/Bogota");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value)
    {
        date_default_timezone_set("America/Bogota");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function sale()
{
    return $this->belongsTo(Sale::class);
}
}
