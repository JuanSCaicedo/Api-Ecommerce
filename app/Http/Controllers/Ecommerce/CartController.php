<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceCollection;
use App\Http\Resources\Ecommerce\Cart\CartEcommerceResource;
use App\Models\Cupone\Cupone;
use App\Models\HomeView;
use App\Models\Product\Product;
use App\Models\Product\ProductVariation;
use App\Models\Sale\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    //Funcion para limitar las peticiones a 10 por minuto y bloquear por 1 minuto
    public function __construct()
    {
        $this->middleware('throttle:20,1')->only(['update', 'store', 'destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

        $user = auth('api')->user();

        $carts = Cart::where("user_id", $user->id)->orderBy("created_at", "ASC")->get();

        return response()->json([
            "carts" => CartEcommerceCollection::make($carts),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

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

        $request->request->add([
            "user_id" => $user->id,
            "id" => Str::uuid()->toString()
        ]);

        $cart = Cart::create($request->all());

        return response()->json([
            "cart" => CartEcommerceResource::make($cart)
        ]);
    }

    public function apply_cupon(Request $request)
    {
        $cupon = Cupone::where("code", $request->code_cupon)->where("state", 1)->first();

        if (!$cupon) {
            return response()->json([
                "message" => 403,
                "message_text" => "El cupón no es válido."
            ]);
        }

        // Verificar si el cupón tiene límite de usos y si aún tiene usos disponibles
        if ($cupon->type_count == 2 && $cupon->num_use <= 0) {
            return response()->json([
                "message" => 403,
                "message_text" => "El cupón ha alcanzado el límite de usos."
            ]);
        }

        $user = auth('api')->user();
        $carts = Cart::where("user_id", $user->id)->get();
        $cupon_applied = false;

        foreach ($carts as $cart) {
            //Nivel de producto
            if ($cupon->type_cupone == 1) {
                $is_exist_product_cupon = false;

                foreach ($cupon->products as $cupon_product) {
                    if ($cupon_product->product_id == $cart->product_id) {
                        $is_exist_product_cupon = true;
                        break;
                    }
                }

                if ($is_exist_product_cupon) {
                    $this->updateCartWithDiscount($cart, $cupon);
                    $cupon_applied = true;
                }
            }

            //Nivel de categoria          
            if ($cupon->type_cupone == 2) {
                $is_exist_category_cupon = false;

                foreach ($cupon->categories as $cupon_product) {
                    if ($cupon_product->categorie_id == $cart->product->categorie_first_id) {
                        $is_exist_category_cupon = true;
                        break;
                    }
                }

                if ($is_exist_category_cupon) {
                    $this->updateCartWithDiscount($cart, $cupon);
                    $cupon_applied = true;
                }
            }

            //Nivel de marca           
            if ($cupon->type_cupone == 3) {
                $is_exist_brand_cupon = false;

                foreach ($cupon->brands as $cupon_product) {
                    if ($cupon_product->brand_id == $cart->product->brand_id) {
                        $is_exist_brand_cupon = true;
                        break;
                    }
                }

                if ($is_exist_brand_cupon) {
                    $this->updateCartWithDiscount($cart, $cupon);
                    $cupon_applied = true;
                }
            }
        }

        // Si el cupón se aplicó y tiene type_count = 2, decrementar num_use
        if ($cupon_applied && $cupon->type_count == 2) {
            $cupon->update([
                'num_use' => $cupon->num_use - 1
            ]);
        }

        return response()->json([
            "message" => 200,
            "message_text" => "Cupón aplicado correctamente!."
        ]);
    }

    // Método auxiliar para actualizar el carrito con el descuento
    private function updateCartWithDiscount($cart, $cupon)
    {
        $subtotal = 0;

        //Porcentaje
        if ($cupon->type_discount == 1) {
            $subtotal = $cart->price_unit - $cart->price_unit * ($cupon->discount * 0.01);
        }

        //Monto fijo
        if ($cupon->type_discount == 2) {
            $subtotal = $cart->price_unit - $cupon->discount;
        }

        $cart->update([
            "type_discount" => $cupon->type_discount,
            "discount" => $cupon->discount,
            "code_cupon" => $cupon->code,
            "subtotal" => $subtotal,
            "total" => $subtotal * $cart->quantity,
            "type_campaing" => NULL,
            "code_discount" => NULL,
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
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

        $cart = Cart::findOrFail($id);

        $cart->delete();

        return response()->json([
            "message" => 200,
        ]);
    }

    public function delete_all()
    {
        $user = auth('api')->user();
        $carts = Cart::where("user_id", $user->id)->get();

        foreach ($carts as $cart) {
            $cart->delete();
        }

        return response()->json([
            "message" => 200,
        ]);
    }
}
