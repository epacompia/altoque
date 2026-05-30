<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

/**
 * Mock adapter for an OSE (facturación electrónica) provider.
 * Genera XML en formato UBL 2.1 compatible con SUNAT.
 * In production replace with real integration (PSE/OSE provider SDK/http client).
 */
class OseAdapterMock
{
    private string $rucEmisor = '20612345678';
    private string $razonSocial = 'ALTOQUE DELIVERY S.A.C.';
    private string $nombreComercial = 'AlToque';
    private string $direccionEmisor = 'Av. Javier Prado Este 1234, San Isidro, Lima';
    private string $ubigeo = '150131';
    private string $departamento = 'LIMA';
    private string $provincia = 'LIMA';
    private string $distrito = 'SAN ISIDRO';

    public function sendInvoice(array $payload): array
    {
        $timestamp = now()->format('YmdHis');
        $oseTicket = 'OSE-' . $timestamp . '-' . rand(1000, 9999);
        $hash = sha1(json_encode($payload) . $oseTicket);

        $xml = $this->generateUblXml($payload, $hash, $oseTicket);
        $pdf = "PDF_PLACEHOLDER"; // El PDF real se genera con InvoicePdfGenerator

        return [
            'success' => true,
            'ose_ticket' => $oseTicket,
            'ose_hash' => $hash,
            'xml' => $xml,
            'pdf' => $pdf,
        ];
    }

