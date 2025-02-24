<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Sale\SaleResource;
use App\Mail\SaleMail;
use App\Models\HomeView;
use App\Models\Sale\Cart;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleAddres;
use App\Models\Sale\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class SaleController extends Controller
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
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

        $request->request->add(['user_id' => auth("api")->user()->id]);
        $sale = Sale::create($request->all());

        $carts = Cart::where('user_id', auth("api")->user()->id)->get();

        foreach ($carts as $cart) {
            $new_detail = [];
            $new_detail = $cart->toArray();
            $new_detail['sale_id'] = $sale->id;
            SaleDetail::create($new_detail);
        }

        $sale_addres = $request->sale_address;
        $sale_addres['sale_id'] = $sale->id;
        $sale_address = SaleAddres::create($sale_addres);

        //Enviar correo de la compra
        $sale_new = Sale::findOrfail($sale->id);
        Mail::to(auth("api")->user()->email)->send(new SaleMail(auth("api")->user(), $sale_new));

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

        $sale = Sale::where("n_transaccion", $id)->first();

        return response()->json([
            "sale" => SaleResource::make($sale),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function mercadopago(Request $request)
    {
        MercadoPagoConfig::setAccessToken(env("MERCADOPAGO_KEY"));
        $client = new PreferenceClient();
        $client->auto_return = "approved";

        // $carts = Cart::where('user_id', auth("api")->user()->id)->get();

        // $array_carts = [];

        // foreach ($carts as $cart) {
        //     array_push($array_carts, [
        //         "title" => $cart->product->title,
        //         "quantity" => $cart->quantity,
        //         "currency_id" => $cart->currency,
        //         "unit_price" => $cart->total
        //     ]);
        // }

        $datos = array(
            "items" => [
                [
                    "title" => "Meu produto",
                    "quantity" => 1,
                    "currency_id" => "COP",
                    "unit_price" => 100
                ]
            ],
            "back_urls" => array(
                "success" => env("URL_TIENDA") . "mercado-pago-success",
                "failure" => env("URL_TIENDA") . "mercado-pago-failure",
                "pending" => env("URL_TIENDA") . "mercado-pago-pending"
            ),
            "redirect_urls" => array(
                "success" => env("URL_TIENDA") . "mercado-pago-success",
                "failure" => env("URL_TIENDA") . "mercado-pago-failure",
                "pending" => env("URL_TIENDA") . "mercado-pago-pending"
            ),
            "auto_return" => "approved",
            "external_reference" => uniqid(),
        );
        $preference = $client->create($datos);

        return response()->json([
            "preference" => $preference,
        ]);
    }
}
