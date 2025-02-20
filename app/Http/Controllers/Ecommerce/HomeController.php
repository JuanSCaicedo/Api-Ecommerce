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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        date_default_timezone_set("America/Bogota");

        $cacheKey = 'home_response_' . now()->format('Y-m-d_H');

        return Cache::remember($cacheKey, 3600, function () {
            if ($this->isUnderMaintenance()) {
                return response()->json(["message" => "El sitio está en mantenimiento"], 503);
            }

            $discountFlash = $this->getCachedData('discount_flash', fn() => $this->getDiscountFlash());
            $discountFlashProducts = $this->getDiscountFlashProducts($discountFlash);

            $sliders = [
                'principal' => $this->getCachedSliders(1),
                'secundario' => $this->getCachedSliders(2),
                'products' => $this->getCachedSliders(3),
            ];

            $categoriesRandom = $this->getCachedData('categories_randoms', fn() => $this->getRandomCategories());

            $productSections = [
                'trending_new' => $this->getCachedRandomProducts('product_trending_new'),
                'trending_featured' => $this->getCachedRandomProducts('product_trending_featured'),
                'trending_top_sellers' => $this->getCachedRandomProducts('product_trending_top_sellers'),
                'last_discounts' => $this->getCachedRandomProducts('product_last_discounts', 3),
                'last_featured' => $this->getCachedRandomProducts('product_last_featured', 3),
                'last_selling' => $this->getCachedRandomProducts('product_last_selling', 3),
            ];

            $productElectronics = $this->getCachedData('product_electronics', fn() => $this->getElectronicsProducts());

            $productCarusel = $this->getCachedData('product_carusel', fn() => $this->getCaruselProducts($categoriesRandom));

            $homeViews = HomeView::all();

            return response()->json([
                "discount_flash" => $this->formatDiscountFlash($discountFlash),
                "discount_flash_products" => ProductEcommerceResource::collection($discountFlashProducts),
                "sliders_principal" => $this->formatSliders($sliders['principal']),
                "sliders_secundario" => $this->formatSliders($sliders['secundario']),
                "sliders_products" => $this->formatSliders($sliders['products']),
                "categories_randoms" => $this->formatCategories($categoriesRandom),
                "product_trending_new" => ProductEcommerceCollection::make($productSections['trending_new']),
                "product_trending_featured" => ProductEcommerceCollection::make($productSections['trending_featured']),
                "product_trending_top_sellers" => ProductEcommerceCollection::make($productSections['trending_top_sellers']),
                "product_last_discounts" => ProductEcommerceCollection::make($productSections['last_discounts']),
                "product_last_featured" => ProductEcommerceCollection::make($productSections['last_featured']),
                "product_last_selling" => ProductEcommerceCollection::make($productSections['last_selling']),
                "product_electronics" => ProductEcommerceCollection::make($productElectronics),
                "product_carusel" => ProductEcommerceCollection::make($productCarusel),
                "home_views" => $homeViews->map(fn($view) => [
                    "id" => $view->id,
                    "name" => $view->name,
                    "state" => $view->state,
                ]),
            ]);
        });
    }

    public function menu(Request $request)
    {
        $cacheKey = 'menu_response_' . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 86400, function () {
            if ($this->isUnderMaintenance()) {
                return response()->json(["message" => "El sitio está en mantenimiento"], 503);
            }

            $categoriesMenus = Categorie::with(['categorie_seconds.categorie_seconds'])
                ->whereNull("categorie_second_id")
                ->whereNull("categorie_third_id")
                ->where("state", 1)
                ->orderBy("position", "desc")
                ->get();

            return response()->json([
                "categories_menus" => $categoriesMenus->map(fn($dept) => [
                    "id" => $dept->id,
                    "name" => $dept->name,
                    "icon" => $dept->icon,
                    "categories" => $dept->categorie_seconds->map(fn($cat) => [
                        "id" => $cat->id,
                        "name" => $cat->name,
                        "imagen" => $cat->imagen ? env("APP_URL") . "storage/" . $cat->imagen : null,
                        "subcategories" => $cat->categorie_seconds->map(fn($subcat) => [
                            "id" => $subcat->id,
                            "name" => $subcat->name,
                            "imagen" => $subcat->imagen ? env("APP_URL") . "storage/" . $subcat->imagen : null,
                        ]),
                    ]),
                ]),
            ]);
        });
    }

    private function isUnderMaintenance()
    {
        return HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists();
    }

    private function getCachedData($key, $callback)
    {
        return Cache::remember($key, 3600, $callback);
    }

    private function getCachedRandomProducts($key, $limit = 6)
    {
        return $this->getCachedData($key, fn() => Product::where("state", 2)->inRandomOrder()->limit($limit)->get());
    }

    private function getCachedSliders($type)
    {
        return $this->getCachedData("sliders_type_$type", fn() => Slider::where("state", 1)->where("type_slider", $type)->orderBy("id", "desc")->get());
    }

    private function getRandomCategories()
    {
        return Categorie::withCount(["product_categorie_firsts"])
            ->whereNull("categorie_second_id")
            ->whereNull("categorie_third_id")
            ->where("state", 1)
            ->inRandomOrder()
            ->limit(5)
            ->get();
    }

    private function getDiscountFlash()
    {
        return Discount::where("type_campaing", 2)
            ->where("state", 1)
            ->where("start_date", "<=", today())
            ->where("end_date", ">=", today())
            ->with(['products.product' => fn($query) => $query->select('id', 'name', 'price', 'stock', 'state')->where('state', 2)])
            ->first();
    }

    private function getDiscountFlashProducts($discountFlash)
    {
        return $discountFlash ? $discountFlash->products->pluck('product') : collect();
    }

    private function getElectronicsProducts()
    {
        return Product::where("state", 2)
            ->where("categorie_first_id", 1)
            ->select('id', 'name', 'price', 'stock', 'state', 'categorie_first_id')
            ->inRandomOrder()
            ->limit(6)
            ->get();
    }

    private function getCaruselProducts($categoriesRandom)
    {
        return Product::where("state", 2)
            ->whereIn("categorie_first_id", $categoriesRandom->pluck("id"))
            ->select('id', 'name', 'price', 'stock', 'state', 'categorie_first_id')
            ->inRandomOrder()
            ->get();
    }

    private function formatDiscountFlash($discountFlash)
    {
        return $discountFlash ? [
            "id" => $discountFlash->id,
            "title" => $discountFlash->title,
            "start_date" => $discountFlash->start_date,
            "end_date" => $discountFlash->end_date,
        ] : null;
    }

    private function formatSliders($sliders)
    {
        return $sliders->map(fn($slider) => [
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
        ]);
    }

    private function formatCategories($categories)
    {
        return $categories->map(fn($cat) => [
            "id" => $cat->id,
            "name" => $cat->name,
            "product_categorie_firsts_count" => $cat->product_categorie_firsts_count,
            "imagen" => $cat->imagen ? env("APP_URL") . "storage/" . $cat->imagen : null,
        ]);
    }
}
