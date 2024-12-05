<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:10,1')->only(['update', 'store', 'destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $sliders = Slider::where('title', 'LIKE', "%" . $search . "%")
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json([
            "total" => $sliders->total(),
            "sliders" => $sliders->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => env("APP_URL") . "storage/" . $slider->imagen,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "type_slider" => $slider->type_slider,
                    "type_width" => $slider->type_width,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                    "color" => $slider->color,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile("image")) {
            $path = Storage::putFile("sliders", $request->file("image"));
            $request->request->add(["imagen" =>  $path]);
        }
        $slider = Slider::create($request->all());
        return response()->json([
            "message" => 200,
            "id" => $slider->id,  // Include the ID in the response
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $slider = Slider::findOrFail($id);

        return response()->json(["slider" => [
            "id" => $slider->id,
            "title" => $slider->title,
            "subtitle" => $slider->subtitle,
            "label" => $slider->label,
            "imagen" => env("APP_URL") . "storage/" . $slider->imagen,
            "link" => $slider->link,
            "state" => $slider->state,
            "type_slider" => $slider->type_slider,
            "type_width" => $slider->type_width,
            "price_original" => $slider->price_original,
            "price_campaing" => $slider->price_campaing,
            "color" => $slider->color,
        ]]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $slider = Slider::findOrFail($id);
        if ($request->has('image')) {
            if ($request->image == '') {
                if ($slider->imagen) {
                    Storage::delete($slider->imagen);
                    $slider->imagen = null;
                    $slider->save();
                }
            } else if ($request->hasFile('image')) {
                if ($slider->imagen) {
                    Storage::delete($slider->imagen);
                }
                $path = Storage::putFile("sliders", $request->file('image'));
                $request->request->add(["imagen" => $path]);
            }
        }
        $slider->update($request->all());
        return response()->json(["message" => 200]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $slider = Slider::findOrFail($id);

        // Verificar si el slider tiene una imagen y eliminarla si existe
        if ($slider->imagen) {
            Storage::delete($slider->imagen);
        }

        $slider->delete();

        return response()->json(["message" => 200]);
    }
}
