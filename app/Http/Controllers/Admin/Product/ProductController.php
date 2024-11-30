<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Brand;
use App\Models\Product\Categorie;
use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:10,1')->only(['update', 'store', 'destroy', 'delete_imagen']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $categorie_first_id = $request->categorie_first_id;
        $cateforie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;
        $brand_id = $request->brand_id;

        $products = Product::filterAdvanceProduct(
            $search,
            $categorie_first_id,
            $cateforie_second_id,
            $categorie_third_id,
            $brand_id
        )
            ->orderby("id")->paginate(10);

        return response()->json([
            "total" => $products->total(),
            "products" => ProductCollection::make($products),
        ]);
    }

    public function config()
    {
        $categories_first = Categorie::where("state", 1)->where("categorie_second_id", NULL)->where("categorie_third_id", NULL)->get();
        $categories_seconds = Categorie::where("state", 1)->where("categorie_second_id", "<>", NULL)->where("categorie_third_id", NULL)->get();
        $categories_thirds = Categorie::where("state", 1)->where("categorie_second_id", "<>", NULL)->where("categorie_third_id", "<>", NULL)->get();
        $brands = Brand::where("state", 1)->get();

        return response()->json([
            "categories_first" => $categories_first,
            "categories_seconds" => $categories_seconds,
            "categories_thirds" => $categories_thirds,
            "brands" => $brands,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isValid = Product::where("title", $request->title)->first();

        if ($isValid) {
            return response()->json([
                "message" => 403,
                "message_text" => "The product already exists",
            ]);
        }

        if ($request->hasFile("portada")) {
            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);

        $product = Product::create($request->all());

        return response()->json([
            "message" => 200,
            "id" => $product->id,  // Include the ID in the response
        ]);
    }

    public function imagens(Request $request)
    {
        $product_id = $request->product_id;

        if ($request->hasFile("imagen_add")) {
            $path = Storage::putFile("products", $request->file("imagen_add"));
        }

        $product_imagen = ProductImage::create(([
            "imagen" => $path,
            "product_id" => $product_id,
        ]));

        return response()->json([
            "imagen" => [
                "id" => $product_imagen->id,
                "imagen" => env("APP_URL") . "/storage/" . $product_imagen->imagen,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            "product" => ProductResource::make($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $isValid = Product::where("id", "<>", $id)
            ->where("title", $request->title)->first();

        if ($isValid) {
            return response()->json([
                "message" => 403,
                "message_text" => "The product already exists",
            ]);
        }

        $product = Product::findOrFail($id);

        if ($request->hasFile("portada")) {

            if ($product->imagen) {
                Storage::delete($product->imagen);
            }

            $path = Storage::putFile("products", $request->file("portada"));
            $request->request->add(["imagen" => $path]);
        }

        $request->request->add(["slug" => Str::slug($request->title)]);
        $request->request->add(["tags" => $request->multiselect]);

        $product->update($request->all());

        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Verificar y eliminar imágenes asociadas al producto
        foreach ($product->images as $image) {
            if ($image->imagen) {
                Storage::delete($image->imagen);
            }
            $image->delete();
        }

        // Verificar si el slider tiene una imagen y eliminarla si existe
        if ($product->imagen) {
            Storage::delete($product->imagen);
        }

        //No se puede eliminar un producto que tenga una venta
        $product->delete();

        return response()->json([
            "message" => 200,
        ]);
    }

    public function delete_imagen(string $id)
    {
        $product = ProductImage::findOrFail($id);

        // Verificar si el slider tiene una imagen y eliminarla si existe
        if ($product->imagen) {
            Storage::delete($product->imagen);
        }

        $product->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
