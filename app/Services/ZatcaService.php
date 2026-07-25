<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;

class ZatcaService
{
    /**
     * Generate ZATCA Phase 1 compliant data for an invoice.
     */
    public function generatePhase1(Invoice $invoice): Invoice
    {
        $invoice->load('company', 'client', 'items');

        if (!$invoice->uuid) {
            $invoice->uuid = (string) Str::uuid();
        }

        $invoice->zatca_qr_tlv = $this->generateTlvQr($invoice);
        $invoice->zatca_xml = $this->generateSimplifiedXml($invoice);
        $invoice->zatca_hash = hash('sha256', $invoice->zatca_xml);
        $invoice->zatca_status = 'pending';
        $invoice->saveQuietly();

        return $invoice;
    }

    /**
     * TLV (Tag-Length-Value) encoded QR code per ZATCA spec.
     * Tags: 1=Seller, 2=TRN, 3=Timestamp, 4=Total, 5=VAT
     */
    private function generateTlvQr(Invoice $invoice): string
    {
        $company = $invoice->company;

        $fields = [
            1 => $company->name,
            2 => $company->tax_registration_number ?? '',
            3 => $invoice->issue_date->toIso8601String(),
            4 => number_format((float) $invoice->total, 2, '.', ''),
            5 => number_format((float) $invoice->vat_amount, 2, '.', ''),
        ];

        $tlv = '';
        foreach ($fields as $tag => $value) {
            $tlv .= chr($tag) . chr(strlen($value)) . $value;
        }

        return base64_encode($tlv);
    }

    /**
     * Simplified XML for ZATCA Phase 1 (offline generation).
     */
    private function generateSimplifiedXml(Invoice $invoice): string
    {
        $company = $invoice->company;
        $client = $invoice->client;

        $itemsXml = '';
        foreach ($invoice->items as $item) {
            $itemsXml .= <<<XML
        <cac:InvoiceLine>
            <cbc:ID>{$item->id}</cbc:ID>
            <cbc:InvoicedQuantity unitCode="PCE">{$item->quantity}</cbc:InvoicedQuantity>
            <cbc:LineExtensionAmount currencyID="{$invoice->currency}">{$item->line_total}</cbc:LineExtensionAmount>
            <cac:Item><cbc:Name>{$this->xmlEscape($item->description)}</cbc:Name></cac:Item>
            <cac:Price><cbc:PriceAmount currencyID="{$invoice->currency}">{$item->unit_price}</cbc:PriceAmount></cac:Price>
            <cac:TaxTotal><cbc:TaxAmount currencyID="{$invoice->currency}">{$item->vat_amount}</cbc:TaxAmount></cac:TaxTotal>
        </cac:InvoiceLine>
XML;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:ProfileID>reporting:1.0</cbc:ProfileID>
    <cbc:ID>{$invoice->invoice_number}</cbc:ID>
    <cbc:UUID>{$invoice->uuid}</cbc:UUID>
    <cbc:IssueDate>{$invoice->issue_date->toDateString()}</cbc:IssueDate>
    <cbc:DueDate>{$invoice->due_date->toDateString()}</cbc:DueDate>
    <cbc:InvoiceTypeCode>388</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>{$invoice->currency}</cbc:DocumentCurrencyCode>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification><cbc:ID schemeID="TRN">{$company->tax_registration_number}</cbc:ID></cac:PartyIdentification>
            <cac:PartyName><cbc:Name>{$this->xmlEscape($company->name)}</cbc:Name></cac:PartyName>
            <cac:PostalAddress><cbc:CityName>{$company->city}</cbc:CityName><cac:Country><cbc:IdentificationCode>{$company->country}</cbc:IdentificationCode></cac:Country></cac:PostalAddress>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification><cbc:ID schemeID="TRN">{$client->tax_registration_number}</cbc:ID></cac:PartyIdentification>
            <cac:PartyName><cbc:Name>{$this->xmlEscape($client->name)}</cbc:Name></cac:PartyName>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="{$invoice->currency}">{$invoice->subtotal}</cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="{$invoice->currency}">{$invoice->subtotal}</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="{$invoice->currency}">{$invoice->total}</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="{$invoice->currency}">{$invoice->total}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="{$invoice->currency}">{$invoice->vat_amount}</cbc:TaxAmount>
    </cac:TaxTotal>
{$itemsXml}
</Invoice>
XML;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
