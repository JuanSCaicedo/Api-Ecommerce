<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
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
            "carts" => $carts
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
            }
        } else {
            $is_exist_cart_variations = Cart::where("product_variation_id", NULL)->where("user_id", $user->id)->where("product_id", $request->product_id)->first();

            if ($is_exist_cart_variations) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya se encuentra en el carrito, intente modificando la cantidad en el carrito."
                ]);
            }
        }

        $request->request->add(["user_id" => $user->id]);

        $cart = Cart::create($request->all());

        return response()->json([
            "cart" => $cart
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
            }
        } else {
            $is_exist_cart_variations = Cart::where("product_variation_id", NULL)
                ->where("user_id", $user->id)->where("product_id", $request->product_id)
                ->where("id", '<>', $id)->first();

            if ($is_exist_cart_variations) {
                return response()->json([
                    "message" => 403,
                    "message_text" => "El producto ya se encuentra en el carrito, intente modificando la cantidad en el carrito."
                ]);
            }
        }

        $cart = Cart::findOrFail($id);

        $cart->update($request->all());

        return response()->json([
            "cart" => $cart
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
