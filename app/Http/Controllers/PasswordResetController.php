<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    // Enviar OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Número de celular no está registrado'], 404);
        }

        // Generar OTP
        $otp = rand(100000, 999999);

        // Guardar OTP en la tabla `password_resets`
        PasswordReset::updateOrCreate(
            ['phone' => $user->phone],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        // Enviar SMS usando Twilio
        try {
            $smsService = new \App\Services\SmsService();
            $smsService->sendSms($user->phone, "Tu código OTP es: $otp");
            return response()->json(['message' => 'OTP enviado correctamente'], 200);
        } catch (\Exception $e) {
            // Devolver el error detallado en la respuesta
            return response()->json([
                'message' => 'Error al enviar el SMS.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Verificar OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        $reset = PasswordReset::where('phone', $request->phone)
                              ->where('otp', $request->otp)
                              ->where('expires_at', '>', Carbon::now())
                              ->first();

        if (!$reset) {
            return response()->json(['message' => 'Código OTP inválido o ha expirado'], 400);
        }

        return response()->json(['message' => 'OTP verificado. Proceda a cambiar la contraseña'], 200);
    }

    // Restablecer Contraseña
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $reset = PasswordReset::where('phone', $request->phone)
                              ->where('otp', $request->otp)
                              ->where('expires_at', '>', Carbon::now())
                              ->first();

        if (!$reset) {
            return response()->json(['message' => 'Código OTP inválido o ha expirado'], 400);
        }

        // Actualizar contraseña del usuario
        $user = User::where('phone', $request->phone)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Eliminar el registro OTP
        $reset->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente'], 200);
    }
}