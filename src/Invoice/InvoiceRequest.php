<?php

namespace tonikresic\CroatianFiscalization\Invoice;

use XMLWriter;

class InvoiceRequest
{
    /**
     * Request name.
     *
     * @var string
     */
    protected string $requestName;

    /**
     * Invoice.
     *
     * @var Invoice
     */
    protected Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->requestName = 'RacunZahtjev';
    }

    /**
     * Generates UUID.
     *
     * @return string
     */
    public function generateUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Writes to XML.
     *
     * @return string
     */
    public function toXML(): string {
        $ns = 'tns';

        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->setIndent(true);
        $writer->setIndentString("    ");
        $writer->startElementNs($ns, $this->requestName, 'http://www.apis-it.hr/fin/2012/types/f73');
        $writer->writeAttribute('Id', uniqid());
        $writer->startElementNs($ns, 'Zaglavlje', null);
        $writer->writeElementNs($ns, 'IdPoruke', null, $this->generateUUID());
        $writer->writeElementNs($ns, 'DatumVrijeme', null, date('d.m.Y\TH:i:s'));
        $writer->endElement();

        $writer->writeRaw($this->invoice->toXML());
        $writer->endElement();

        return $writer->outputMemory();
    }
}