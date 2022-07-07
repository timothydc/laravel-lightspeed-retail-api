<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Services\Lightspeed;

use TimothyDC\LightspeedRetailApi\Resource;

class ResourceSale extends Resource
{
    public static string $resource = 'Sale';
    public string $primaryKey = 'saleID';

    public static string $timeStamp = "timeStamp";
    public static string $discountPercent = "discountPercent";
    public static string $completed = "completed";
    public static string $archived = "archived";
    public static string $voided = "voided";
    public static string $enablePromotions = "enablePromotions";
    public static string $isTaxInclusive = "isTaxInclusive";
    public static string $createTime = "createTime";
    public static string $updatetime = "updatetime";
    public static string $completeTime = "completeTime";
    public static string $referenceNumber = "referenceNumber";
    public static string $referenceNumberSource = "referenceNumberSource";
    public static string $tax1Rate = "tax1Rate";
    public static string $tax2Rate = "tax2Rate";
    public static string $change = "change";
    public static string $tipEnabled = "tipEnabled";
    public static string $receiptPreference = "receiptPreference";
    public static string $displayableSubtotal = "displayableSubtotal";
    public static string $ticketNumber = "ticketNumber";
    public static string $calcDiscount = "calcDiscount";
    public static string $calcTotal = "calcTotal";
    public static string $calcSubtotal = "calcSubtotal";
    public static string $calcTaxable = "calcTaxable";
    public static string $calcNonTaxable = "calcNonTaxable";
    public static string $calcAvgCost = "calcAvgCost";
    public static string $calcFIFOCost = "calcFIFOCost";
    public static string $calcTax1 = "calcTax1";
    public static string $calcTax2 = "calcTax2";
    public static string $calcPayments = "calcPayments";
    public static string $calcTips = "calcTips";
    public static string $total = "total";
    public static string $totalDue = "totalDue";
    public static string $displayableTotal = "displayableTotal";
    public static string $balance = "balance";
    public static string $customerID = "customerID";
    public static string $discountID = "discountID";
    public static string $employeeID = "employeeID";
    public static string $tipEmployeeID = "tipEmployeeID";
    public static string $quoteID = "quoteID";
    public static string $registerID = "registerID";
    public static string $shipToID = "shipToID";
    public static string $shopID = "shopID";
    public static string $taxCategoryID = "taxCategoryID";
    public static string $taxTotal = "taxTotal";
}
