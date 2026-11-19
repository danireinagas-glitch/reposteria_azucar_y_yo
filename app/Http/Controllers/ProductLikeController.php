<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductLikeController extends Controller
{
    public function update(Request $request, Product $producto): JsonResponse
    {
        abort_unless($producto->estado, 404);

        $data = $request->validate([
            'liked' => ['required', 'boolean'],
        ]);
        $visitorHash = hash('sha256', $request->session()->getId());

        if ($data['liked']) {
            DB::table('product_likes')->insertOrIgnore([
                'product_id' => $producto->id,
                'visitor_hash' => $visitorHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('product_likes')
                ->where('product_id', $producto->id)
                ->where('visitor_hash', $visitorHash)
                ->delete();
        }

        return response()->json(['liked' => (bool) $data['liked']]);
    }
}
