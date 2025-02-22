<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\HomeView;
use App\Models\Sale\UserAddres;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdressController extends Controller
{
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

        $address = UserAddres::where("user_id", $user->id)->orderBy("id", "desc")->get();

        return response()->json([
            "message" => 200,
            "address" => $address
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

        $request->request->add(["user_id" => auth('api')->user()->id]);

        $addres = UserAddres::create($request->all());

        return response()->json([
            "message" => 201,
            "addres" => $addres
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
        // Verifica si la vista de mantenimiento está activada
        if (HomeView::where("name", "vista_mantenimiento")->where("state", 1)->exists()) {
            return response()->json(["message" => "El sitio está en mantenimiento"], 503);
        }

        $addres = UserAddres::findOrFail($id);
        $addres->update($request->all());

        return response()->json([
            "message" => 200,
            "addres" => $addres
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
        
        $addres = UserAddres::findOrFail($id);
        $addres->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
