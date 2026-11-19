<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = ['nombre', 'qr_token', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function pedidos()
    {
        return $this->hasMany(MesaPedido::class, 'mesa_id');
    }

    public function pedidoAbierto()
    {
        return $this->pedidos()->whereIn('estado', [
            MesaPedido::ESTADO_PENDIENTE_MESERO,
            MesaPedido::ESTADO_EN_CAJA,
        ]);
    }
}
