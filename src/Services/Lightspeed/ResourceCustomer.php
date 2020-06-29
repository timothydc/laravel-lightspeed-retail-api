<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Resource;

class ResourceCustomer extends Resource
{
    public static string $resource = 'Customer';
    public string $primaryKey = 'customerID';

    public static string $firstName = 'firstName';
    public static string $lastName = 'lastName';
    public static string $birthday = 'dob';
    public static string $archived = 'archived';
    public static string $title = 'title';
    public static string $company = 'company';
    public static string $companyRegistrationNumber = 'companyRegistrationNumber';
    public static string $vat = 'vatNumber';

    public static string $address1 = 'address1';
    public static string $address2 = 'address2';
    public static string $city = 'city';
    public static string $state = 'state';
    public static string $stateCode = 'stateCode';
    public static string $zip = 'zip';
    public static string $country = 'country';
    public static string $countryCode = 'countryCode';

    public static string $note = 'note';
    public static string $isNotePublic = 'isPublic';
}
