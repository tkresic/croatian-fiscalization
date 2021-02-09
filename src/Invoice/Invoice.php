<?php

namespace tonikresic\CroatianFiscalization\Invoice;

use XMLWriter;

class Invoice
{
    /**
     * Invoice UID.
     *
     * @var
     */
    public int $uid;

    /**
     * Invoice has tax.
     *
     * @var
     */
    public $haveTax;

    /**
     * Date and time.
     *
     * @var
     */
    public string $dateTime;

    /**
     * Note of order.
     *
     * @var string
     */
    public string $noteOfOrder = 'N';

    /**
     * Invoice number.
     *
     * @var
     */
    public InvoiceNumber $invoiceNumber;

    /**
     * Tax rates.
     *
     * @var array
     */
    public array $listTax;

    /**
     * Total value.
     *
     * @var
     */
    public int $totalValue;

    /**
     * Type of payment.
     *
     * @var string
     */
    public string $typeOfPayment;

    /**
     * Operative UID.
     *
     * @var string
     */
    public string $operativeUID;

    /**
     * Security code.
     *
     * @var string
     */
    public string $securityCode;

    /**
     * Note of redelivery.
     *
     * @var bool
     */
    public bool $noteOfRedelivery = false;

    /**
     * Sets UID.
     *
     * @param $uid
     */
    public function setUID($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Sets has tax.
     *
     * @param $haveTax
     */
    public function setHaveTax($haveTax)
    {
        $this->haveTax = $haveTax;
    }

    /**
     * Sets date and time.
     *
     * @param $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Sets note of order.
     *
     * @param $noteOfOrder
     */
    public function setNoteOfOrder($noteOfOrder)
    {
        $this->noteOfOrder = $noteOfOrder;
    }

    /**
     * Sets invoice number.
     *
     * @param $invoiceNumber
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * Sets lists of taxes.
     *
     * @param $listTax
     */
    public function setListTax($listTax)
    {
        $this->listTax = $listTax;
    }

    /**
     * Sets total value.
     *
     * @param $totalValue
     */
    public function setTotalValue($totalValue)
    {
        $this->totalValue = $totalValue;
    }

    /**
     * Sets type of payment.
     *
     * @param $typeOfPayment
     */
    public function setTypeOfPayment($typeOfPayment)
    {
        $this->typeOfPayment = $typeOfPayment;
    }

    /**
     * Sets operative UID.
     *
     * @param $operativeUID
     */
    public function setOperativeUID($operativeUID)
    {
        $this->operativeUID = $operativeUID;
    }

    /**
     * Sets security code.
     *
     * @param $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->securityCode = $securityCode;
    }

    /**
     * Sets note of redelivery.
     *
     * @param $noteOfRedelivery
     */
    public function setNoteOfRedelivery($noteOfRedelivery)
    {
        $this->noteOfRedelivery = $noteOfRedelivery;
    }

    /**
     * Generates security code based on given parameters.
     *
     * @param $pkey
     * @param $uid
     * @param $dt
     * @param $bor
     * @param $opp
     * @param $onu
     * @param $uir
     * @return string [type]       md5 hash
     * @throws \Exception
     */
    public function formatSecurityCode($pkey, $uid, $dt, $bor, $opp, $onu, $uir)
    {
        $formattedSecurityCode = '';
        $formattedSecurityCode .= $uid;
        $formattedSecurityCode .= $dt;
        $formattedSecurityCode .= $opp;
        $formattedSecurityCode .= $bor;
        $formattedSecurityCode .= $onu;
        $formattedSecurityCode .= $uir;

        $securityCodeSignature = null;
        if (!openssl_sign($formattedSecurityCode, $securityCodeSignature, $pkey, OPENSSL_ALGO_SHA1)) {
            throw new \Exception('Error creating security code.');
        }

        $this->securityCode = md5($securityCodeSignature);

        return $this->securityCode = md5($securityCodeSignature);
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
        $writer->startElementNs($ns, 'Racun', null);
        $writer->writeElementNs($ns, 'Oib', null, $this->uid);
        $writer->writeElementNs($ns, 'USustPdv', null, $this->haveTax ? "true" : "false");
        $writer->writeElementNs($ns, 'DatVrijeme', null, $this->dateTime);
        $writer->writeElementNs($ns, 'OznSlijed', null, $this->noteOfOrder);

        $writer->writeRaw($this->invoiceNumber->toXML());

        // Basic Tax (PDV)
        if (!empty($this->listTax)) {
            $writer->startElementNs($ns, 'Pdv', null);
            foreach ($this->listTax as $tax) {
                $writer->writeRaw($tax->toXML());
            }
            $writer->endElement();
        }

        $writer->writeElementNs($ns, 'IznosOslobPdv', null, number_format(null, 2, '.', ''));
        $writer->writeElementNs($ns, 'IznosMarza', null, number_format(null, 2, '.', ''));
        $writer->writeElementNs($ns, 'IznosNePodlOpor', null, number_format(null, 2, '.', ''));

        $writer->writeElementNs($ns, 'IznosUkupno', null, number_format($this->totalValue, 2, '.', ''));
        $writer->writeElementNs($ns, 'NacinPlac', null, $this->typeOfPayment);
        $writer->writeElementNs($ns, 'OibOper', null, $this->operativeUID);
        $writer->writeElementNs($ns, 'ZastKod', null, $this->securityCode);
        $writer->writeElementNs($ns, 'NakDost', null, $this->noteOfRedelivery ? "true" : "false");

        $writer->endElement();

        return $writer->outputMemory();
    }
}
