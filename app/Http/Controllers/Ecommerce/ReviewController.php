<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Sale\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->request->add(["user_id" => auth()->user()->id]);
        $review = Review::create($request->all());

        // Invalidar la caché del producto relacionado
        $this->invalidateProductCache($request->product_id);

        return response()->json([
            "review" => $review
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $review = Review::findOrfail($id);
        $review->update($request->all());

        // Invalidar la caché del producto relacionado
        $this->invalidateProductCache($review->product_id);

        return response()->json([
            "review" => $review
        ]);
    }

    /**
     * Invalidate all cache keys related to a specific product
     */
    private function invalidateProductCache($productId)
    {
        // Obtener el slug del producto para invalidar su caché específica
        $product = \App\Models\Product\Product::find($productId);

        if ($product) {
            // Invalidar la caché del producto específico
            Cache::forget("product_{$product->slug}_no_discount");

            // También invalidar cualquier versión con descuento
            $discounts = \App\Models\Discount\Discount::where('state', 1)->pluck('code');
            foreach ($discounts as $code) {
                Cache::forget("product_{$product->slug}_{$code}");
            }

            // Invalidar caches de secciones que podrían contener el producto
            $cacheKeys = [
                'trending_new',
                'trending_featured',
                'trending_top_sellers',
                'last_discounts',
                'last_featured',
                'last_selling',
                'electronics',
                'carusel',
                "related_products_{$productId}"
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget("products_{$key}");
            }

            // Invalidar la caché de la página principal para forzar su renovación
            $hourFormat = now()->format('Y-m-d_H');
            Cache::forget("home_response_{$hourFormat}");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
