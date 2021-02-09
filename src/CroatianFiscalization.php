<?php

namespace tonikresic\CroatianFiscalization;

use DOMDocument;
use DOMElement;
use Exception;
use tonikresic\CroatianFiscalization\Company\Company;
use tonikresic\CroatianFiscalization\Invoice\Invoice;
use tonikresic\CroatianFiscalization\Invoice\InvoiceNumber;
use tonikresic\CroatianFiscalization\Invoice\InvoiceRequest;
use tonikresic\CroatianFiscalization\Invoice\TaxRate;

class CroatianFiscalization
{
    /**
     * Company.
     *
     * @var Company
     */
    private Company $company;

    /**
     * Invoice number.
     *
     * @var InvoiceNumber
     */
    private InvoiceNumber $invoiceNumber;

    /**
     * Certificate data.
     *
     * @var array|null
     */
    private ?array $certificate;


    /**
     * Security protocol.
     *
     * @var string
     */
    private string $security = 'TLS';

    /**
     * Private key resource.
     *
     * @var
     */
    private $privateKeyResource;

    /**
     * Certificate data.
     *
     * @var array
     */
    private array $publicCertificateData;

    /**
     * Croatian fiscalization Demo API URL. The real URL is commented out and requires real certificate in order to work.
     *
     * @var string
     */
    private string $url = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest'; //'https://cis.porezna-uprava.hr:8449/FiskalizacijaService';

    /**
     * Demo fiscalization.
     *
     * @var bool
     */
    private bool $demo = true;

    /**
     * Configures fiscalization data.
     *
     * @param Company $company
     */
    public function configure(Company $company)
    {
        $this->company = $company;

        if ($this->company->demo) {
            $this->url = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';
        }

        try {
            $this->setCertificate($company->certificate->path, $company->certificate->password);
        } catch (Exception $e) {
            fwrite(STDERR, print_r('An exception happened: ' . $e->getMessage(), TRUE));
        }
    }

    /**
     * Validates required attributes.
     *
     * @param object $invoiceObject
     * @return object
     */
    protected function validate(object $invoiceObject): object
    {

        if (!isset($invoiceObject->userUID) || empty($invoiceObject->userUID)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice user UID is required.'
            ];
        }

