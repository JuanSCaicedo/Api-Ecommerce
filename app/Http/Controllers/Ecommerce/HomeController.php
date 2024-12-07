<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ecommerce\Product\ProductEcommerceCollection;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use App\Models\Slider;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        //Lógica de la página de inicio
    }

    public function menu(Request $request)
    {
        $categories_menus = Categorie::where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->orderBy("position", "desc")->get();

        return response()->json([
            "categories_menus" => $categories_menus->map(function ($departament) {
                return [
                    "id" => $departament->id,
                    "name" => $departament->name,
                    "icon" => $departament->icon,
                    "categories" => $departament->categorie_seconds->map(function ($categorie) {
                        return [
                            "id" => $categorie->id,
                            "name" => $categorie->name,
                            "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : null,
                            "subcategories" => $categorie->categorie_seconds->map(function ($subcategorie) {
                                return [
                                    "id" => $subcategorie->id,
                                    "name" => $subcategorie->name,
                                    "imagen" => $subcategorie->imagen ? env("APP_URL") . "storage/" . $subcategorie->imagen : null,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function slider(Request $request)
    {
        $sliders_principal = Slider::where("state", 1)->where("type_slider", 1)->orderBy("id", "desc")->get();

        return response()->json([
            "sliders_principal" => $sliders_principal->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
        ]);
    }

    public function slider_secundario()
    {
        $sliders_secundario = Slider::where("state", 1)->where("type_slider", 2)->orderBy("id", "asc")->get();

        return response()->json([
            "sliders_secundario" => $sliders_secundario->map(function ($slider) {
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "imagen" => $slider->imagen ? env("APP_URL") . "storage/" . $slider->imagen : null,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "color" => $slider->color,
                    "type_slider" => $slider->type_slider,
                    "type_width" => $slider->type_width,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                ];
            }),
        ]);
    }

    public function categories(Request $request)
    {
        $categories_randoms = Categorie::withCount(["product_categorie_firsts"])->where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->inRandomOrder()->limit(5)->get();

        return response()->json([

            "categories_randoms" => $categories_randoms->map(function ($categorie) {
                return [
                    "id" => $categorie->id,
                    "name" => $categorie->name,
                    "product_categorie_firsts_count" => $categorie->product_categorie_firsts_count,
                    "imagen" => $categorie->imagen ? env("APP_URL") . "storage/" . $categorie->imagen : null,
                ];
            }),
        ]);
    }

    public function products(Request $request)
    {
        $product_trending_new = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
        $product_trending_featured = Product::where("state", 2)->inRandomOrder()->limit(8)->get();
        $product_trending_top_sellers = Product::where("state", 2)->inRandomOrder()->limit(8)->get();

        return response()->json([

            "product_trending_new" => ProductEcommerceCollection::make($product_trending_new),

            "product_trending_featured" => ProductEcommerceCollection::make($product_trending_featured),

            "product_trending_top_sellers" => ProductEcommerceCollection::make($product_trending_top_sellers),
        ]);
    }

    public function products_electronics(Request $request)
    {
        $product_electronics = Product::where("state",2)->where("categorie_first_id",1)->inRandomOrder()->limit(6)->get();
        return response()->json([

            "product_electronics" => ProductEcommerceCollection::make($product_electronics),
        ]);
    }
}
