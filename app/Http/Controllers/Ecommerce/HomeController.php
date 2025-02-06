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

        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

        // Métodos de caché reutilizables
        $discountFlash = $this->getCachedData('discount_flash', function () {
            return Discount::where("type_campaing", 2)
                ->where("state", 1)
                ->where("start_date", "<=", today())
                ->where("end_date", ">=", today())
                ->with(['products.product', 'categories', 'brands'])
                ->first();
        });

        $discountFlashProducts = $this->getDiscountFlashProducts($discountFlash);

        // Sliders
        $slidersPrincipal = $this->getCachedData('sliders_principal', function () {
            return Slider::where("state", 1)->where("type_slider", 1)->orderBy("id", "desc")->get();
        });

        $slidersSecundario = $this->getCachedData('sliders_secundario', function () {
            return Slider::where("state", 1)->where("type_slider", 2)->orderBy("id", "asc")->get();
        });

        $slidersProducts = $this->getCachedData('sliders_products', function () {
            return Slider::where("state", 1)->where("type_slider", 3)->orderBy("id", "asc")->get();
        });

        // Categorías aleatorias
        $categoriesRandom = $this->getCachedData('categories_randoms', function () {
            return Categorie::withCount(["product_categorie_firsts"])
                ->where("categorie_second_id", NULL)
                ->where("categorie_third_id", NULL)
                ->where("state", 1)
                ->inRandomOrder()
                ->limit(5)
                ->get();
        });

        // Productos trending y últimos
        $productTrendingNew = $this->getCachedRandomProducts('product_trending_new');
        $productTrendingFeatured = $this->getCachedRandomProducts('product_trending_featured');
        $productTrendingTopSellers = $this->getCachedRandomProducts('product_trending_top_sellers');

        $productLastDiscounts = $this->getCachedRandomProducts('product_last_discounts', 3);
        $productLastFeatured = $this->getCachedRandomProducts('product_last_featured', 3);
        $productLastSelling = $this->getCachedRandomProducts('product_last_selling', 3);

        // Productos de electrónicos
        $productElectronics = $this->getCachedData('product_electronics', function () {
            return Product::where("state", 2)
                ->where("categorie_first_id", 1)
                ->inRandomOrder()
                ->limit(6)
                ->get();
        });

        // Productos de carrusel
        $productCarusel = $this->getCachedData('product_carusel', function () use ($categoriesRandom) {
            return Product::where("state", 2)
                ->whereIn("categorie_first_id", $categoriesRandom->pluck("id"))
                ->inRandomOrder()
                ->get();
        });

        $homeViews = HomeView::get();

        // Formatear datos de sliders
        $formatSliders = function ($sliders) {
            return $sliders->map(function ($slider) {
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
                    "type_width" => $slider->type_width ?? null,
                ];
            });
        };

        // Preparar respuesta JSON
        return response()->json([
            "discount_flash" => $this->formatDiscountFlash($discountFlash),
            "discount_flash_products" => ProductEcommerceResource::collection($discountFlashProducts),
            "sliders_principal" => $formatSliders($slidersPrincipal),
            "categories_randoms" => $categoriesRandom->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "product_categorie_firsts_count" => $categorie->product_categorie_firsts_count,
                    // "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : null,
                ];
            }),
            "product_trending_new" => ProductEcommerceCollection::make($productTrendingNew),
            "product_trending_featured" => ProductEcommerceCollection::make($productTrendingFeatured),
            "product_trending_top_sellers" => ProductEcommerceCollection::make($productTrendingTopSellers),
            "product_last_discounts" => ProductEcommerceCollection::make($productLastDiscounts),
            "product_last_featured" => ProductEcommerceCollection::make($productLastFeatured),
            "product_last_selling" => ProductEcommerceCollection::make($productLastSelling),
            "product_electronics" => ProductEcommerceCollection::make($productElectronics),
            "product_carusel" => ProductEcommerceCollection::make($productCarusel),
            "sliders_secundario" => $formatSliders($slidersSecundario),
            "sliders_products" => $formatSliders($slidersProducts),
            "home_views" => $homeViews->map(function ($homeView) {
                return [
                    "id" => $homeView->id,
                    "name" => $homeView->name,
                    "state" => $homeView->state,
                ];
            }),
        ]);
    }

    /**
     * Obtener datos en caché o generar nuevos
     */
    private function getCachedData(string $key, callable $callback, int $minutes = 60 * 24)
    {
        return Cache::remember($key, now()->addMinutes($minutes), $callback);
    }

    /**
     * Obtener productos aleatorios en caché
     */
    private function getCachedRandomProducts(string $key, int $limit = 8)
    {
        return $this->getCachedData($key, function () use ($limit) {
            return Product::where("state", 2)
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Formatear descuento flash
     */
    private function formatDiscountFlash($discountFlash)
    {
        if (!$discountFlash) return null;

        $discountFlash->end_date_format = Carbon::parse($discountFlash->end_date)->format("M d Y H:i:s");
        return $discountFlash;
    }

    /**
     * Obtener productos de descuento flash
     */
    private function getDiscountFlashProducts($discountFlash)
    {
        if (!$discountFlash) return collect();

        return $this->getCachedData('discount_flash_products', function () use ($discountFlash) {
            $products = collect();

            // Productos directos del descuento
            foreach ($discountFlash->products as $aux_product) {
                $products->push(ProductEcommerceResource::make($aux_product->product));
            }

            // Productos por categoría
            foreach ($discountFlash->categories as $aux_categorie) {
                $categoryProducts = Product::where("state", 2)
                    ->where("categorie_first_id", $aux_categorie->categorie_id)
                    ->get();

                foreach ($categoryProducts as $product) {
                    $products->push(ProductEcommerceResource::make($product));
                }
            }

            // Productos por marca
            foreach ($discountFlash->brands as $aux_brand) {
                $brandProducts = Product::where("state", 2)
                    ->where("brand_id", $aux_brand->brand_id)
                    ->get();

                foreach ($brandProducts as $product) {
                    $products->push(ProductEcommerceResource::make($product));
                }
            }

            return $products;
        });
    }

    public function menu(Request $request)
    {
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

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
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

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
}
