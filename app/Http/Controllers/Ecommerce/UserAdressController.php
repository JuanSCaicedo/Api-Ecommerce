<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
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
        $addres = UserAddres::created($request->all());

        return response()->json([
            "message" => 201,
            "address" => $addres
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
        $addres = UserAddres::findOrFail($id);
        $addres->update($request->all());

        return response()->json([
            "message" => 200,
            "address" => $addres
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $addres = UserAddres::findOrFail($id);
        $addres->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
