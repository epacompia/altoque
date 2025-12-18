<?php

namespace App\Http\Controllers;

use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SMSController extends Controller
{
    public function sendOTP(Request $request)
    {
        $otp = rand(100000, 999999); // Generar OTP

        // Usar Twilio para enviar el mensaje
        $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

        $client->messages->create($request->phone, [
            'from' => env('TWILIO_PHONE_NUMBER'),
            'body' => "Tu código de verificación es: $otp",
        ]);

        // Guardar OTP en la base de datos o en cache temporal
        $request->session()->put('otp', $otp);

        return response()->json(['message' => 'OTP enviado'], 200);
    }

    public function verifyOTP(Request $request)
    {
        $otp = $request->session()->get('otp');

        if ($otp && $otp == $request->otp) {
            // Autenticar al usuario o registrarlo si no existe
            $user = User::firstOrCreate(['phone' => $request->phone], [
                'name' => $request->name,
                'role' => 'customer'
            ]);

            Auth::login($user);

            return response()->json(['message' => 'Autenticado'], 200);
        }

        return response()->json(['message' => 'OTP inválido'], 401);
    }

}
