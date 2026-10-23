<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'grupo_id', 'marca_id',
        'unidad_medida_id', 'procedencia', 'ubicacion', 'caso_uso',
        'sucursal', 'orden_menu', 'precio_compra', 'precio_venta',
        'stock_actual', 'stock_minimo', 'estado', 'observaciones', 'imagen',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'orden_menu' => 'integer',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoProducto::class, 'grupo_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }
}
