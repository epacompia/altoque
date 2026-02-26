<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\FoodStall;

class VendedorController extends Controller
{
    /**
     * Convertir un cliente a vendedor
     * POST /api/convertirse-vendedor
     */
    public function convertirseAVendedor(Request $request)
    {
        try {
            // Obtener usuario autenticado
            $usuario = Auth::user();

            // Validar que sea cliente
            if ($usuario->role !== 'client') {
                return response()->json([
                    'error' => 'Solo los clientes pueden convertirse en vendedores',
                    'rol_actual' => $usuario->role
                ], 409);
            }

            // Validar que no tenga ya un puesto
            if ($usuario->foodStall) {
                return response()->json([
                    'error' => 'Ya eres vendedor. No puedes crear otro puesto.',
                    'puesto_existente' => $usuario->foodStall->name
                ], 409);
            }

            // Validar datos requeridos
            $request->validate([
                'nombre_puesto' => 'required|string|max:100|unique:food_stalls,name',
                'direccion' => 'required|string|max:255',
                'telefono' => 'required|string|max:20',
                'horario_apertura' => 'required|date_format:H:i',
                'horario_cierre' => 'required|date_format:H:i|after:horario_apertura',
                'latitud' => 'nullable|numeric|between:-90,90',
                'longitud' => 'nullable|numeric|between:-180,180',
                'descripcion' => 'nullable|string|max:500'
            ], [
                'nombre_puesto.required' => 'El nombre del puesto es obligatorio',
                'nombre_puesto.unique' => 'Ya existe un puesto con ese nombre',
                'direccion.required' => 'La dirección es obligatoria',
                'telefono.required' => 'El teléfono es obligatorio',
                'horario_apertura.required' => 'El horario de apertura es obligatorio',
                'horario_cierre.required' => 'El horario de cierre es obligatorio',
                'horario_cierre.after' => 'El horario de cierre debe ser posterior al de apertura'
            ]);

            // Generar slug automático desde el nombre del puesto
            $slug = Str::slug($request->nombre_puesto);
            
            // Asegurar que el slug sea único
            $slugBase = $slug;
            $contador = 1;
            while (FoodStall::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $contador;
                $contador++;
            }

            // Crear el puesto de comida
            $puesto = FoodStall::create([
                'name' => $request->nombre_puesto,
                'seller_id' => $usuario->id,
                'slug' => $slug,
                'address' => $request->direccion,
                'phone' => $request->telefono,
                'opening_time' => $request->horario_apertura,
                'closing_time' => $request->horario_cierre,
                'latitude' => $request->input('latitud'),
                'longitude' => $request->input('longitud'),
                'description' => $request->input('descripcion'),
                'active' => true,
                'qr_path' => null // Se generará en RF6
            ]);

            // Cambiar rol del usuario a vendedor
            $usuario->update(['role' => 'vendor']);

            // Refrescar el modelo para obtener datos actualizados
            $usuario->refresh();

            // Log de auditoría
            Log::info("Usuario convertido a vendedor", [
                'usuario_id' => $usuario->id,
                'usuario_email' => $usuario->email,
                'puesto_id' => $puesto->id,
                'puesto_nombre' => $puesto->name,
                'timestamp' => now()
            ]);

            return response()->json([
                'message' => '¡Felicitaciones! Ahora eres vendedor',
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->name,
                    'email' => $usuario->email,
                    'rol' => $usuario->role,
                    'nombre_puesto' => $usuario->first_name,
                    'apellido' => $usuario->last_name
                ],
                'puesto' => [
                    'id' => $puesto->id,
                    'nombre' => $puesto->name,
                    'slug' => $puesto->slug,
                    'seller_id' => $puesto->seller_id,
                    'direccion' => $puesto->address,
                    'telefono' => $puesto->phone,
                    'horario_apertura' => $puesto->opening_time,
                    'horario_cierre' => $puesto->closing_time,
                    'latitud' => $puesto->latitude,
                    'longitud' => $puesto->longitude,
                    'descripcion' => $puesto->description,
                    'activo' => $puesto->active,
                    'qr_path' => $puesto->qr_path,
                    'creado_en' => $puesto->created_at
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Retornar errores de validación
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Registrar error
            Log::error('Error al convertir usuario a vendedor: ' . $e->getMessage(), [
                'usuario_id' => Auth::id(),
                'excepcion' => $e
            ]);

            return response()->json([
                'error' => 'Error al convertir a vendedor',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }
}
