<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceCollection;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceResource;
use App\Models\Product\Product;
use App\Models\Product\ProductVariation;
use App\Models\Sale\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();

        $carts = Cart::where("user_id", $user->id)->get();

        return response()->json([
            "carts" => CartEcommerceCollection::make($carts),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if ($request->product_variation_id) {
            $is_exist_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)->where("user_id", $user->id)->where("product_id", $request->product_id)->first();

            if ($is_exist_cart_variations) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto y la variación ya se encuentra en el carrito, intente modificando la cantidad en el carrito."
                ]);
            } else {
                $variation = ProductVariation::find($request->product_variation_id);

                if ($variation && $variation->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La cantidad solicitada supera el stock disponible."
                    ]);
                }
            }
        } else {
            $is_exist_cart_simple = Cart::where("product_variation_id", NULL)->where("user_id", $user->id)->where("product_id", $request->product_id)->first();

            if ($is_exist_cart_simple) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya se encuentra en el carrito, intente modificando la cantidad en el carrito."
                ]);
            } else {
                $product = Product::find($request->product_id);

                if ($product && $product->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La cantidad solicitada supera el stock disponible."
                    ]);
                }
            }
        }

        $request->request->add(["user_id" => $user->id]);

        $cart = Cart::create($request->all());

        return response()->json([
            "cart" => CartEcommerceResource::make($cart)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth('api')->user();

        if ($request->product_variation_id) {
            $is_exist_cart_variations = Cart::where("product_variation_id", $request->product_variation_id)
                ->where("user_id", $user->id)->where("product_id", $request->product_id)
                ->where("id", '<>', $id)->first();

            if ($is_exist_cart_variations) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto y la variación ya se encuentra en el carrito, intente modificando la cantidad en el carrito."
                ]);
            } else {
                $variation = ProductVariation::find($request->product_variation_id);

                if ($variation && $variation->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La cantidad solicitada supera el stock disponible."
                    ]);
                }
            }
        } else {
            $is_exist_cart_simple = Cart::where("product_variation_id", NULL)
                ->where("user_id", $user->id)->where("product_id", $request->product_id)
                ->where("id", '<>', $id)->first();

            if ($is_exist_cart_simple) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya se encuentra en el carrito, intente modificando la cantidad en el carrito."
                ]);
            } else {
                $product = Product::find($request->product_id);

                if ($product && $product->stock < $request->quantity) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La cantidad solicitada supera el stock disponible."
                    ]);
                }
            }
        }

        $cart = Cart::findOrFail($id);

        $cart->update($request->all());

        return response()->json([
            "cart" => CartEcommerceResource::make($cart)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cart = Cart::findOrFail($id);

        $cart->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
