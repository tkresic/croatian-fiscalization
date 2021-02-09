<?php

namespace tonikresic\CroatianFiscalization\Invoice;

use XMLWriter;

class InvoiceNumber
{
    /**
     * Invoice number.
     *
     * @var string
     */
    public string $numberNoteInvoice;

    /**
     * Invoice business place label.
     *
     * @var string
     */
    public string $noteOfBusinessArea;

    /**
     * Invoice cash register label.
     *
     * @var string
     */
    public string $noteOfExchangeDevice;

    /**
     * InvoiceNumber constructor.
     *
     * @param $numberNoteInvoice
     * @param $noteOfBusinessArea
     * @param $noteOfExchangeDevice
     */
    public function __construct($numberNoteInvoice, $noteOfBusinessArea, $noteOfExchangeDevice)
    {
        $this->numberNoteInvoice = $numberNoteInvoice;
        $this->noteOfBusinessArea = $noteOfBusinessArea;
        $this->noteOfExchangeDevice = $noteOfExchangeDevice;
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
        $writer->setIndentString('    ');
        $writer->startElementNs($ns, 'BrRac', null);
        $writer->writeElementNs($ns, 'BrOznRac', null, $this->numberNoteInvoice);
        $writer->writeElementNs($ns, 'OznPosPr', null, $this->noteOfBusinessArea);
        $writer->writeElementNs($ns, 'OznNapUr', null, $this->noteOfExchangeDevice);
        $writer->endElement();

        return $writer->outputMemory();
    }
}