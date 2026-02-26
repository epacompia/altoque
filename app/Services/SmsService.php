<?php

namespace App\Services;

use Dotenv\Dotenv;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log; // Importar log para usar \Log

class SmsService
{
    protected $client;

    public function __construct()
    {
        // Cargar manualmente el archivo .env desde la raíz del proyecto
        $dotenv = Dotenv::createImmutable(base_path());
        $dotenv->load();

        // Depuración de las variables .env
        Log::info('TWILIO_ACCOUNT_SID: ' . env('TWILIO_ACCOUNT_SID'));
        Log::info('TWILIO_AUTH_TOKEN: ' . env('TWILIO_AUTH_TOKEN'));
        Log::info('TWILIO_PHONE_NUMBER: ' . env('TWILIO_PHONE_NUMBER'));

        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $phone = env('TWILIO_PHONE_NUMBER');

        if (!$sid || !$token || !$phone) {
            throw new \Exception(
                'Error: Alguna credencial de Twilio no está configurada correctamente. ' .
                'SID: ' . ($sid ?? 'NULL') .
                ', Token: ' . ($token ?? 'NULL') .
                ', Phone: ' . ($phone ?? 'NULL')
            );
        }

        $this->client = new Client($sid, $token);
    }
    
    public function sendSms($to, $message)
    {
        try {
            // Enviar el mensaje SMS
            $response = $this->client->messages->create($to, [
                'from' => env('TWILIO_PHONE_NUMBER'), // Número Twilio adquirido
                'body' => $message,
            ]);

            // Registrar la respuesta de Twilio
            Log::info('Twilio Response:', (array)$response);

            return $response;
        } catch (\Exception $e) {
            // Loguear detalles del error
            Log::error('Twilio Error:', ['error' => $e->getMessage()]);
            
            // Lanzar una excepción con detalles del error desde Twilio
            throw new \Exception('Error al enviar SMS: ' . $e->getMessage());
        }
    }
}