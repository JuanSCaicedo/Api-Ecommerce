<?php

namespace App\Http\Controllers\Admin\Discount;

use App\Http\Controllers\Controller;
use App\Http\Resources\Discount\DiscountCollection;
use App\Http\Resources\Discount\DiscountResource;
use App\Models\Discount\Discount;
use App\Models\Discount\DiscountBrand;
use App\Models\Discount\DiscountCategorie;
use App\Models\Discount\DiscountProduct;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $discounts = Discount::orderBy("id", "desc")->paginate(25);
        $discounts = Discount::where("code", "like", "%$request->search%")->orderBy("id", "desc")->paginate(25);

        return response()->json([
            "total" => $discounts->total(),
            "discounts" => DiscountCollection::make($discounts),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar que los productos o categorias o marcas no se registren dentro del mismo periodo de tiempo
        if ($request->discount_type == 1) { //Validacion a nivel de productos
            foreach ($request->product_selected as $key => $product_selected) {
                $EXIST_DISCOUNT_START_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("products", function ($query) use ($product_selected) {
                        $query->where("product_id", $product_selected["id"]);
                    })->whereBetween("start_date", [$request->start_date, $request->end_date])->first();

                $EXIST_DISCOUNT_END_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("products", function ($query) use ($product_selected) {
                        $query->where("product_id", $product_selected["id"]);
                    })->whereBetween("end_date", [$request->start_date, $request->end_date])->first();

                if ($EXIST_DISCOUNT_START_DATE || $EXIST_DISCOUNT_END_DATE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "El producto " . $product_selected["title"] . " ya tiene un descuento en el rango de fechas seleccionado, que es " . ($EXIST_DISCOUNT_START_DATE ? '#' . $EXIST_DISCOUNT_START_DATE->code : '')
                            . ($EXIST_DISCOUNT_END_DATE ? '#' . $EXIST_DISCOUNT_END_DATE->code : '')
                    ]);
                }
            }
        }

        if ($request->discount_type == 2) { //Validacion a nivel de categorias
            foreach ($request->categorie_selected as $key => $categorie_selected) {
                $EXIST_DISCOUNT_START_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("categories", function ($query) use ($categorie_selected) {
                        $query->where("categorie_id", $categorie_selected["id"]);
                    })->whereBetween("start_date", [$request->start_date, $request->end_date])->first();

                $EXIST_DISCOUNT_END_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("categories", function ($query) use ($categorie_selected) {
                        $query->where("categorie_id", $categorie_selected["id"]);
                    })->whereBetween("end_date", [$request->start_date, $request->end_date])->first();

                if ($EXIST_DISCOUNT_START_DATE || $EXIST_DISCOUNT_END_DATE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La categoria " . $categorie_selected["name"] . " ya tiene un descuento en el rango de fechas seleccionado, que es " . ($EXIST_DISCOUNT_START_DATE ? '#' . $EXIST_DISCOUNT_START_DATE->code : '')
                            . ($EXIST_DISCOUNT_END_DATE ? '#' . $EXIST_DISCOUNT_END_DATE->code : '')
                    ]);
                }
            }
        }

        if ($request->discount_type == 3) { //Validacion a nivel de marcas
            foreach ($request->brand_selected as $key => $brand_selected) {
                $EXIST_DISCOUNT_START_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("brands", function ($query) use ($brand_selected) {
                        $query->where("brand_id", $brand_selected["id"]);
                    })->whereBetween("start_date", [$request->start_date, $request->end_date])->first();

                $EXIST_DISCOUNT_END_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("brands", function ($query) use ($brand_selected) {
                        $query->where("brand_id", $brand_selected["id"]);
                    })->whereBetween("end_date", [$request->start_date, $request->end_date])->first();

                if ($EXIST_DISCOUNT_START_DATE || $EXIST_DISCOUNT_END_DATE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La marca " . $brand_selected["name"] . " ya tiene un descuento en el rango de fechas seleccionado, que es " . ($EXIST_DISCOUNT_START_DATE ? '#' . $EXIST_DISCOUNT_START_DATE->code : '')
                            . ($EXIST_DISCOUNT_END_DATE ? '#' . $EXIST_DISCOUNT_END_DATE->code : '')
                    ]);
                }
            }
        }

        $request->request->add(["code" => uniqid()]);
        $discount = Discount::create($request->all());

        foreach ($request->product_selected as $key => $product_selec) {
            DiscountProduct::create([
                "discount_id" => $discount->id,
                "product_id" => $product_selec["id"],
            ]);
        }

        foreach ($request->categorie_selected as $key => $categorie_selec) {
            DiscountCategorie::create([
                "discount_id" => $discount->id,
                "categorie_id" => $categorie_selec["id"],
            ]);
        }

        foreach ($request->brand_selected as $key => $brand_selec) {
            DiscountBrand::create([
                "discount_id" => $discount->id,
                "brand_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200,
            "id" => $discount->id,  // Include the ID in the response
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $discount = Discount::findOrFail($id);

        return response()->json([
            "discount" => DiscountResource::make($discount), 
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validar que los productos o categorias o marcas no se registren dentro del mismo periodo de tiempo
        if ($request->discount_type == 1) { //Validacion a nivel de productos
            foreach ($request->product_selected as $key => $product_selected) {
                $EXIST_DISCOUNT_START_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("id", "<>", $id)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("products", function ($query) use ($product_selected) {
                        $query->where("product_id", $product_selected["id"]);
                    })->whereBetween("start_date", [$request->start_date, $request->end_date])->first();

                $EXIST_DISCOUNT_END_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("id", "<>", $id)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("products", function ($query) use ($product_selected) {
                        $query->where("product_id", $product_selected["id"]);
                    })->whereBetween("end_date", [$request->start_date, $request->end_date])->first();

                if ($EXIST_DISCOUNT_START_DATE || $EXIST_DISCOUNT_END_DATE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "El producto " . $product_selected["title"] . " ya tiene un descuento en el rango de fechas seleccionado, que es " . ($EXIST_DISCOUNT_START_DATE ? '#' . $EXIST_DISCOUNT_START_DATE->code : '')
                            . ($EXIST_DISCOUNT_END_DATE ? '#' . $EXIST_DISCOUNT_END_DATE->code : '')
                    ]);
                }
            }
        }

        if ($request->discount_type == 2) { //Validacion a nivel de categorias
            foreach ($request->categorie_selected as $key => $categorie_selected) {
                $EXIST_DISCOUNT_START_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("id", "<>", $id)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("categories", function ($query) use ($categorie_selected) {
                        $query->where("categorie_id", $categorie_selected["id"]);
                    })->whereBetween("start_date", [$request->start_date, $request->end_date])->first();

                $EXIST_DISCOUNT_END_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("id", "<>", $id)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("categories", function ($query) use ($categorie_selected) {
                        $query->where("categorie_id", $categorie_selected["id"]);
                    })->whereBetween("end_date", [$request->start_date, $request->end_date])->first();

                if ($EXIST_DISCOUNT_START_DATE || $EXIST_DISCOUNT_END_DATE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La categoria " . $categorie_selected["name"] . " ya tiene un descuento en el rango de fechas seleccionado, que es " . ($EXIST_DISCOUNT_START_DATE ? '#' . $EXIST_DISCOUNT_START_DATE->code : '')
                            . ($EXIST_DISCOUNT_END_DATE ? '#' . $EXIST_DISCOUNT_END_DATE->code : '')
                    ]);
                }
            }
        }

        if ($request->discount_type == 3) { //Validacion a nivel de marcas
            foreach ($request->brand_selected as $key => $brand_selected) {
                $EXIST_DISCOUNT_START_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("id", "<>", $id)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("brands", function ($query) use ($brand_selected) {
                        $query->where("brand_id", $brand_selected["id"]);
                    })->whereBetween("start_date", [$request->start_date, $request->end_date])->first();

                $EXIST_DISCOUNT_END_DATE = Discount::where("type_campaing", $request->type_campaing)
                    ->where("id", "<>", $id)
                    ->where("discount_type", $request->discount_type)
                    ->whereHas("brands", function ($query) use ($brand_selected) {
                        $query->where("brand_id", $brand_selected["id"]);
                    })->whereBetween("end_date", [$request->start_date, $request->end_date])->first();

                if ($EXIST_DISCOUNT_START_DATE || $EXIST_DISCOUNT_END_DATE) {
                    return response()->json([
                        "message" => 403,
                        "message_text" => "La marca " . $brand_selected["name"] . " ya tiene un descuento en el rango de fechas seleccionado, que es " . ($EXIST_DISCOUNT_START_DATE ? '#' . $EXIST_DISCOUNT_START_DATE->code : '')
                            . ($EXIST_DISCOUNT_END_DATE ? '#' . $EXIST_DISCOUNT_END_DATE->code : '')
                    ]);
                }
            }
        }

        $discount = Discount::findOrFail($id);
        $discount->update($request->all());

        foreach ($discount->categories as $key => $categorie) {
            $categorie->delete();
        }

        foreach ($discount->products as $key => $product) {
            $product->delete();
        }

        foreach ($discount->brands as $key => $brand) {
            $brand->delete();
        }

        foreach ($request->product_selected as $key => $product_selec) {
            DiscountProduct::create([
                "discount_id" => $discount->id,
                "product_id" => $product_selec["id"],
            ]);
        }

        foreach ($request->categorie_selected as $key => $categorie_selec) {
            DiscountCategorie::create([
                "discount_id" => $discount->id,
                "categorie_id" => $categorie_selec["id"],
            ]);
        }

        foreach ($request->brand_selected as $key => $brand_selec) {
            DiscountBrand::create([
                "discount_id" => $discount->id,
                "brand_id" => $brand_selec["id"],
            ]);
        }

        return response()->json([
            "message" => 200,
            "id" => $discount->id,  // Include the ID in the response
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $discount = Discount::findOrFail($id);

        //Si peretenece a una venta no debe eliminarse

        return response()->json([
            "message" => 200,
        ]);
    }
}
