<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;

class InvoiceGenerated extends Mailable
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
        $html = "<h1>Comprobante Electrónico</h1>";
        $html .= "<p>ID comprobante: {$inv->id}</p>";
        $html .= "<p>Tipo: {$inv->type}</p>";
        $html .= "<p>Cliente: {$inv->customer_name} ({$inv->customer_document_type} - {$inv->customer_document_number})</p>";
        $html .= "<p>Subtotal: {$inv->subtotal} | IGV: {$inv->igv} | Total: {$inv->total}</p>";
        $mail = $this->subject('Su comprobante electrónico')
            ->html($html);

        if ($inv->pdf_path && file_exists(storage_path('app/' . $inv->pdf_path))) {
            $mail->attach(storage_path('app/' . $inv->pdf_path));
        }
        if ($inv->xml_path && file_exists(storage_path('app/' . $inv->xml_path))) {
            $mail->attach(storage_path('app/' . $inv->xml_path));
        }

        return $mail;
    }
}
