<?php

namespace App\Http\Controllers;

use App\Http\Requests\Producto\StoreProductoImagenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductoImagenController extends Controller
{
    public function store(StoreProductoImagenRequest $request): JsonResponse
    {
        $file = $request->file('imagen');
        $directory = public_path('media/productos');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid()->toString().'.jpg';
        $file->move($directory, $filename);

        return response()->json([
            'url' => '/media/productos/'.$filename,
        ]);
    }
}
