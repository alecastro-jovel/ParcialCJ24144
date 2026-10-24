<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de la tabla "Solicitud_de_constancias" del diagrama físico.
 */
class Constancia extends Model
{
    protected $table = 'solicitud_de_constancias';
    protected $primaryKey = 'id_constancias';
    public $timestamps = false; // la tabla usa fecha/hora propias, no created_at/updated_at

    protected $fillable = [
        'nombre_empleado',
        'correo',
        'movil',
        'tipo_de_constancia',
        'descripcion',
        'cantidad',
        'fecha_de_solicitud',
        'hora_de_solicitud',
        'estado_solicitud',
    ];

    protected function casts(): array
    {
        return [
            'fecha_de_solicitud' => 'date:Y-m-d',
            'estado_solicitud'   => 'boolean', // true = Activa (solicitada) | false = Inactiva (lista)
            'cantidad'           => 'integer',
        ];
    }
}
