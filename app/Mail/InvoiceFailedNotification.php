<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;

class InvoiceFailedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $inv = $this->invoice;
        $html = "<h1>Fallo en generación de comprobante</h1>";
        $html .= "<p>Invoice ID: {$inv->id}</p>";
        $html .= "<p>Order ID: {$inv->order_id}</p>";
        $html .= "<p>Intentos: {$inv->attempts}</p>";
        $html .= "<p>Error: {$inv->error_message}</p>";

        return $this->subject('ALERTA: Fallo generación comprobante')
            ->html($html);
    }
}
