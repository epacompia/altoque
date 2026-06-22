<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function update(Request $request)
    {
        try {
            // Validación de los datos enviados
            $request->validate([
                'phone' => 'nullable|string|max:15',
                'dni' => 'nullable|regex:/^[0-9]{8}$/|max:8', // Asegurar solo números y longitud precisa
                'address' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
            ]);

            // Obtener al usuario autenticado
            $user = Auth::user();

            // Actualizar los datos proporcionados por el usuario manualmente
            $user->phone = $request->input('phone');
            $user->dni = $request->input('dni');
            $user->address = $request->input('address');
            $user->district = $request->input('district');
            $user->department = $request->input('department');

            // Guardar los cambios en el modelo de usuario
            $user->save();

            // Responder con los datos actualizados
            return response()->json([
                'message' => 'Datos actualizados correctamente',
                'user' => $user,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si hay errores de validación, devolver respuesta con detalles
            return response()->json([
                'error' => 'Error en validación',
                'details' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            $message = 'Error en la actualización';

            if (str_contains($e->getMessage(), 'users_phone_unique')) {
                $message = 'El número de teléfono ya está registrado por otro usuario';
            } elseif (str_contains($e->getMessage(), 'users_dni_unique')) {
                $message = 'El DNI ya está registrado por otro usuario';
            } elseif (str_contains($e->getMessage(), 'users_email_unique')) {
                $message = 'El correo electrónico ya está registrado por otro usuario';
            }

            return response()->json([
                'error' => $message,
            ], 409);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la actualización',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}