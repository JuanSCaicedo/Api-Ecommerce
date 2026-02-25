<?php

namespace App\Http\Resources\Cupone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CuponeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->resource->id,
            "code" => $this->code,
            "type_discount" => $this->resource->type_discount,
            "discount" => $this->resource->discount,
            "type_count" => $this->resource->type_count,
            "num_use" => $this->resource->num_use,
            "type_cupone" => $this->resource->type_cupone,
            "state" => $this->resource->state,
            "created_at" => $this->resource->created_at->format('Y-m-d h:i A'),

            "products" => $this->resource->products->map(function($product_aux) {
                return [
                    "id" => $product_aux->product->id,
                    "title" => $product_aux->product->title,
                    "imagen" => $product_aux->product->imagen ? Storage::disk('oci')->url($product_aux->product->imagen) : null,
                    "id_aux" => $product_aux->product_id,
                ];
            }),

            "categories" => $this->resource->categories->map(function($categorie_aux) {
                return [
                    "id" => $categorie_aux->categorie->id,
                    "name" => $categorie_aux->categorie->name,
                    "imagen" => $categorie_aux->categorie->imagen ? Storage::disk('oci')->url($categorie_aux->categorie->imagen) : null,
                    "id_aux" => $categorie_aux->id,
                ];
            }),

            "brands" => $this->resource->brands->map(function($brand_aux) {
                return [
                    "id" => $brand_aux->brand->id,
                    "name" => $brand_aux->brand->name,
                    "id_aux" => $brand_aux->id,
                ];
            }),
        ];
    }
}
