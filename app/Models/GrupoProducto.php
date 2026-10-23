<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoProducto extends Model
{
    protected $table = 'grupo_productos';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
