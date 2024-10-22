<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

class Scope
{
    /**    Grants full read and write access to the account. This grant will automatically assume all access rights that the authorizing user possesses */
    const EMPLOYEE_ALL = 'employee:all';
    /**    View, create, update, and archive administrative records. */
    const EMPLOYEE_ADMIN = 'employee:admin';
    /**    View, create, update, and archive employees. */
    const EMPLOYEE_ADMIN_EMPLOYEES = 'employee:admin_employees';
    /**    View, create, update, and archive vendors and manufacturers. */
    const EMPLOYEE_ADMIN_INVENTORY = 'employee:admin_inventory';
    /**    View, create, update, and archive payment types, discounts, and taxes. */
    const EMPLOYEE_ADMIN_PURCHASES = 'employee:admin_purchases';
    /**    View, create, update, and archive shops. */
    const EMPLOYEE_ADMIN_SHOPS = 'employee:admin_shops';
    /**    Void sales. */
    const EMPLOYEE_ADMIN_VOID_SALE = 'employee:admin_void_sale';
    /**    View, create, update, and archive product categories. */
    const EMPLOYEE_CATEGORIES = 'employee:categories';
    /**    View, create, update, and archive customers. */
    const EMPLOYEE_CUSTOMERS = 'employee:customers';
    /**    View customer accounts. */
    const EMPLOYEE_CUSTOMERS_READ = 'employee:customers_read';
    /**    View the “code” field unmasked in the response payloads of the credit account endpoints. */
    const EMPLOYEE_CUSTOMERS_VIEW_GIFT_CARD_NUMBERS = 'employee:customers_view_gift_card_numbers';
    /**    View, create, update, and archive items and inventory. */
    const EMPLOYEE_INVENTORY = 'employee:inventory';
    /**    View, create, update inventory counts. */
    const EMPLOYEE_INVENTORY_COUNTS = 'employee:inventory_counts';
    /**    Reconcile inventory counts. */
    const EMPLOYEE_INVENTORY_COUNTS_RECONCILE = 'employee:inventory_counts_reconcile';
    /**    View items and inventory. */
    const EMPLOYEE_INVENTORY_READ = 'employee:inventory_read';
    /**    View, create, update, and archive manufacturers. */
    const EMPLOYEE_MANUFACTURERS = 'employee:manufacturers';
    /**    View and edit product cost values */
    const EMPLOYEE_PRODUCT_COST = 'employee:product_cost';
    /**    create, update, and archive products */
    const EMPLOYEE_PRODUCT_EDIT = 'employee:product_edit';
    /**    View, create, and update purchase orders */
    const EMPLOYEE_PURCHASE_ORDERS = 'employee:purchase_orders';
    /**    Create new sales and read sales history. */
    const EMPLOYEE_REGISTER = 'employee:register';
    /**    Create layaway sales. */
    const EMPLOYEE_REGISTER_LAYAWAY = 'employee:register_layaway';
    /**    Read sales history. */
    const EMPLOYEE_REGISTER_READ = 'employee:register_read';
    /**    Create refund sales. */
    const EMPLOYEE_REGISTER_REFUND = 'employee:register_refund';
    /**    View reports. */
    const EMPLOYEE_REPORTS = 'employee:reports';
    /**    View, createm and update special orders. */
    const EMPLOYEE_SPECIAL_ORDERS = 'employee:special_orders';
    /**    View, create, update, and archive product tags. */
    const EMPLOYEE_TAGS = 'employee:tags';
    /**    View, create, update, and archive transfers. */
    const EMPLOYEE_TRANSFERS = 'employee:transfers';
    /**    View, create, update and archive vendors. */
    const EMPLOYEE_VENDORS = 'employee:vendors';
    /**    View vendor returns. */
    const EMPLOYEE_VENDOR_RETURNS = 'employee:vendor_returns';
    /**    View, create, update, and archive work orders. */
    const EMPLOYEE_WORKBENCH = 'employee:workbench';
}