    private function generateUblXml(array $payload, string $hash, string $oseTicket): string
    {
        $fechaEmision = now()->format('Y-m-d');
        $horaEmision = now()->format('H:i:s');
        $tipoDoc = ($payload['type'] ?? 'boleta') === 'factura' ? '01' : '03';
        $serie = $tipoDoc === '01' ? 'F001' : 'B001';
        $numero = str_pad($payload['order_id'], 8, '0', STR_PAD_LEFT);
        $serieNumero = $serie . '-' . $numero;

        $subtotal = number_format($payload['subtotal'] ?? 0, 2, '.', '');
        $igv = number_format($payload['igv'] ?? 0, 2, '.', '');
        $total = number_format($payload['total'] ?? 0, 2, '.', '');
        $opGravada = number_format(($payload['subtotal'] ?? 0), 2, '.', '');

        $customerName = htmlspecialchars($payload['customer_name'] ?? 'CLIENTE GENERAL', ENT_XML1);
        $customerDocType = ($payload['customer_document_type'] ?? 'DNI') === 'RUC' ? '6' : '1';
        $customerDocNumber = $payload['customer_document_number'] ?? '00000000';

        // Obtener items del pedido
        $itemsXml = $this->generateItemsXml($payload);

        $signatureValue = base64_encode(hash('sha256', $serieNumero . $total . $hash, true));
        $digestValue = base64_encode(hash('sha256', json_encode($payload), true));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' . "\n";
        $xml .= '         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' . "\n";
        $xml .= '         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"' . "\n";
        $xml .= '         xmlns:ds="http://www.w3.org/2000/09/xmldsig#"' . "\n";
        $xml .= '         xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">' . "\n\n";

        // UBL Extensions (Firma digital)
        $xml .= '  <ext:UBLExtensions>' . "\n";
        $xml .= '    <ext:UBLExtension>' . "\n";
        $xml .= '      <ext:ExtensionContent>' . "\n";
        $xml .= '        <ds:Signature Id="SignAlToque">' . "\n";
        $xml .= '          <ds:SignedInfo>' . "\n";
        $xml .= '            <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>' . "\n";
        $xml .= '            <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>' . "\n";
        $xml .= '            <ds:Reference URI="">' . "\n";
        $xml .= '              <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' . "\n";
        $xml .= '              <ds:DigestValue>' . $digestValue . '</ds:DigestValue>' . "\n";
        $xml .= '            </ds:Reference>' . "\n";
        $xml .= '          </ds:SignedInfo>' . "\n";
        $xml .= '          <ds:SignatureValue>' . $signatureValue . '</ds:SignatureValue>' . "\n";
        $xml .= '        </ds:Signature>' . "\n";
        $xml .= '      </ext:ExtensionContent>' . "\n";
        $xml .= '    </ext:UBLExtension>' . "\n";
        $xml .= '  </ext:UBLExtensions>' . "\n\n";

        // Datos del documento
        $xml .= '  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>' . "\n";
        $xml .= '  <cbc:CustomizationID>2.0</cbc:CustomizationID>' . "\n";
        $xml .= '  <cbc:ID>' . $serieNumero . '</cbc:ID>' . "\n";
        $xml .= '  <cbc:IssueDate>' . $fechaEmision . '</cbc:IssueDate>' . "\n";
        $xml .= '  <cbc:IssueTime>' . $horaEmision . '</cbc:IssueTime>' . "\n";
        $xml .= '  <cbc:InvoiceTypeCode listID="0101">' . $tipoDoc . '</cbc:InvoiceTypeCode>' . "\n";
        $xml .= '  <cbc:Note languageLocaleID="1000"><![CDATA[' . $this->numberToWords((float)$total) . ' SOLES]]></cbc:Note>' . "\n";
        $xml .= '  <cbc:DocumentCurrencyCode>PEN</cbc:DocumentCurrencyCode>' . "\n\n";

        // Firma
        $xml .= '  <cac:Signature>' . "\n";
        $xml .= '    <cbc:ID>IDSign' . $this->rucEmisor . '</cbc:ID>' . "\n";
        $xml .= '    <cac:SignatoryParty>' . "\n";
        $xml .= '      <cac:PartyIdentification>' . "\n";
        $xml .= '        <cbc:ID>' . $this->rucEmisor . '</cbc:ID>' . "\n";
        $xml .= '      </cac:PartyIdentification>' . "\n";
        $xml .= '      <cac:PartyName>' . "\n";
        $xml .= '        <cbc:Name><![CDATA[' . $this->razonSocial . ']]></cbc:Name>' . "\n";
        $xml .= '      </cac:PartyName>' . "\n";
        $xml .= '    </cac:SignatoryParty>' . "\n";
        $xml .= '    <cac:DigitalSignatureAttachment>' . "\n";
        $xml .= '      <cac:ExternalReference>' . "\n";
        $xml .= '        <cbc:URI>#SignAlToque</cbc:URI>' . "\n";
        $xml .= '      </cac:ExternalReference>' . "\n";
        $xml .= '    </cac:DigitalSignatureAttachment>' . "\n";
        $xml .= '  </cac:Signature>' . "\n\n";

        // Datos del emisor
        $xml .= '  <cac:AccountingSupplierParty>' . "\n";
        $xml .= '    <cac:Party>' . "\n";
        $xml .= '      <cac:PartyIdentification>' . "\n";
        $xml .= '        <cbc:ID schemeID="6">' . $this->rucEmisor . '</cbc:ID>' . "\n";
        $xml .= '      </cac:PartyIdentification>' . "\n";
        $xml .= '      <cac:PartyName>' . "\n";
        $xml .= '        <cbc:Name><![CDATA[' . $this->nombreComercial . ']]></cbc:Name>' . "\n";
        $xml .= '      </cac:PartyName>' . "\n";
        $xml .= '      <cac:PartyLegalEntity>' . "\n";
        $xml .= '        <cbc:RegistrationName><![CDATA[' . $this->razonSocial . ']]></cbc:RegistrationName>' . "\n";
        $xml .= '        <cac:RegistrationAddress>' . "\n";
        $xml .= '          <cbc:ID>' . $this->ubigeo . '</cbc:ID>' . "\n";
        $xml .= '          <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>' . "\n";
        $xml .= '          <cbc:CitySubdivisionName>NINGUNO</cbc:CitySubdivisionName>' . "\n";
        $xml .= '          <cbc:CityName>' . $this->provincia . '</cbc:CityName>' . "\n";
        $xml .= '          <cbc:CountrySubentity>' . $this->departamento . '</cbc:CountrySubentity>' . "\n";
        $xml .= '          <cbc:District>' . $this->distrito . '</cbc:District>' . "\n";
        $xml .= '          <cac:AddressLine>' . "\n";
        $xml .= '            <cbc:Line><![CDATA[' . $this->direccionEmisor . ']]></cbc:Line>' . "\n";
        $xml .= '          </cac:AddressLine>' . "\n";
        $xml .= '          <cac:Country>' . "\n";
        $xml .= '            <cbc:IdentificationCode>PE</cbc:IdentificationCode>' . "\n";
        $xml .= '          </cac:Country>' . "\n";
        $xml .= '        </cac:RegistrationAddress>' . "\n";
        $xml .= '      </cac:PartyLegalEntity>' . "\n";
        $xml .= '    </cac:Party>' . "\n";
        $xml .= '  </cac:AccountingSupplierParty>' . "\n\n";

        // Datos del receptor/cliente
        $xml .= '  <cac:AccountingCustomerParty>' . "\n";
        $xml .= '    <cac:Party>' . "\n";
        $xml .= '      <cac:PartyIdentification>' . "\n";
        $xml .= '        <cbc:ID schemeID="' . $customerDocType . '">' . $customerDocNumber . '</cbc:ID>' . "\n";
        $xml .= '      </cac:PartyIdentification>' . "\n";
        $xml .= '      <cac:PartyLegalEntity>' . "\n";
        $xml .= '        <cbc:RegistrationName><![CDATA[' . $customerName . ']]></cbc:RegistrationName>' . "\n";
        $xml .= '      </cac:PartyLegalEntity>' . "\n";
        $xml .= '    </cac:Party>' . "\n";
        $xml .= '  </cac:AccountingCustomerParty>' . "\n\n";

        // Condiciones de pago
        $xml .= '  <cac:PaymentTerms>' . "\n";
        $xml .= '    <cbc:ID>FormaPago</cbc:ID>' . "\n";
        $xml .= '    <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>' . "\n";
        $xml .= '  </cac:PaymentTerms>' . "\n\n";

        // Totales de impuestos
        $xml .= '  <cac:TaxTotal>' . "\n";
        $xml .= '    <cbc:TaxAmount currencyID="PEN">' . $igv . '</cbc:TaxAmount>' . "\n";
        $xml .= '    <cac:TaxSubtotal>' . "\n";
        $xml .= '      <cbc:TaxableAmount currencyID="PEN">' . $opGravada . '</cbc:TaxableAmount>' . "\n";
        $xml .= '      <cbc:TaxAmount currencyID="PEN">' . $igv . '</cbc:TaxAmount>' . "\n";
        $xml .= '      <cac:TaxCategory>' . "\n";
        $xml .= '        <cac:TaxScheme>' . "\n";
        $xml .= '          <cbc:ID>1000</cbc:ID>' . "\n";
        $xml .= '          <cbc:Name>IGV</cbc:Name>' . "\n";
        $xml .= '          <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>' . "\n";
        $xml .= '        </cac:TaxScheme>' . "\n";
        $xml .= '      </cac:TaxCategory>' . "\n";
        $xml .= '    </cac:TaxSubtotal>' . "\n";
        $xml .= '  </cac:TaxTotal>' . "\n\n";

        // Totales del documento
        $xml .= '  <cac:LegalMonetaryTotal>' . "\n";
        $xml .= '    <cbc:LineExtensionAmount currencyID="PEN">' . $opGravada . '</cbc:LineExtensionAmount>' . "\n";
        $xml .= '    <cbc:TaxInclusiveAmount currencyID="PEN">' . $total . '</cbc:TaxInclusiveAmount>' . "\n";
        $xml .= '    <cbc:PayableAmount currencyID="PEN">' . $total . '</cbc:PayableAmount>' . "\n";
        $xml .= '  </cac:LegalMonetaryTotal>' . "\n\n";

        // Items
        $xml .= $itemsXml;

        $xml .= '</Invoice>';

        return $xml;
    }

