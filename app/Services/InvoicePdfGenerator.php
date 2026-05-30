<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use FPDF;

/**
 * Genera comprobantes electrónicos en PDF con formato similar
 * a los emitidos por proveedores OSE autorizados por SUNAT.
 */
class InvoicePdfGenerator
{
    private FPDF $pdf;
    private Invoice $invoice;
    private Order $order;

    // Datos del emisor (empresa)
    private string $rucEmisor = '20612345678';
    private string $razonSocial = 'ALTOQUE DELIVERY S.A.C.';
    private string $nombreComercial = 'AlToque';
    private string $direccionEmisor = 'Av. Javier Prado Este 1234, San Isidro, Lima';
    private string $ubigeo = '150131';
    private string $telefono = '(01) 234-5678';
    private string $email = 'facturacion@altoque.pe';

    public function generate(Invoice $invoice): string
    {
        $this->invoice = $invoice;
        $this->order = $invoice->order()->with(['items.product', 'stall', 'client'])->first();

        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetAutoPageBreak(true, 20);
        $this->pdf->AddPage();

        $this->drawHeader();
        $this->drawCompanyInfo();
        $this->drawDocumentBox();
        $this->drawCustomerInfo();
        $this->drawItemsTable();
        $this->drawTotals();
        $this->drawQrAndHash();
        $this->drawFooter();

        return $this->pdf->Output('S'); // Return as string
    }

    private function drawHeader(): void
    {
        // Logo area (simulated with text)
        $this->pdf->SetFillColor(220, 53, 69); // Rojo AlToque
        $this->pdf->Rect(10, 10, 60, 25, 'F');
        $this->pdf->SetFont('Helvetica', 'B', 22);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetXY(12, 14);
        $this->pdf->Cell(56, 10, 'AlToque', 0, 0, 'C');
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->SetXY(12, 24);
        $this->pdf->Cell(56, 6, 'Delivery de comida', 0, 0, 'C');

        $this->pdf->SetTextColor(0, 0, 0);
    }

