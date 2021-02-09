<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use tonikresic\CroatianFiscalization\Company\Certificate;
use tonikresic\CroatianFiscalization\Company\Company;
use tonikresic\CroatianFiscalization\CroatianFiscalization;

class CroatianFiscalizationTest extends TestCase
{
    /**
     * Test certificate path.
     *
     * @var string
     */
    public static string $certificatePath = 'CERTIFICATE_PATH';

    /**
     * Test certificate password.
     *
     * @var string
     */
    public static string $certificatePassword = 'CERTIFICATE_PASSWORD';

    /**
     * Company UID.
     *
     * @var string
     */
    public static string $companyUID = 'COMPANY_UID';

    /**
     * User UID.
     *
     * @var string
     */
    public static string $userUID = 'USER_UID';

    /**
     * Asserts that the fiscalization runs properly.
     *
     * @throws Exception
     */
    public function testFiscalization()
    {
        // Required. Add certificate path and password
        $certificate = new Certificate(self::$certificatePath, self::$certificatePassword);

        // Required. Add company UID
        $company = new Company(self::$companyUID, $certificate, true, true);

        // Required. Add user UID
        $userUID = self::$userUID;

        // Set fiscal abbreviation. G is for cash, K is for credit card.
        $fiscalAbbreviation = 'G';

        // Invoice gross
        $gross = 12.5;

        // Invoice net
        $net = 10;

        // Invoice number
        $number = 1;

        // Invoice business place label
        $businessPlaceLabel = 1;

        // Invoice cash register label
        $cashRegisterLabel = 1;

        // Set Croatian fiscalization data to null
        $fiscalData = [
            'zki' => NULL,
            'jir' => NULL
        ];

        $invoiceObject = (object) [
            'userUID' => $userUID,
            'fiscal_abbreviation' => $fiscalAbbreviation,
            'company' => $company,
            'gross' => $gross,
            'net' => $net,
            'number' => $number,
            'business_place_label' => $businessPlaceLabel,
            'cash_register_label' => $cashRegisterLabel,
            'fiscal_data' => (object) $fiscalData,
            'created_at' =>  date('Y-m-d H:i:s'),
            'fiscal_at' => NULL,
        ];

        $cf = new CroatianFiscalization();
        $cf->configure($company);

        $response = $cf->fiscalize($invoiceObject);

        fwrite(STDERR, print_r($response, TRUE));

        $this->assertEquals(true, $response->success);
    }
}