<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MesaPedido extends Model
{
    public const ESTADO_PENDIENTE_MESERO = 'pendiente_mesero';

    public const ESTADO_EN_CAJA = 'en_caja';

    public const ESTADO_PAGADO = 'pagado';

    public const ESTADO_CANCELADO = 'cancelado';

    protected $table = 'mesa_pedidos';

    protected $fillable = [
        'mesa_id',
        'requester_hash',
        'estado',
        'mesero_id',
        'cajero_id',
        'venta_id',
        'total',
        'observaciones',
        'enviado_a_caja_en',
        'pagado_en',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'enviado_a_caja_en' => 'datetime',
        'pagado_en' => 'datetime',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    public function items()
    {
        return $this->hasMany(MesaPedidoItem::class, 'mesa_pedido_id');
    }

    public function mesero()
    {
        return $this->belongsTo(User::class, 'mesero_id');
    }

    public function cajero()
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function venta()
    {
        return $this->belongsTo(Order::class, 'venta_id');
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE_MESERO => 'Llama al mesero',
            self::ESTADO_EN_CAJA => 'Pendiente de cobro',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_CANCELADO => 'Cancelado',
            default => ucfirst(str_replace('_', ' ', $this->estado)),
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE_MESERO => 'success',
            self::ESTADO_EN_CAJA => 'warning',
            self::ESTADO_PAGADO => 'primary',
            self::ESTADO_CANCELADO => 'secondary',
            default => 'light',
        };
    }
}
