<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_proveedor',
        'procedencia',
        'nit',
        'telefono',
        'contacto',
        'celular_contacto',
        'nombre_cheque',
        'nombre_lcv',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
}
