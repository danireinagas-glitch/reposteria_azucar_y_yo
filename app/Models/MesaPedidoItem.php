<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MesaPedidoItem extends Model
{
    protected $table = 'mesa_pedido_items';

    protected $fillable = [
        'mesa_pedido_id',
        'product_id',
        'nombre_producto',
        'precio',
        'cantidad',
        'observacion',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'cantidad' => 'integer',
    ];

    public function pedido()
    {
        return $this->belongsTo(MesaPedido::class, 'mesa_pedido_id');
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getSubtotalAttribute(): float
    {
        return round((float) $this->precio * $this->cantidad, 2);
    }
}
