<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
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
     * Display the specified resource.
     */
    public function show(string $id)
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

        // Limpiar toda la caché de productos
        $this->clearAllProductCaches();

        return response()->json([
            "review" => $review
        ]);
    }

    public function update(Request $request, string $id)
    {
        $review = Review::findOrfail($id);
        $review->update($request->all());

        // Limpiar toda la caché de productos
        $this->clearAllProductCaches();

        return response()->json([
            "review" => $review
        ]);
    }

    private function clearAllProductCaches()
    {
        // Limpiar la caché de la página principal
        $currentHour = now()->format('Y-m-d_H');
        Cache::forget("home_response_{$currentHour}");

        // Limpiar todos los productos en caché
        // Esto borra cualquier caché que comience con "product_"
        Cache::flush();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
