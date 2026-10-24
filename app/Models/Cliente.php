<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = ['codigo', 'nombre_completo', 'ci_nit', 'telefono', 'domicilio', 'tipo_cliente', 'estado'];
    protected $casts = ['estado' => 'boolean'];
}
