<?php

namespace App\Http\Controllers;

use App\Models\FoodStall;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StallController extends Controller
{
    // MÉTODO 1: Obtener puesto del vendedor autenticado
    public function obtenerMiPuesto(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            // Obtener puesto del vendedor
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            
            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado',
                    'mensaje' => 'Primero debes crear un puesto'
                ], 404);
            }
            
            return response()->json([
                'puesto' => [
                    'id' => $puesto->id,
                    'nombre' => $puesto->name,
                    'slug' => $puesto->slug,
                    'direccion' => $puesto->address,
                    'telefono' => $puesto->phone,
                    'horario_apertura' => $puesto->opening_time,
                    'horario_cierre' => $puesto->closing_time,
                    'latitud' => $puesto->latitude,
                    'longitud' => $puesto->longitude,
                    'descripcion' => $puesto->description,
                    'qr_path' => $puesto->qr_path,
                    'activo' => $puesto->active,
                    'creado_en' => $puesto->created_at
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener puesto: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener puesto',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    // MÉTODO 2: Generar/Obtener QR mejorado
    public function generarQr(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            // Obtener puesto del vendedor
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            
            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }
            
            // Generar URL del menú con slug para mejor UX
            $menuUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://127.0.0.1:3000')) 
                     . '/menu/' . $puesto->slug;
            
            // Generar QR usando SVG (no requiere imagick)
            $filename = 'qr_codes/stall_' . $puesto->id . '_' . time() . '.svg';
            $svg = QrCode::format('svg')
                         ->size(400)
                         ->margin(1)
                         ->generate($menuUrl);
            
            Storage::disk('public')->put($filename, $svg);
            
            // Actualizar ruta en BD
            $puesto->qr_path = $filename;
            $puesto->save();
            
            Log::info('QR generado', [
                'stall_id' => $puesto->id,
                'filename' => $filename,
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'QR generado exitosamente',
                'qr_url' => Storage::disk('public')->url($filename),
                'codigo_puesto' => 'STALL-' . $puesto->id,
                'menu_url' => $menuUrl,
                'generado_en' => now()
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al generar QR: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al generar QR',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'seller_id' => 'nullable|integer',
        ]);

        $stall = FoodStall::create([
            'name' => $data['name'],
            'seller_id' => $data['seller_id'] ?? null,
            'slug' => Str::slug($data['name']) . '-' . Str::random(4),
        ]);

        return response()->json(['stall' => $stall], 201);
    }

    // Generar/obtener QR (método antiguo - mantener para compatibilidad)
    public function qr($stallId)
    {
        $stall = FoodStall::findOrFail($stallId);

        // URL de app/front (deep link) con stallId
        $menuUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://127.0.0.1:3000')) . '/stall/' . $stall->id;

        $filename = 'qr_codes/stall_' . $stall->id . '.png';
        $png = QrCode::format('png')->size(400)->generate($menuUrl);
        Storage::disk('public')->put($filename, $png);

        $stall->qr_path = $filename;
        $stall->save();

        return response()->json([
            'qr_url' => Storage::disk('public')->url($filename),
            'menu_url' => $menuUrl,
        ]);
    }
}