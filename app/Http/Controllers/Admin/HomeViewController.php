<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeView;
use Illuminate\Http\Request;

class HomeViewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $home_views = HomeView::where("name", "like", "%" . $search . "%")->paginate(20);

        return response()->json([
            "total" => $home_views->total(),
            "home_views" => $home_views->map(function ($home_view) {
                return [
                    "id" => $home_view->id,
                    "name" => $home_view->name,
                    "state" => $home_view->state,
                    "updated_at" => $home_view->updated_at->format("Y-m-d h:i:s"),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $isValida = HomeView::where("id", "<>", $id)->where("name", $request->name)->first();
        if ($isValida) {
            return response()->json(["message" => 403]);
        }
        $homeView = HomeView::findOrFail($id);
        $homeView->update($request->all());
        return response()->json([
            "message" => 200,
            "homeView" => [
                "id" => $homeView->id,
                "name" => $homeView->name,
                "state" => $homeView->state,
                "created_at" => $homeView->created_at->format("Y-m-d h:i:s"),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