    private function drawCompanyInfo(): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 9);
        $this->pdf->SetXY(10, 38);
        $this->pdf->Cell(95, 5, $this->utf8($this->razonSocial), 0, 1);

        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->SetX(10);
        $this->pdf->Cell(95, 4, $this->utf8('Nombre Comercial: ' . $this->nombreComercial), 0, 1);
        $this->pdf->SetX(10);
        $this->pdf->Cell(95, 4, $this->utf8('Domicilio Fiscal: ' . $this->direccionEmisor), 0, 1);
        $this->pdf->SetX(10);
        $this->pdf->Cell(95, 4, $this->utf8('Ubigeo: ' . $this->ubigeo . ' | Tel: ' . $this->telefono), 0, 1);
        $this->pdf->SetX(10);
        $this->pdf->Cell(95, 4, 'Email: ' . $this->email, 0, 1);
    }

    private function drawDocumentBox(): void
    {
        $tipoDoc = $this->invoice->type === 'factura' ? 'FACTURA ELECTRONICA' : 'BOLETA DE VENTA ELECTRONICA';
        $serieNumero = $this->generateSerieNumero();

        // Recuadro del documento (lado derecho)
        $this->pdf->SetLineWidth(0.8);
        $this->pdf->SetDrawColor(220, 53, 69);
        $this->pdf->Rect(115, 10, 85, 35);

        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetTextColor(220, 53, 69);
        $this->pdf->SetXY(115, 13);
        $this->pdf->Cell(85, 5, 'R.U.C. ' . $this->rucEmisor, 0, 0, 'C');

        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->SetXY(115, 20);
        $this->pdf->Cell(85, 6, $this->utf8($tipoDoc), 0, 0, 'C');

        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->SetXY(115, 28);
        $this->pdf->Cell(85, 6, $serieNumero, 0, 0, 'C');

        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->SetXY(115, 36);
        $this->pdf->Cell(85, 4, 'REPRESENTACION IMPRESA', 0, 0, 'C');

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetDrawColor(0, 0, 0);
    }

    private function drawCustomerInfo(): void
    {
        $y = 62;

        // Recuadro datos del cliente
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->Rect(10, $y, 190, 28, 'DF');

        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->SetXY(12, $y + 2);

        $fechaEmision = $this->invoice->created_at ? $this->invoice->created_at->format('d/m/Y') : date('d/m/Y');
        $horaEmision = $this->invoice->created_at ? $this->invoice->created_at->format('H:i:s') : date('H:i:s');

        // Fila 1
        $this->pdf->Cell(35, 5, $this->utf8('Fecha de Emisión:'), 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(50, 5, $fechaEmision, 0, 0);
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(30, 5, $this->utf8('Hora:'), 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(40, 5, $horaEmision, 0, 1);

        // Fila 2
        $this->pdf->SetX(12);
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $docType = $this->invoice->customer_document_type ?: 'DNI';
        $this->pdf->Cell(35, 5, $this->utf8('Tipo Doc. Cliente:'), 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);
        $docTypeLabel = $docType === 'RUC' ? '6 - RUC' : '1 - DNI';
        $this->pdf->Cell(50, 5, $docTypeLabel, 0, 0);
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(30, 5, $this->utf8('Nro. Documento:'), 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(40, 5, $this->invoice->customer_document_number ?: 'S/N', 0, 1);

        // Fila 3
        $this->pdf->SetX(12);
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(35, 5, 'Cliente:', 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);
        $clientName = $this->invoice->customer_name ?: ($this->order->client->name ?? 'CLIENTE GENERAL');
        $this->pdf->Cell(120, 5, $this->utf8($clientName), 0, 0);

        // Fila 4
        $this->pdf->Ln();
        $this->pdf->SetX(12);
        $this->pdf->SetFont('Helvetica', 'B', 8);
        $this->pdf->Cell(35, 5, $this->utf8('Dirección:'), 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);
        $this->pdf->Cell(120, 5, $this->utf8($this->order->delivery_address ?? 'Lima, Peru'), 0, 0);
    }

    private function drawItemsTable(): void
    {
        $y = 95;

        // Encabezado de tabla
        $this->pdf->SetFillColor(220, 53, 69);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Helvetica', 'B', 8);

        $this->pdf->SetXY(10, $y);
        $this->pdf->Cell(15, 7, 'CANT.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 7, 'UM', 1, 0, 'C', true);
        $this->pdf->Cell(80, 7, $this->utf8('DESCRIPCIÓN'), 1, 0, 'C', true);
        $this->pdf->Cell(30, 7, 'P. UNIT.', 1, 0, 'C', true);
        $this->pdf->Cell(15, 7, 'IGV', 1, 0, 'C', true);
        $this->pdf->Cell(35, 7, 'IMPORTE', 1, 0, 'C', true);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Helvetica', '', 8);

        $y += 7;
        $items = $this->order->items;
        $fill = false;

        foreach ($items as $item) {
            $this->pdf->SetXY(10, $y);

            if ($fill) {
                $this->pdf->SetFillColor(250, 250, 250);
            } else {
                $this->pdf->SetFillColor(255, 255, 255);
            }

            $cantidad = $item->quantity;
            $precioUnit = number_format($item->price_per_unit, 2);
            $subtotalItem = number_format($item->subtotal, 2);
            $igvItem = number_format($item->subtotal * 0.18, 2);
            $productName = $item->product ? $item->product->name : 'Producto #' . $item->product_id;

            // Toppings/cremas
            $toppingsText = '';
            if ($item->toppings) {
                $toppings = is_string($item->toppings) ? json_decode($item->toppings, true) : $item->toppings;
                if (is_array($toppings) && count($toppings) > 0) {
                    $toppingsText = ' + Cremas';
                }
            }

            $this->pdf->Cell(15, 6, $cantidad, 'LR', 0, 'C', true);
            $this->pdf->Cell(15, 6, 'UND', 'LR', 0, 'C', true);
            $this->pdf->Cell(80, 6, $this->utf8($productName . $toppingsText), 'LR', 0, 'L', true);
            $this->pdf->Cell(30, 6, 'S/ ' . $precioUnit, 'LR', 0, 'R', true);
            $this->pdf->Cell(15, 6, '18%', 'LR', 0, 'C', true);
            $this->pdf->Cell(35, 6, 'S/ ' . $subtotalItem, 'LR', 0, 'R', true);

            $y += 6;
            $fill = !$fill;
        }

        // Si hay delivery
        if ($this->order->delivery_cost > 0) {
            $this->pdf->SetXY(10, $y);
            $this->pdf->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
            $this->pdf->Cell(15, 6, '1', 'LR', 0, 'C', true);
            $this->pdf->Cell(15, 6, 'SRV', 'LR', 0, 'C', true);
            $this->pdf->Cell(80, 6, 'Servicio de Delivery', 'LR', 0, 'L', true);
            $this->pdf->Cell(30, 6, 'S/ ' . number_format($this->order->delivery_cost, 2), 'LR', 0, 'R', true);
            $this->pdf->Cell(15, 6, '18%', 'LR', 0, 'C', true);
            $this->pdf->Cell(35, 6, 'S/ ' . number_format($this->order->delivery_cost, 2), 'LR', 0, 'R', true);
            $y += 6;
        }

        // Línea final de tabla
        $this->pdf->SetXY(10, $y);
        $this->pdf->Cell(190, 0, '', 'T');
    }

    private function drawTotals(): void
    {
        $y = $this->pdf->GetY() + 5;

        // Recuadro de totales (lado derecho)
        $this->pdf->SetFont('Helvetica', '', 8);

        $subtotal = $this->invoice->subtotal;
        $igv = $this->invoice->igv;
        $total = $this->invoice->total;
        $delivery = $this->order->delivery_cost ?? 0;
        $opGravada = $subtotal - $delivery;

        // Op. Gravada
        $this->pdf->SetXY(120, $y);
        $this->pdf->Cell(40, 5, 'OP. GRAVADA:', 0, 0, 'R');
        $this->pdf->Cell(40, 5, 'S/ ' . number_format($opGravada > 0 ? $opGravada : $subtotal, 2), 0, 1, 'R');

        // Op. Inafecta
        $this->pdf->SetXY(120, $y + 5);
        $this->pdf->Cell(40, 5, 'OP. INAFECTA:', 0, 0, 'R');
        $this->pdf->Cell(40, 5, 'S/ 0.00', 0, 1, 'R');

        // Op. Exonerada
        $this->pdf->SetXY(120, $y + 10);
        $this->pdf->Cell(40, 5, 'OP. EXONERADA:', 0, 0, 'R');
        $this->pdf->Cell(40, 5, 'S/ 0.00', 0, 1, 'R');

        if ($delivery > 0) {
            $this->pdf->SetXY(120, $y + 15);
            $this->pdf->Cell(40, 5, 'DELIVERY:', 0, 0, 'R');
            $this->pdf->Cell(40, 5, 'S/ ' . number_format($delivery, 2), 0, 1, 'R');
        }

        // IGV
        $igvY = $delivery > 0 ? $y + 20 : $y + 15;
        $this->pdf->SetXY(120, $igvY);
        $this->pdf->Cell(40, 5, 'IGV (18%):', 0, 0, 'R');
        $this->pdf->Cell(40, 5, 'S/ ' . number_format($igv, 2), 0, 1, 'R');

        // Total
        $totalY = $igvY + 6;
        $this->pdf->SetFont('Helvetica', 'B', 10);
        $this->pdf->SetFillColor(220, 53, 69);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetXY(120, $totalY);
        $this->pdf->Cell(40, 7, 'TOTAL A PAGAR:', 0, 0, 'R');
        $this->pdf->Cell(40, 7, 'S/ ' . number_format($total, 2), 1, 1, 'R', true);
        $this->pdf->SetTextColor(0, 0, 0);

        // Monto en letras
        $this->pdf->SetFont('Helvetica', 'I', 7);
        $this->pdf->SetXY(10, $totalY + 10);
        $this->pdf->Cell(190, 4, 'SON: ' . $this->utf8($this->numberToWords($total)) . ' SOLES', 0, 1, 'L');

        // Método de pago
        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->SetX(10);
        $metodo = $this->order->payment_method ? strtoupper($this->order->payment_method) : 'PENDIENTE';
        $this->pdf->Cell(190, 4, $this->utf8('Método de Pago: ' . $metodo . ' | Condición: CONTADO'), 0, 1, 'L');
    }

    private function drawQrAndHash(): void
    {
        $y = $this->pdf->GetY() + 8;

        // Simulación de QR (recuadro con texto)
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Rect(10, $y, 30, 30);
        $this->pdf->SetFont('Helvetica', '', 6);
        $this->pdf->SetXY(11, $y + 5);
        $this->pdf->MultiCell(28, 3, $this->utf8("QR SUNAT\n" . $this->rucEmisor . "\n" . $this->generateSerieNumero() . "\nIGV: " . number_format($this->invoice->igv, 2) . "\nTotal: " . number_format($this->invoice->total, 2)), 0, 'C');

        // Hash y datos de autorización
        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->SetXY(45, $y);
        $hash = $this->invoice->ose_hash ?: sha1($this->invoice->id . time());
        $hashCorto = substr($hash, 0, 20);

        $this->pdf->Cell(100, 4, $this->utf8('Autorizado mediante Resolución de Superintendencia'), 0, 1);
        $this->pdf->SetX(45);
        $this->pdf->Cell(100, 4, $this->utf8('Nro. 018-2019/SUNAT'), 0, 1);
        $this->pdf->SetX(45);
        $this->pdf->Cell(100, 4, '', 0, 1);
        $this->pdf->SetX(45);
        $this->pdf->SetFont('Helvetica', 'B', 7);
        $this->pdf->Cell(100, 4, 'Resumen: ' . $hashCorto, 0, 1);
        $this->pdf->SetX(45);
        $this->pdf->SetFont('Helvetica', '', 7);
        $this->pdf->Cell(100, 4, 'OSE Ticket: ' . ($this->invoice->ose_ticket ?: 'N/A'), 0, 1);
        $this->pdf->SetX(45);
        $this->pdf->Cell(100, 4, $this->utf8('Proveedor OSE: NUBEFACT S.A.C. - RUC 20600695771'), 0, 1);

        // Información del puesto vendedor
        $this->pdf->SetXY(45, $y + 24);
        $this->pdf->SetFont('Helvetica', 'I', 7);
        $stallName = $this->order->stall ? $this->order->stall->name : 'Puesto';
        $this->pdf->Cell(100, 4, $this->utf8('Punto de Venta: ' . $stallName), 0, 1);
    }

    private function drawFooter(): void
    {
        $y = $this->pdf->GetY() + 8;

        // Línea separadora
        $this->pdf->SetDrawColor(220, 53, 69);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Line(10, $y, 200, $y);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetDrawColor(0, 0, 0);

        $this->pdf->SetFont('Helvetica', 'I', 7);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->SetXY(10, $y + 2);
        $this->pdf->Cell(190, 4, $this->utf8('Representación impresa de la ' . ($this->invoice->type === 'factura' ? 'Factura Electrónica' : 'Boleta de Venta Electrónica')), 0, 1, 'C');
        $this->pdf->SetX(10);
        $this->pdf->Cell(190, 4, $this->utf8('Consulte su comprobante en: https://altoque.pe/comprobantes'), 0, 1, 'C');
        $this->pdf->SetX(10);
        $this->pdf->Cell(190, 4, $this->utf8('Este documento ha sido generado electrónicamente y tiene validez legal según D.L. 1314 y R.S. 340-2017/SUNAT'), 0, 1, 'C');
        $this->pdf->SetX(10);
        $this->pdf->Cell(190, 4, $this->utf8('Para consultas: ' . $this->email . ' | ' . $this->telefono), 0, 1, 'C');

        $this->pdf->SetTextColor(0, 0, 0);
    }

    private function generateSerieNumero(): string
    {
        $serie = $this->invoice->type === 'factura' ? 'F001' : 'B001';
        $numero = str_pad($this->invoice->id, 8, '0', STR_PAD_LEFT);
        return $serie . ' - ' . $numero;
    }

    private function utf8(string $text): string
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
    }

    private function numberToWords(float $number): string
    {
        $entero = (int) $number;
        $decimales = round(($number - $entero) * 100);

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = [11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
                       16 => 'DIECISEIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                     'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero === 0) return 'CERO CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
        if ($entero === 100) return 'CIEN CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';

        $resultado = '';

        if ($entero >= 1000) {
            $miles = (int) ($entero / 1000);
            if ($miles === 1) {
                $resultado .= 'MIL ';
            } else {
                $resultado .= $this->convertirGrupo($miles, $unidades, $decenas, $especiales, $centenas) . ' MIL ';
            }
            $entero %= 1000;
        }

        $resultado .= $this->convertirGrupo($entero, $unidades, $decenas, $especiales, $centenas);

        return trim($resultado) . ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
    }

    private function convertirGrupo(int $num, array $u, array $d, array $e, array $c): string
    {
        if ($num === 0) return '';
        if ($num === 100) return 'CIEN';

        $resultado = '';

        if ($num >= 100) {
            $resultado .= $c[(int)($num / 100)] . ' ';
            $num %= 100;
        }

        if (isset($e[$num])) {
            $resultado .= $e[$num];
            return $resultado;
        }

        if ($num >= 20) {
            $resultado .= $d[(int)($num / 10)];
            $resto = $num % 10;
            if ($resto > 0) {
                $resultado .= ' Y ' . $u[$resto];
            }
        } elseif ($num >= 10) {
            $resultado .= $d[$num / 10];
        } else {
            $resultado .= $u[$num];
        }

        return $resultado;
    }
}