    private function generateItemsXml(array $payload): string
    {
        $xml = '';
        $orderId = $payload['order_id'];

        // Intentar obtener items reales del pedido
        $order = Order::with(['items.product'])->find($orderId);

        if ($order && $order->items->count() > 0) {
            $lineNumber = 1;
            foreach ($order->items as $item) {
                $productName = htmlspecialchars($item->product->name ?? 'Producto', ENT_XML1);
                $cantidad = $item->quantity;
                $precioUnit = number_format($item->price_per_unit, 2, '.', '');
                $subtotalItem = number_format($item->subtotal, 2, '.', '');
                $igvItem = number_format($item->subtotal * 0.18, 2, '.', '');
                $totalItem = number_format($item->subtotal + ($item->subtotal * 0.18), 2, '.', '');

                $xml .= $this->buildInvoiceLine($lineNumber, $cantidad, 'NIU', $productName, $precioUnit, $subtotalItem, $igvItem, $totalItem);
                $lineNumber++;
            }

            // Agregar delivery si aplica
            if ($order->delivery_cost > 0) {
                $deliveryCost = number_format($order->delivery_cost, 2, '.', '');
                $deliveryIgv = number_format($order->delivery_cost * 0.18, 2, '.', '');
                $deliveryTotal = number_format($order->delivery_cost + ($order->delivery_cost * 0.18), 2, '.', '');
                $xml .= $this->buildInvoiceLine($lineNumber, 1, 'ZZ', 'Servicio de Delivery', $deliveryCost, $deliveryCost, $deliveryIgv, $deliveryTotal);
            }
        } else {
            // Fallback: item único con el total
            $xml .= $this->buildInvoiceLine(1, 1, 'NIU', 'Pedido #' . $orderId, $payload['subtotal'], $payload['subtotal'], $payload['igv'], $payload['total']);
        }

        return $xml;
    }

