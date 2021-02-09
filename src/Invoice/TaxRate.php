<?php

namespace tonikresic\CroatianFiscalization\Invoice;

use XMLWriter;

class TaxRate
{
    /**
     * Tax name.
     *
     * @var string|null
     */
    public ?string $name;

    /**
     * Tax rate.
     *
     * @var string
     */
    private string $rate;

    /**
     * Base value.
     *
     * @var string
     */
    public string $baseValue;

    /**
     * Value.
     *
     * @var string
     */
    public string $value;

    /**
     * TaxRate constructor.
     *
     * @param $rate
     * @param $baseValue
     * @param $value
     * @param $name
     */
    public function __construct($rate, $baseValue, $value, $name)
    {
        $this->name = $name;
        $this->rate = number_format($rate, 2, '.', '');
        $this->baseValue = number_format($baseValue, 2, '.', '');
        $this->value = number_format($value, 2, '.', '');
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
        $writer->startElementNs($ns, 'Porez', null);
        if ($this->name) $writer->writeElementNs($ns, 'Naziv', null, $this->name);
        $writer->writeElementNs($ns, 'Stopa', null, $this->rate);
        $writer->writeElementNs($ns, 'Osnovica', null, $this->baseValue);
        $writer->writeElementNs($ns, 'Iznos', null, $this->value);
        $writer->endElement();

        return $writer->outputMemory();
    }
}