<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceResource;
use App\Models\Discount\Discount;
use App\Models\HomeView;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use App\Models\Sale\Review;
use App\Models\Slider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    private const CACHE_DURATION = 2592000; // 1 mes
    private const CACHE_DURATION_DAILY = 86400; // 24 hours

    public function home(Request $request)
    {
        date_default_timezone_set("America/Bogota");

        $cacheKey = 'home_response_' . now()->format('Y-m-d_H');

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            if ($this->isUnderMaintenance()) {
                return response()->json(["message" => "El sitio está en mantenimiento"], 503);
            }

            $discountFlash = $this->getCachedData('discount_flash', fn() => $this->getDiscountFlash());
            $discountFlashProducts = $this->getDiscountFlashProducts($discountFlash);

            $sliders = $this->getAllSliders();
            $categoriesRandom = $this->getCachedRandomCategories();
            $products = $this->getAllProductSections();
            $homeViews = HomeView::all();

            return response()->json([
                "discount_flash" => $this->formatDiscountFlash($discountFlash),
                "discount_flash_products" => ProductEcommerceResource::collection($discountFlashProducts),
                "sliders_principal" => $this->formatSliders($sliders['principal']),
                "sliders_secundario" => $this->formatSliders($sliders['secundario']),
                "sliders_products" => $this->formatSliders($sliders['products']),
                "categories_randoms" => $this->formatCategories($categoriesRandom),
                "product_trending_new" => ProductEcommerceCollection::make($products['trending_new']),
                "product_trending_featured" => ProductEcommerceCollection::make($products['trending_featured']),
                "product_trending_top_sellers" => ProductEcommerceCollection::make($products['trending_top_sellers']),
                "product_last_discounts" => ProductEcommerceCollection::make($products['last_discounts']),
                "product_last_featured" => ProductEcommerceCollection::make($products['last_featured']),
                "product_last_selling" => ProductEcommerceCollection::make($products['last_selling']),
                "product_electronics" => ProductEcommerceCollection::make($products['electronics']),
                "product_carusel" => ProductEcommerceCollection::make($products['carusel']),
                "home_views" => $this->formatHomeViews($homeViews),
            ]);
        });
    }

    public function show_product(Request $request, $slug)
    {
        $cacheKey = "product_{$slug}_" . ($request->get("campaing_discount") ?? 'no_discount');

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($request, $slug) {
            if ($this->isUnderMaintenance()) {
                return response()->json(["message" => "El sitio está en mantenimiento"], 503);
            }

            $product = $this->getProduct($slug);
            if (!$product) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "Producto no encontrado",
                ]);
            }

            $discount = $this->getProductDiscount($product, $request->get("campaing_discount"));
            $relatedProducts = $this->getRelatedProducts($product);
            $reviews = Review::where("product_id", $product->id)->orderBy("id", "desc")->get();

            // Calcular la distribución de calificaciones
            $ratingSummary = [
                5 => 0,
                4 => 0,
                3 => 0,
                2 => 0,
                1 => 0,
            ];

            foreach ($reviews as $review) {
                $ratingSummary[$review->rating]++;
            }

            $totalReviews = $reviews->count();
            $ratingDistribution = array_map(function ($count) use ($totalReviews) {
                return $totalReviews > 0 ? round(($count / $totalReviews) * 100, 2) : 0;
            }, $ratingSummary);

            return response()->json([
                "message" => 200,
                "product" => ProductEcommerceResource::make($product),
                "product_relateds" => ProductEcommerceCollection::make($relatedProducts),
                "discount_campaing" => $discount,
                "reviews" => $reviews->map(function ($review) {
                    return [
                        "id" => $review->id,
                        "user" => [
                            "full_name" => $review->user->name . ' ' . $review->user->surname,
                            'avatar' => $review->user->avatar ? env("APP_URL") . "storage/" . $review->user->avatar : 'https://cdn-icons-png.flaticon.com/512/18851/18851107.png',
                        ],
                        "message" => $review->message,
                        "rating" => $review->rating,
                        "created_at" => $review->created_at->format("Y-m-d h:i A")
                    ];
                }),
                "rating_distribution" => $ratingDistribution,
            ]);
        });
    }

    public function menu(Request $request)
    {
        $cacheKey = 'menu_response_' . now()->format('Y-m-d');

        return Cache::remember($cacheKey, self::CACHE_DURATION_DAILY, function () {
            if ($this->isUnderMaintenance()) {
                return response()->json(["message" => "El sitio está en mantenimiento"], 503);
            }

            $categoriesMenus = $this->getCachedMenuCategories();
            return response()->json([
                "categories_menus" => $this->formatMenuCategories($categoriesMenus)
            ]);
        });
    }

    private function isUnderMaintenance()
    {
        return Cache::remember('maintenance_status', 300, function () {
            return HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists();
        });
    }

    private function getCachedData($key, $callback, $duration = self::CACHE_DURATION)
    {
        return Cache::remember($key, $duration, $callback);
    }

    private function getDiscountFlash()
    {
        return Discount::where("type_campaing", 2)
            ->where("state", 1)
            ->where("start_date", "<=", today())
            ->where("end_date", ">=", today())
            ->with(['products.product', 'categories', 'brands'])
            ->first();
    }

    private function getDiscountFlashProducts($discountFlash)
    {
        if (!$discountFlash) return collect();

        return Cache::remember('discount_flash_products', self::CACHE_DURATION, function () use ($discountFlash) {
            $products = collect();

            // Productos directos
            $products = $products->merge($discountFlash->products->pluck('product'));

            // Productos por categoría
            foreach ($discountFlash->categories as $category) {
                $products = $products->merge(
                    Product::where("state", 2)
                        ->where("categorie_first_id", $category->categorie_id)
                        ->get()
                );
            }

            // Productos por marca
            foreach ($discountFlash->brands as $brand) {
                $products = $products->merge(
                    Product::where("state", 2)
                        ->where("brand_id", $brand->brand_id)
                        ->get()
                );
            }

            return $products->unique('id');
        });
    }

    private function getAllSliders()
    {
        return [
            'principal' => $this->getCachedData("sliders_principal", fn() => Slider::where("state", 1)->where("type_slider", 1)->orderBy("id", "desc")->get()),
            'secundario' => $this->getCachedData("sliders_secundario", fn() => Slider::where("state", 1)->where("type_slider", 2)->orderBy("id", "asc")->get()),
            'products' => $this->getCachedData("sliders_products", fn() => Slider::where("state", 1)->where("type_slider", 3)->orderBy("id", "asc")->get()),
        ];
    }

    private function getCachedRandomCategories()
    {
        return $this->getCachedData('categories_randoms', function () {
            return Categorie::withCount(["product_categorie_firsts"])
                ->whereNull(["categorie_second_id", "categorie_third_id"])
                ->where("state", 1)
                ->inRandomOrder()
                ->limit(5)
                ->get();
        });
    }

    private function getAllProductSections()
    {
        $categoriesRandom = $this->getCachedRandomCategories();

        return [
            'trending_new' => $this->getCachedRandomProducts('trending_new', 8),
            'trending_featured' => $this->getCachedRandomProducts('trending_featured', 8),
            'trending_top_sellers' => $this->getCachedRandomProducts('trending_top_sellers', 8),
            'last_discounts' => $this->getCachedRandomProducts('last_discounts', 3),
            'last_featured' => $this->getCachedRandomProducts('last_featured', 3),
            'last_selling' => $this->getCachedRandomProducts('last_selling', 3),
            'electronics' => $this->getCachedData(
                'electronics',
                fn() =>
                Product::where("state", 2)
                    ->where("categorie_first_id", 1)
                    ->inRandomOrder()
                    ->limit(6)
                    ->get()
            ),
            'carusel' => $this->getCachedData(
                'carusel',
                fn() =>
                Product::where("state", 2)
                    ->whereIn("categorie_first_id", $categoriesRandom->pluck("id"))
                    ->inRandomOrder()
                    ->get()
            ),
        ];
    }

    private function getCachedRandomProducts($key, $limit)
    {
        return $this->getCachedData(
            "products_{$key}",
            fn() =>
            Product::where("state", 2)
                ->inRandomOrder()
                ->limit($limit)
                ->get()
        );
    }

    private function getProduct($slug)
    {
        return Product::where("slug", $slug)
            ->where("state", 2)
            ->first();
    }

    private function getProductDiscount($product, $campaingCode)
    {
        if (!$campaingCode) return null;

        return Discount::where("code", $campaingCode)
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

    private function getRelatedProducts($product)
    {
        return $this->getCachedData(
            "related_products_{$product->id}",
            fn() =>
            Product::where("categorie_first_id", $product->categorie_first_id)
                ->where("id", "!=", $product->id)
                ->where("state", 2)
                ->limit(8)
                ->get()
        );
    }

    private function getCachedMenuCategories()
    {
        return $this->getCachedData(
            'menu_categories',
            fn() =>
            Categorie::with(['categorie_seconds.categorie_seconds'])
                ->whereNull(["categorie_second_id", "categorie_third_id"])
                ->where("state", 1)
                ->orderBy("position", "desc")
                ->get(),
            self::CACHE_DURATION_DAILY
        );
    }

    // Formatting methods
    private function formatDiscountFlash($discountFlash)
    {
        if (!$discountFlash) return null;

        $discountFlash->end_date_format = Carbon::parse($discountFlash->end_date)->format("M d Y H:i:s");
        return $discountFlash;
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

    private function formatHomeViews($views)
    {
        return $views->map(fn($view) => [
            "id" => $view->id,
            "name" => $view->name,
            "state" => $view->state,
        ]);
    }

    private function formatMenuCategories($categories)
    {
        return $categories->map(fn($dept) => [
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
        ]);
    }

    //Filtros productos
    public function config_filter_advance() {
        
    }

    public function filter_advance_product(Request $request) {
        
    }
}
