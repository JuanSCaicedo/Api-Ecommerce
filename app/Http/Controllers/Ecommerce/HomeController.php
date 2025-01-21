<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceResource;
use App\Models\Discount\Discount;
use App\Models\HomeView;
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
        if (Cache::has('discount_flash')) {
            $discount_flash = Cache::get('discount_flash');
        } else {
            $discount_flash = Discount::where("type_campaing", 2)
                ->where("state", 1)
                ->where("start_date", "<=", today())
                ->where("end_date", ">=", today())
                ->with(['products.product', 'categories', 'brands'])
                ->first();
            Cache::put('discount_flash', $discount_flash);
        }

        // Productos de descuento flash
        $discount_flash_products = collect();

        if ($discount_flash) {
            // Productos de descuento flash
            if (Cache::has('discount_flash_products')) {
                $discount_flash_products = Cache::get('discount_flash_products');
            } else {
                foreach ($discount_flash->products as $key => $aux_product) {
                    $discount_flash_products->push(ProductEcommerceResource::make($aux_product->product));
                }

                foreach ($discount_flash->categories as $key => $aux_categorie) {
                    $products_of_categories = Product::where("state", 2)->where("categorie_first_id", $aux_categorie->categorie_id)->get();
                    foreach ($products_of_categories as $key => $product) {
                        $discount_flash_products->push(ProductEcommerceResource::make($product));
                    }
                }

                foreach ($discount_flash->brands as $key => $aux_brand) {
                    $products_of_brands = Product::where("state", 2)->where("brand_id", $aux_brand->brand_id)->get();
                    foreach ($products_of_brands as $key => $product) {
                        $discount_flash_products->push(ProductEcommerceResource::make($product));
                    }
                }

                Cache::put('discount_flash_products', $discount_flash_products);
            }

            $discount_flash->end_date_format = Carbon::parse($discount_flash->end_date)->format("M d Y H:i:s");
        }

        // Sliders
        if (Cache::has('sliders_secundario')) {
            $sliders_secundario = Cache::get('sliders_secundario');
        } else {
            $sliders_secundario = Slider::where("state", 1)->where("type_slider", 2)->orderBy("id", "asc")->get();
            Cache::put('sliders_secundario', $sliders_secundario);
        }

        if (Cache::has('sliders_principal')) {
            $sliders_principal = Cache::get('sliders_principal');
        } else {
            $sliders_principal = Slider::where("state", 1)->where("type_slider", 1)->orderBy("id", "desc")->get();
            Cache::put('sliders_principal', $sliders_principal);
        }

        if (Cache::has('sliders_products')) {
            $sliders_products = Cache::get('sliders_products');
        } else {
            $sliders_products = Slider::where("state", 1)->where("type_slider", 3)->orderBy("id", "asc")->get();
            Cache::put('sliders_products', $sliders_products);
        }

        // Categorías aleatorias
        if (Cache::has('categories_randoms')) {
            $categories_randoms = Cache::get('categories_randoms');
        } else {
            $categories_randoms = Categorie::withCount(["product_categorie_firsts"])
                ->where("categorie_second_id", NULL)
                ->where("categorie_third_id", NULL)
                ->where("state", 1)
                ->inRandomOrder()->limit(5)->get();
            Cache::put('categories_randoms', $categories_randoms);
        }

        // Productos aleatorios trending
        if (Cache::has('product_trending_new')) {
            $product_trending_new = Cache::get('product_trending_new');
        } else {
            $product_trending_new = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
            Cache::put('product_trending_new', $product_trending_new);
        }

        if (Cache::has('product_trending_featured')) {
            $product_trending_featured = Cache::get('product_trending_featured');
        } else {
            $product_trending_featured = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
            Cache::put('product_trending_featured', $product_trending_featured);
        }

        if (Cache::has('product_trending_top_sellers')) {
            $product_trending_top_sellers = Cache::get('product_trending_top_sellers');
        } else {
            $product_trending_top_sellers = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
            Cache::put('product_trending_top_sellers', $product_trending_top_sellers);
        }

        // Últimos productos
        if (Cache::has('product_last_discounts')) {
            $product_last_discounts = Cache::get('product_last_discounts');
        } else {
            $product_last_discounts = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
            Cache::put('product_last_discounts', $product_last_discounts);
        }

        if (Cache::has('product_last_featured')) {
            $product_last_featured = Cache::get('product_last_featured');
        } else {
            $product_last_featured = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
            Cache::put('product_last_featured', $product_last_featured);
        }

        if (Cache::has('product_last_selling')) {
            $product_last_selling = Cache::get('product_last_selling');
        } else {
            $product_last_selling = Product::where("state", 2)->inRandomOrder()->limit(3)->get();
            Cache::put('product_last_selling', $product_last_selling);
        }

        // Productos de electrónics
        if (Cache::has('product_electronics')) {
            $product_electronics = Cache::get('product_electronics');
        } else {
            $product_electronics = Product::where("state", 2)->where("categorie_first_id", 1)->inRandomOrder()->limit(6)->get();
            Cache::put('product_electronics', $product_electronics);
        }

        // Productos de carusel
        if (Cache::has('product_carusel')) {
            $product_carusel = Cache::get('product_carusel');
        } else {
            $product_carusel = Product::where("state", 2)
                ->whereIn("categorie_first_id", $categories_randoms->pluck("id"))
                ->inRandomOrder()
                ->get();
            Cache::put('product_carusel', $product_carusel);
        }

        $homeViews = HomeView::get();

        return response()->json([
            "discount_flash" => $discount_flash,
            "discount_flash_products" => $discount_flash_products,
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
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "type_width" => $slider->type_width,
                    "price_original" => $slider->price_original,
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
            "home_views" => $homeViews->map(function ($homeView) {
                return [
                    "id" => $homeView->id,
                    "name" => $homeView->name,
                    "state" => $homeView->state,
                ];
            }),
        ]);
    }

    public function menu(Request $request)
    {
        // Cargar categorías principales para los menús
        if (Cache::has('categories_menus')) {
            $categories_menus = Cache::get('categories_menus');
        } else {
            $categories_menus = Categorie::where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->orderBy("position", "desc")->where("state", 1)->get();
            Cache::put('categories_menus', $categories_menus);
        }

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
                ->where('state', 1)
                ->where(function ($query) use ($product) {
                    $query->whereHas('products', function ($q) use ($product) {
                        $q->where('product_id', $product->id);
                    })
                        ->orWhereHas('categories', function ($q) use ($product) {
                            $q->where('categorie_id', $product->categorie_first_id);
                        })
                        ->orWhereHas('brands', function ($q) use ($product) {
                            $q->where('brand_id', $product->brand_id);
                        });
                })
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

    public function homeView()
    {
        $homeViews = HomeView::where("name", "vista_mantenimiento")->get();

        return response()->json([
            "home_views" => $homeViews->map(function ($homeView) {
                return [
                    "id" => $homeView->id,
                    "name" => $homeView->name,
                    "state" => $homeView->state,
                ];
            }),
        ]);
    }
}
