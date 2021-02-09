# Croatian Fiscalization

[![](https://img.shields.io/badge/version-1.0.2-blue.svg)](https://shields.io/)
[![](https://img.shields.io/badge/maintained-yes-green.svg)](https://shields.io/)


PHP package for Croatian fiscalization. Provides basic service for fiscalizing the invoices with Croatian tax PDV (25%).

## Install the required package

- `composer require tonikresic/croatian-fiscalization`


## Example usage

You need to have a demo certificate with its password, the company UID and the user UID in order for fiscalization to work.


```php
$userUID = 'USER_UID';
$companyUID = 'COMPANY_UID';
$certificatePath = 'CERTIFICATE_PATH';
$certificatePassword = 'CERTIFICATE_PASSWORD';

$cf = new tonikresic\CroatianFiscalization\CroatianFiscalization();

$certificate = new tonikresic\CroatianFiscalization\Company\Certificate($certificatePath, $certificatePassword);
$company = new tonikresic\CroatianFiscalization\Company\Company($companyUID, $certificate, true, true);

$cf->configure($company);

$invoiceObject = (object) [
    'userUID' => $userUID,
    'fiscal_abbreviation' => 'G',
    'company' => $company,
    'gross' => 12.5,
    'net' => 10,
    'number' => 1,
    'business_place_label' => 1,
    'cash_register_label' => 1,
    'fiscal_data' => (object) [
        'zki' => NULL,
        'jir' => NULL
    ],
    'created_at' =>  date('Y-m-d H:i:s'),
    'fiscal_at' => NULL,
];

$response = $cf->fiscalize($invoiceObject);
```

## Running the tests

- `php vendor/phpunit/phpunit/phpunit`