    private function buildInvoiceLine(int $lineNumber, int $cantidad, string $unitCode, string $description, string $precioUnit, string $subtotal, string $igv, string $total): string
    {
        $xml = '  <cac:InvoiceLine>' . "\n";
        $xml .= '    <cbc:ID>' . $lineNumber . '</cbc:ID>' . "\n";
        $xml .= '    <cbc:InvoicedQuantity unitCode="' . $unitCode . '">' . $cantidad . '</cbc:InvoicedQuantity>' . "\n";
        $xml .= '    <cbc:LineExtensionAmount currencyID="PEN">' . $subtotal . '</cbc:LineExtensionAmount>' . "\n";
        $xml .= '    <cac:PricingReference>' . "\n";
        $xml .= '      <cac:AlternativeConditionPrice>' . "\n";
        $xml .= '        <cbc:PriceAmount currencyID="PEN">' . number_format((float)$precioUnit * 1.18, 2, '.', '') . '</cbc:PriceAmount>' . "\n";
        $xml .= '        <cbc:PriceTypeCode>01</cbc:PriceTypeCode>' . "\n";
        $xml .= '      </cac:AlternativeConditionPrice>' . "\n";
        $xml .= '    </cac:PricingReference>' . "\n";
        $xml .= '    <cac:TaxTotal>' . "\n";
        $xml .= '      <cbc:TaxAmount currencyID="PEN">' . $igv . '</cbc:TaxAmount>' . "\n";
        $xml .= '      <cac:TaxSubtotal>' . "\n";
        $xml .= '        <cbc:TaxableAmount currencyID="PEN">' . $subtotal . '</cbc:TaxableAmount>' . "\n";
        $xml .= '        <cbc:TaxAmount currencyID="PEN">' . $igv . '</cbc:TaxAmount>' . "\n";
        $xml .= '        <cac:TaxCategory>' . "\n";
        $xml .= '          <cbc:Percent>18.00</cbc:Percent>' . "\n";
        $xml .= '          <cbc:TaxExemptionReasonCode>10</cbc:TaxExemptionReasonCode>' . "\n";
        $xml .= '          <cac:TaxScheme>' . "\n";
        $xml .= '            <cbc:ID>1000</cbc:ID>' . "\n";
        $xml .= '            <cbc:Name>IGV</cbc:Name>' . "\n";
        $xml .= '            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>' . "\n";
        $xml .= '          </cac:TaxScheme>' . "\n";
        $xml .= '        </cac:TaxCategory>' . "\n";
        $xml .= '      </cac:TaxSubtotal>' . "\n";
        $xml .= '    </cac:TaxTotal>' . "\n";
        $xml .= '    <cac:Item>' . "\n";
        $xml .= '      <cbc:Description><![CDATA[' . $description . ']]></cbc:Description>' . "\n";
        $xml .= '    </cac:Item>' . "\n";
        $xml .= '    <cac:Price>' . "\n";
        $xml .= '      <cbc:PriceAmount currencyID="PEN">' . $precioUnit . '</cbc:PriceAmount>' . "\n";
        $xml .= '    </cac:Price>' . "\n";
        $xml .= '  </cac:InvoiceLine>' . "\n\n";

        return $xml;
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
            $resultado .= ($miles === 1) ? 'MIL ' : $this->convertGroup($miles, $unidades, $decenas, $especiales, $centenas) . ' MIL ';
            $entero %= 1000;
        }
        $resultado .= $this->convertGroup($entero, $unidades, $decenas, $especiales, $centenas);
        return trim($resultado) . ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
    }

    private function convertGroup(int $num, array $u, array $d, array $e, array $c): string
    {
        if ($num === 0) return '';
        if ($num === 100) return 'CIEN';
        $r = '';
        if ($num >= 100) { $r .= $c[(int)($num / 100)] . ' '; $num %= 100; }
        if (isset($e[$num])) { $r .= $e[$num]; return $r; }
        if ($num >= 20) { $r .= $d[(int)($num / 10)]; $resto = $num % 10; if ($resto > 0) $r .= ' Y ' . $u[$resto]; }
        elseif ($num >= 10) { $r .= $d[$num / 10]; }
        else { $r .= $u[$num]; }
        return $r;
    }
}
