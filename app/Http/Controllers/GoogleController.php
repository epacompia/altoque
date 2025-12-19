<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // Verifica si se proporciona el parámetro `code`
            if (!$request->has('code')) {
                return response()->json(['error' => 'Falta el código de autorización'], 400);
            }

            // Recuperar datos del usuario desde Google usando el código enviado
            $googleUser = Socialite::driver('google')->user();

            // Procesar el usuario en la base de datos
            $user = User::where('email', $googleUser->email)->first();

            // Dividir el nombre completo en `first_name` y `last_name`
            $nameParts = explode(' ', $googleUser->name);
            $firstName = $nameParts[0]; // Primer nombre
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : null; // Apellidos

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name, // Nombre completo por si se requiere
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleUser->email,
                    'profile_picture' => $googleUser->avatar,
                    'role' => 'client', // Cliente por defecto
                    'password' => null, // Debido a OAuth, la contraseña puede ser nula
                ]);
            } else {
                // Actualizar los valores en caso de que el usuario ya exista
                $user->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'profile_picture' => $googleUser->avatar,
                ]);
            }

            // Generar un token de acceso para el usuario autenticado
            $token = $user->createToken('GoogleLogin')->plainTextToken;

            return response()->json([
                'message' => 'Autenticación exitosa',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            // Registrar el error y retornar detalles para depuración
            Log::error('Error en Google OAuth Callback: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error de autenticación',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}