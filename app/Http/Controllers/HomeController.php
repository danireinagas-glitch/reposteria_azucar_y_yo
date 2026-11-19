<?php

namespace App\Http\Controllers;

use App\Models\Product; // Importa tu modelo de productos
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Show the main public website.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Trae los productos destacados para que funcionen en tu index.blade.php
        $destacados = Product::where('destacado', 1)
            ->where('estado', true)
            ->get();
        $likedProductIds = DB::table('product_likes')
            ->where('visitor_hash', hash('sha256', request()->session()->getId()))
            ->pluck('product_id')
            ->all();

        return view('index', compact('destacados', 'likedProductIds'));
    }
}