        if (!isset($invoiceObject->company) || empty($invoiceObject->company)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice company is required.'
            ];
        }

        if (!isset($invoiceObject->fiscal_data) || empty($invoiceObject->fiscal_data)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice fiscal data is required.'
            ];
        }

        if (!isset($invoiceObject->fiscal_abbreviation) || empty($invoiceObject->fiscal_abbreviation)) {
            return (object) [
                'success' => false,
                'message' => 'Fiscal abbreviation is required.'
            ];
        }

        if (!isset($invoiceObject->gross) || empty($invoiceObject->gross)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice gross is required.'
            ];
        }

        if (!isset($invoiceObject->net) || empty($invoiceObject->net)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice net is required.'
            ];
        }

        if (!isset($invoiceObject->number) || empty($invoiceObject->number)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice number is required.'
            ];
        }

        if (!isset($invoiceObject->business_place_label) || empty($invoiceObject->business_place_label)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice business place label is required.'
            ];
        }

        if (!isset($invoiceObject->cash_register_label) || empty($invoiceObject->cash_register_label)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice cash register label is required.'
            ];
        }

        if (!isset($invoiceObject->created_at) || empty($invoiceObject->created_at)) {
            return (object) [
                'success' => false,
                'message' => 'Invoice created at field is required.'
            ];
        }

        return (object) [
            'success' => true,
            'message' => NULL
        ];
    }

    /**
     * Fiscalizes the given Invoice.
     *
     * @param object $invoiceObject
     * @return object
     * @throws Exception
     */
    public function fiscalize(object $invoiceObject): object
    {
        $validation = $this->validate($invoiceObject);

        if (!$validation->success) {
            return (object) [
                'success' => $validation->success,
                'message' => $validation->message
            ];
        }

        $this->createInvoiceNumber($invoiceObject);

        $fiscalizedInvoice = $this->createFiscalizedInvoice($invoiceObject);

        if ($invoiceObject->fiscal_data && $invoiceObject->fiscal_data->zki) {
            $fiscalizedInvoice->setSecurityCode($invoiceObject->fiscal_data->zki);
            $fiscalizedInvoice->setNoteOfRedelivery(true);
        } else {
            $fiscalizedInvoice->setSecurityCode(
                $fiscalizedInvoice->formatSecurityCode(
                    $this->certificate['pkey'],
                    $fiscalizedInvoice->uid,
                    $fiscalizedInvoice->dateTime,
                    $this->invoiceNumber->numberNoteInvoice,
                    $this->invoiceNumber->noteOfBusinessArea,
                    $this->invoiceNumber->noteOfExchangeDevice,
                    $fiscalizedInvoice->totalValue
                )
            );
            $fiscalizedInvoice->setNoteOfRedelivery(false);
            $fiscalizedInvoice->zki = $fiscalizedInvoice->securityCode;
        }

        $invoice_request = new InvoiceRequest($fiscalizedInvoice);
        try {
            $soapMessage = $this->signXML($invoice_request->toXML());
            $res = $this->sendSoap($soapMessage);

            $cleanXML = str_ireplace(['SOAP-ENV:', 'SOAP:', 'tns:'], '', $res);
            $response = simplexml_load_string($cleanXML);

            if ($response->Body->RacunOdgovor->Greske) {
                $error = $response->Body->RacunOdgovor->Greske->Greska;
                return (object) [
                    'success' => false,
                    'message' => "An error occurred with message: {$error->SifraGreske}: {$error->PorukaGreske}"
                ];
            }

            $jir = (string) $response->Body->RacunOdgovor->Jir;
            $date = (string) $response->Body->RacunOdgovor->Zaglavlje->DatumVrijeme;

            return (object) [
                'success' => true,
                'zki' => $fiscalizedInvoice->zki,
                'jir' => $jir,
                'fiscal_at' => \DateTime::createFromFormat('d.m.Y\TH:i:s', $date)->format('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            return (object) [
                'success' => false,
                'message' => 'An exception occurred with message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Creates InvoiceNumber from Invoice data.
     *
     * @param object $invoiceObject
     */
    public function createInvoiceNumber(object $invoiceObject): void
    {
        $this->invoiceNumber = new InvoiceNumber(
            $invoiceObject->number,
            $invoiceObject->business_place_label,
            $invoiceObject->cash_register_label
        );
    }

    /**
     * Creates instance of a Invoice class for further fiscalization.
     *
     * @param object $invoiceObject
     * @return Invoice
     */
    public function createFiscalizedInvoice(object $invoiceObject): Invoice
    {
        $billedAt = \DateTime::createFromFormat('Y-m-d H:i:s', $invoiceObject->created_at)->format('d.m.Y\TH:i:s');
        $fiscalizedInvoice = new Invoice();
        $fiscalizedInvoice->setUID($this->company->uid);
        $fiscalizedInvoice->setHaveTax($this->company->shouldBeTaxed);
        $fiscalizedInvoice->setInvoiceNumber($this->invoiceNumber);
        $fiscalizedInvoice->setNoteOfOrder('P');
        $fiscalizedInvoice->setDateTime($billedAt);
        $taxRate = $this->company->shouldBeTaxed ? (100 * ($invoiceObject->gross / $invoiceObject->net - 1.0)) : 0;
        $fiscalizedInvoice->setListTax([
            new TaxRate(round($taxRate, 2), $invoiceObject->net, ($invoiceObject->gross - $invoiceObject->net), null)
        ]);
        $fiscalizedInvoice->setTotalValue($invoiceObject->gross);
        $fiscalizedInvoice->setTypeOfPayment($invoiceObject->fiscal_abbreviation);
        $fiscalizedInvoice->setOperativeUID($invoiceObject->userUID);

        return $fiscalizedInvoice;
    }

    /**
     * Sets certificate data.
     *
     * @param $path
     * @param $pass
     * @throws Exception
     */
    public function setCertificate($path, $pass)
    {
        $pkcs12 = $this->getCertificate($path);
        openssl_pkcs12_read($pkcs12, $this->certificate, $pass);
        $this->privateKeyResource = openssl_pkey_get_private($this->certificate['pkey'], $pass);
        $this->publicCertificateData = openssl_x509_parse($this->certificate['cert']);
    }

    /**
     * Get certificate.
     *
     * @param $path
     * @return false|string
     * @throws Exception
     */
    public function getCertificate($path)
    {
        if (!$cert = @file_get_contents($path)) {
            throw new \Exception('Cannot read certificate from location: ' . $path, 1);
        }
        return $cert;
    }

    /**
     * Constructs SOAP message.
     *
     * @param $XMLRequest
     * @return string
     * @throws Exception
     */
    public function signXML($XMLRequest)
    {
        $XMLRequestDOMDoc = new DOMDocument();
        $XMLRequestDOMDoc->loadXML($XMLRequest);

        $canonical = $XMLRequestDOMDoc->C14N();
        $DigestValue = base64_encode(hash('sha1', $canonical, true));

        $rootElem = $XMLRequestDOMDoc->documentElement;

        $SignatureNode = $rootElem->appendChild(new DOMElement('Signature'));
        $SignatureNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $SignedInfoNode = $SignatureNode->appendChild(new DOMElement('SignedInfo'));
        $SignedInfoNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $CanonicalizationMethodNode = $SignedInfoNode->appendChild(new DOMElement('CanonicalizationMethod'));
        $CanonicalizationMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $SignatureMethodNode = $SignedInfoNode->appendChild(new DOMElement('SignatureMethod'));
        $SignatureMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

        $ReferenceNode = $SignedInfoNode->appendChild(new DOMElement('Reference'));
        $ReferenceNode->setAttribute('URI', sprintf('#%s', $XMLRequestDOMDoc->documentElement->getAttribute('Id')));

        $TransformsNode = $ReferenceNode->appendChild(new DOMElement('Transforms'));

        $Transform1Node = $TransformsNode->appendChild(new DOMElement('Transform'));
        $Transform1Node->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');

        $Transform2Node = $TransformsNode->appendChild(new DOMElement('Transform'));
        $Transform2Node->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $DigestMethodNode = $ReferenceNode->appendChild(new DOMElement('DigestMethod'));
        $DigestMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

        $ReferenceNode->appendChild(new DOMElement('DigestValue', $DigestValue));

        $SignedInfoNode = $XMLRequestDOMDoc->getElementsByTagName('SignedInfo')->item(0);

        $X509Issuer = $this->publicCertificateData['issuer'];
        $X509IssuerName = sprintf('O=%s,C=%s', $X509Issuer['O'], $X509Issuer['C']);
        $X509IssuerSerial = $this->publicCertificateData['serialNumber'];

        $publicCertificatePureString = str_replace('-----BEGIN CERTIFICATE-----', '', $this->certificate['cert']);
        $publicCertificatePureString = str_replace('-----END CERTIFICATE-----', '', $publicCertificatePureString);

        $signedInfoSignature = null;

        if (!openssl_sign($SignedInfoNode->C14N(true), $signedInfoSignature, $this->privateKeyResource, OPENSSL_ALGO_SHA1))
            throw new Exception('Unable to sign the SOAP request for CroatianFiscalization.');

        $SignatureNode = $XMLRequestDOMDoc->getElementsByTagName('Signature')->item(0);
        $SignatureValueNode = new DOMElement('SignatureValue', base64_encode($signedInfoSignature));
        $SignatureNode->appendChild($SignatureValueNode);

        $KeyInfoNode = $SignatureNode->appendChild(new DOMElement('KeyInfo'));

        $X509DataNode = $KeyInfoNode->appendChild(new DOMElement('X509Data'));
        $X509CertificateNode = new DOMElement('X509Certificate', $publicCertificatePureString);
        $X509DataNode->appendChild($X509CertificateNode);

        $X509IssuerSerialNode = $X509DataNode->appendChild(new DOMElement('X509IssuerSerial'));

        $X509IssuerNameNode = new DOMElement('X509IssuerName', $X509IssuerName);
        $X509IssuerSerialNode->appendChild($X509IssuerNameNode);

        $X509SerialNumberNode = new DOMElement('X509SerialNumber', $X509IssuerSerial);
        $X509IssuerSerialNode->appendChild($X509SerialNumberNode);

        $envelope = new DOMDocument();

        $envelope->loadXML(
    '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
                <soapenv:Body></soapenv:Body>
            </soapenv:Envelope>'
        );

        $envelope->encoding = 'UTF-8';
        $envelope->version = '1.0';
        $XMLRequestType = $XMLRequestDOMDoc->documentElement->localName;
        $XMLRequestTypeNode = $XMLRequestDOMDoc->getElementsByTagName($XMLRequestType)->item(0);
        $XMLRequestTypeNode = $envelope->importNode($XMLRequestTypeNode, true);

        $envelope->getElementsByTagName('Body')->item(0)->appendChild($XMLRequestTypeNode);
        return $envelope->saveXML();
    }

    /**
     * Sends SOAP request to Croatian fiscalization service.
     *
     * @param $payload
     * @return mixed
     * @throws Exception
     */
    public function sendSoap($payload)
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $this->url,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        switch ($this->security) {
            case 'SSL':
                break;
            case 'TLS':
                curl_setopt($ch, CURLOPT_SSLVERSION, 6);
                break;
            default:
                throw new \InvalidArgumentException('Third parameter in CroatianFiscalization constructor must be SSL or TLS!');
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        if ($response) return $this->parseResponse($response, $code);
        throw new Exception(curl_error($ch));
    }

    /**
     * Parses response if an error happens.
     *
     * @param $response
     * @param int $code
     * @return mixed
     * @throws Exception
     */
    public function parseResponse($response, $code = 4)
    {
        if ($code === 200) return $response;
        $DOMResponse = new DOMDocument();
        $DOMResponse->loadXML($response);

        $error_code = $DOMResponse->getElementsByTagName('SifraGreske')->item(0);
        $error_message = $DOMResponse->getElementsByTagName('PorukaGreske')->item(0);

        if ($error_code && $error_message) throw new Exception(sprintf('%s: %s', $error_code->nodeValue, $error_message->nodeValue));
        throw new Exception(print_r($response, true), $code);
    }
}
