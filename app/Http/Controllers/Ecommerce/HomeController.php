<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceResource;
use App\Models\Discount\Discount;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use App\Models\Slider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        date_default_timezone_set("America/Bogota");

        // Discount_flash
        $discount_flash = Discount::where("type_campaing", 2)
            ->where("state", 1)
            ->where("start_date", "<=", today())
            ->where("end_date", ">=", today())
            ->with(['products.product', 'categories', 'brands'])
            ->first();

        $discount_flash_products = collect();

        if ($discount_flash) {
            // Productos de descuento flash
            $discount_flash_products = $discount_flash->products->map(function ($product) {
                return new ProductEcommerceResource($product->product);
            });

            // Productos de las categorías del descuento flash
            $discount_flash->categories->each(function ($category) use (&$discount_flash_products) {
                $products = Product::where("state", 2)->where("categorie_first_id", $category->categorie_id)->get();
                $discount_flash_products->push(ProductEcommerceResource::collection($products));
            });

            // Productos de las marcas del descuento flash
            $discount_flash->brands->each(function ($brand) use (&$discount_flash_products) {
                $products = Product::where("state", 2)->where("brand_id", $brand->brand_id)->get();
                $discount_flash_products->push(ProductEcommerceResource::collection($products));
            });

            $discount_flash->end_date_format = Carbon::parse($discount_flash->end_date)->format("M d Y H:i:s");
        }

        // Cargar categorías principales para los menús
        $categories_menus = Categorie::whereNull("categorie_second_id")
            ->whereNull("categorie_third_id")
            ->orderBy("position", "desc")
            ->with(['categorie_seconds.categorie_seconds'])
            ->get();

        // Sliders
        $sliders_secundario = Slider::where("state", 1)->where("type_slider", 2)->orderBy("id", "asc")->get();
        $sliders_principal = Slider::where("state", 1)->where("type_slider", 1)->orderBy("id", "desc")->get();
        $sliders_products = Slider::where("state", 1)->where("type_slider", 3)->orderBy("id", "asc")->get();

        // Categorías aleatorias
        $categories_randoms = Cache::remember('categories_randoms', 60, function () {
            return Categorie::withCount(["product_categorie_firsts"])
                ->whereNull("categorie_second_id")
                ->whereNull("categorie_third_id")
                ->inRandomOrder()
                ->limit(5)
                ->get();
        });

        // Productos aleatorios
        $product_trending_new = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
        $product_trending_featured = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
        $product_trending_top_sellers = Product::where("state", 2)->inRandomOrder()->limit(8)->get();

        // Últimos productos
        $product_last_discounts = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
        $product_last_featured = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
        $product_last_selling = Product::where("state", 2)->inRandomOrder()->limit(3)->get();

        // Productos de electrónica
        $product_electronics = Product::where("state", 2)->where("categorie_first_id", 1)->inRandomOrder()->limit(6)->get();

        // Productos de carusel
        $product_carusel = Product::where("state", 2)
            ->whereIn("categorie_first_id", $categories_randoms->pluck("id"))
            ->inRandomOrder()
            ->get();

        return response()->json([
            "discount_flash" => $discount_flash,
            "discount_flash_products" => $discount_flash_products,
            "categories_menus" => $categories_menus->map(function ($departament) {
                return [
                    "id" => $departament->id,
                    "name" => $departament->name,
                    "icon" => $departament->icon,
                    "categories" => $departament->categorie_seconds->map(function ($categorie) {
                        return [
                            "id" => $categorie->id,
                            "name" => $categorie->name,
                            "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : null,
                            "subcategories" => $categorie->categorie_seconds->map(function ($subcategorie) {
                                return [
                                    "id" => $subcategorie->id,
                                    "name" => $subcategorie->name,
                                    "imagen" => $subcategorie->imagen ? env("APP_URL") . "storage/" . $subcategorie->imagen : null,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
            "sliders_principal" => $sliders_principal->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
            "categories_randoms" => $categories_randoms->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "product_categorie_firsts_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : null,
                ];
            }),
            "product_trending_new" => ProductEcommerceCollection::make($product_trending_new),
            "product_trending_featured" => ProductEcommerceCollection::make($product_trending_featured),
            "product_trending_top_sellers" => ProductEcommerceCollection::make($product_trending_top_sellers),
            "product_last_discounts" => ProductEcommerceCollection::make($product_last_discounts),
            "product_last_featured" => ProductEcommerceCollection::make($product_last_featured),
            "product_last_selling" => ProductEcommerceCollection::make($product_last_selling),
            "product_electronics" => ProductEcommerceCollection::make($product_electronics),
            "product_carusel" => ProductEcommerceCollection::make($product_carusel),
            "sliders_secundario" => $sliders_secundario->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title"  => $slider->title,
                    "subtitle"  => $slider->subtitle,
                    "label"  => $slider->label,
                    "imagen"  => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : NULL,
                    "link"  => $slider->link,
                    "state"  => $slider->state,
                    "color"  => $slider->color,
                    "type_slider"  => $slider->type_slider,
                    "price_original"  => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
            "sliders_products" => $sliders_products->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title"  => $slider->title,
                    "subtitle"  => $slider->subtitle,
                    "label"  => $slider->label,
                    "imagen"  => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : NULL,
                    "link"  => $slider->link,
                    "state"  => $slider->state,
                    "color"  => $slider->color,
                    "type_slider"  => $slider->type_slider,
                    "price_original"  => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
        ]);
    }

    public function menu(Request $request)
    {
        $categories_menus = Categorie::where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->orderBy("position", "desc")->get();

        return response()->json([
            "categories_menus" => $categories_menus->map(function ($departament) {
                return [
                    "id" => $departament->id,
                    "name" => $departament->name,
                    "icon" => $departament->icon,
                    "categories" => $departament->categorie_seconds->map(function ($categorie) {
                        return [
                            "id" => $categorie->id,
                            "name" => $categorie->name,
                            "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : null,
                            "subcategories" => $categorie->categorie_seconds->map(function ($subcategorie) {
                                return [
                                    "id" => $subcategorie->id,
                                    "name" => $subcategorie->name,
                                    "imagen" => $subcategorie->imagen ? env("APP_URL") . "storage/" . $subcategorie->imagen : null,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function show_product(Request $request, $slug)
    {
        // 1. Primero obtener el producto por slug
        $product = Product::where("slug", $slug)->where("state", 2)->first();

        if (!$product) {
            return response()->json([
                "message" => 403,
                "message_text" => "Producto no encontrado",
            ]);
        }

        // 2. Obtener el descuento si existe en el request
        $discount = null;
        $campaing_discount = $request->get("campaing_discount");

        if ($campaing_discount) {
            // Buscar el descuento que coincida con el código y que esté relacionado con el producto
            $discount = Discount::where("code", $campaing_discount)
                ->whereHas('products', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->where('state', 1)
                ->first();
        }

        // 3. Obtener productos relacionados
        $product_relateds = Product::where("categorie_first_id", $product->categorie_first_id)
            ->where("id", "!=", $product->id)
            ->where("state", 2)
            ->get();

        return response()->json([
            "message" => 200,
            "product" => ProductEcommerceResource::make($product),
            "product_relateds" => ProductEcommerceCollection::make($product_relateds),
            "discount_campaing" => $discount,
        ]);
    }
}
