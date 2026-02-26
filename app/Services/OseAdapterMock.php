<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Mock adapter for an OSE (facturación electrónica) provider.
 * In production replace with real integration (PSE/OSE provider SDK/http client).
 */
class OseAdapterMock
{
    public function sendInvoice(array $payload): array
    {
        // Simulate OSE response: generate 'xml' content, 'pdf' content, ose_ticket and hash
        $timestamp = now()->format('YmdHis');
        $oseTicket = 'OSE-' . $timestamp . '-' . rand(1000, 9999);
        $hash = sha1(json_encode($payload) . $oseTicket);

        // Generate XML and PDF as strings (in real life, provider returns them)
        $xml = "<Invoice><id>{$payload['order_id']}</id><total>{$payload['total']}</total><hash>{$hash}</hash></Invoice>";
        $pdf = "PDF_PLACEHOLDER for order {$payload['order_id']} - total: {$payload['total']}";

        return [
            'success' => true,
            'ose_ticket' => $oseTicket,
            'ose_hash' => $hash,
            'xml' => $xml,
            'pdf' => $pdf,
        ];
    }
}
