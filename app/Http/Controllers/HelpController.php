<?php

namespace App\Http\Controllers;

use App\Models\AyudaConfig;
use App\Models\HelpCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'ayuda_contenido';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $data = Cache::remember($cacheKey, 3600, function () {
            $categorias = HelpCategory::with('articulos')
                ->where('activo', true)
                ->orderBy('orden')
                ->get()
                ->map(function ($categoria) {
                    return [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'icono' => $categoria->icono,
                        'articulos' => $categoria->articulos->map(function ($a) {
                            return [
                                'id' => $a->id,
                                'pregunta' => $a->pregunta,
                                'respuesta' => $a->respuesta,
                            ];
                        }),
                    ];
                });

            return [
                'categorias' => $categorias,
                'contacto' => AyudaConfig::obtenerContacto(),
            ];
        });

        return response()->json($data);
    }
}