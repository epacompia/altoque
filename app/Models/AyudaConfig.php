<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AyudaConfig extends Model
{
    use HasFactory;

    protected $table = 'ayuda_config';

    protected $fillable = [
        'clave',
        'valor',
    ];

    public static function obtenerContacto()
    {
        $datos = self::all()->pluck('valor', 'clave');

        return [
            'telefono' => $datos->get('telefono', ''),
            'correo' => $datos->get('correo', ''),
            'whatsapp' => $datos->get('whatsapp', ''),
            'horario' => $datos->get('horario', ''),
        ];
    }
}