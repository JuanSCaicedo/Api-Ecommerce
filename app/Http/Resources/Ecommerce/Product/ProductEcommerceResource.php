<?php

namespace App\Http\Resources\Ecommerce\Product;

use App\Models\Discount\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductEcommerceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $discount_g = null;

        $discount_collect = collect([]);

        // First check if there's an active flash discount
        // Buscar descuento flash directamente en la base de datos
        $flash_discount = Discount::where('type_campaing', 2)
            ->where('state', 1)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function ($query) {
                $query->whereHas('products', function ($q) {
                    $q->where('product_id', $this->resource->id);
                })
                    ->orWhereHas('categories', function ($q) {
                        $q->where('categorie_id', $this->resource->categorie_first_id);
                    })
                    ->orWhereHas('brands', function ($q) {
                        $q->where('brand_id', $this->resource->brand_id);
                    });
            })
            ->first();

        // Si existe un descuento flash aplicable, usarlo
        if ($flash_discount) {
            $discount_g = [
                'id' => $flash_discount->id,
                'code' => $flash_discount->code,
                'type_discount' => $flash_discount->type_discount,
                'discount' => $flash_discount->discount,
                'start_date' => $flash_discount->start_date,
                'end_date' => $flash_discount->end_date,
                'type_campaing' => $flash_discount->type_campaing,
            ];

            return $this->getFullProductArray($discount_g);
        }

        // If no flash discount applies, process regular discounts
        $discount_product = $this->resource->discount_product;
        if ($discount_product) {
            $discount_collect->push($discount_product);
        }

        $discount_categorie = $this->resource->discount_categorie;
        if ($discount_categorie) {
            $discount_collect->push($discount_categorie);
        }

        $discount_brand = $this->resource->discount_brand;
        if ($discount_brand) {
            $discount_collect->push($discount_brand);
        }

        if ($discount_collect->count() > 0) {
            $discount_g = $discount_collect->sortByDesc("discount")->values()->first();
        }

        return $this->getFullProductArray($discount_g);
    }

    private function getFullProductArray($discount_g): array
    {
        $variation_collect = collect([]);

        foreach ($this->resource->variations->groupBy("attribute_id") as $key => $variation_t) {

            $variation_collect->push([
                "attribute_id" => $variation_t[0]->attribute_id,
                "attribute" => $variation_t[0]->attribute ? [
                    "name" => $variation_t[0]->attribute->name,
                    "type_attribute" => $variation_t[0]->attribute->type_attribute,
                ] : NULL,

                "variations" =>  $variation_t->map(function ($variation) {

                    return [
                        "id" => $variation->id,
                        "product_id" => $variation->product_id,
                        "attribute_id" => $variation->attribute_id,
                        "attribute" => $variation->attribute ? [
                            "name" => $variation->attribute->name,
                            "type_attribute" => $variation->attribute->type_attribute,
                        ] : NULL,
                        "propertie_id" => $variation->propertie_id,
                        "propertie" => $variation->propertie ? [
                            "name" => $variation->propertie->name,
                            "code" => $variation->propertie->code,
                        ] : NULL,
                        "value_add" => $variation->value_add,
                        "add_price" => $variation->add_price,
                        "stock" => $variation->stock,
                        "state" => $variation->state,
                        "subvariation" => $variation->variation_children->count() > 0 ? [
                            "attribute_id" => $variation->variation_children->first()->attribute_id,
                            "attribute" => $variation->variation_children->first()->attribute ? [
                                "name" => $variation->variation_children->first()->attribute->name,
                                "type_attribute" => $variation->variation_children->first()->attribute->type,
                            ] : NULL,
                        ] : null,
                        "subvariations" => $variation->variation_children->map(function ($subvariation) {
                            return [
                                "id" => $subvariation->id,
                                "product_id" => $subvariation->product_id,
                                "attribute_id" => $subvariation->attribute_id,
                                "attribute" => $subvariation->attribute ? [
                                    "name" => $subvariation->attribute->name,
                                    "type_attribute" => $subvariation->attribute->type_attribute,
                                ] : NULL,
                                "propertie_id" => $subvariation->propertie_id,
                                "propertie" => $subvariation->propertie ? [
                                    "name" => $subvariation->propertie->name,
                                    "code" => $subvariation->propertie->code,
                                ] : NULL,
                                "value_add" => $subvariation->value_add,
                                "add_price" => $subvariation->add_price,
                                "stock" => $subvariation->stock,
                                "state" => $subvariation->state,
                            ];
                        }),
                    ];
                })
            ]);
        }

        $tags_parse = [];

        foreach (($this->resource->tags ? json_decode($this->resource->tags, true) : []) as $key => $tag) {
            array_push($tags_parse, $tag["item_text"]);
        }

        return [
            "id" => $this->resource->id,
            "title" => $this->resource->title,
            "slug" => $this->resource->slug,
            "sku" => $this->resource->sku,
            "price_cop" => $this->resource->price_cop,
            "price_usd" => $this->resource->price_usd,
            "resumen" => $this->resource->resumen,
            "imagen" => $this->resource->imagen ? env("APP_URL") . "storage/" . $this->resource->imagen : NULL,
            "state" => $this->resource->state,
            "description" => $this->resource->description,
            "tags" => $this->resource->tags ? json_decode($this->resource->tags) : [],
            "tags_parse" => $tags_parse,
            "brand_id" => $this->resource->brand_id,

            "brand" => $this->resource->brand ? [
                "id" => $this->resource->brand->id,
                "name" => $this->resource->brand->name,
            ] : NULL,

            "categorie_first_id" => $this->resource->categorie_first_id,

            "categorie_first" => $this->resource->categorie_first ? [
                "id" => $this->resource->categorie_first->id,
                "name" => $this->resource->categorie_first->name,
            ] : NULL,

            "categorie_second_id" => $this->resource->categorie_second_id,

            "categorie_second" => $this->resource->categorie_second ? [
                "id" => $this->resource->categorie_second->id,
                "name" => $this->resource->categorie_second->name,
            ] : NULL,

            "categorie_third_id" => $this->resource->categorie_third_id,

            "categorie_third" => $this->resource->categorie_third ? [
                "id" => $this->resource->categorie_third->id,
                "name" => $this->resource->categorie_third->name,
            ] : NULL,

            "stock" => $this->resource->stock,
            "created_at" => $this->resource->created_at->format("Y-m-d H:i:s"),
            "images" => $this->resource->images->map(function ($image) {
                return [
                    "id" => $image->id,
                    "imagen" => env("APP_URL") . "storage/" . $image->imagen,
                ];
            }),
            "discount_g" => $discount_g,
            "variations" => $variation_collect,
            "avg_reviews" => $this->resource->reviews_avg ? round($this->resource->reviews_avg, 2) : 0,
            "count_reviews" => $this->resource->reviews_count,
            "specifications" => $this->resource->specifications->map(function ($specification) {
                return [
                    "id" => $specification->id,
                    "product_id" => $specification->product_id,
                    "attribute_id" => $specification->attribute_id,
                    "attribute" => $specification->attribute ? [
                        "name" => $specification->attribute->name,
                        "type_attribute" => $specification->attribute->type_attribute,
                    ] : NULL,
                    "propertie_id" => $specification->propertie_id,
                    "propertie" => $specification->propertie ? [
                        "name" => $specification->propertie->name,
                        "code" => $specification->propertie->code,
                    ] : NULL,
                    "value_add" => $specification->value_add,
                ];
            }),
        ];
    }
}