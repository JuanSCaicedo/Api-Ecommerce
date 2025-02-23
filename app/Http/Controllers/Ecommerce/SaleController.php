<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Mail\SaleMail;
use App\Models\Sale\Cart;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleAddres;
use App\Models\Sale\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
        $sale = Sale::where("n_transaccion", $id)->first();

        return response()->json([
            "sale" => $sale,
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
}
