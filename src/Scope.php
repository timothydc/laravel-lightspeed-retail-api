<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi;

class Scope
{
    /** Grants full read and write access to the account. */
    const EMPLOYEE_ALL = 'employee:all';

    /** Create new sales and read sales history. */
    const EMPLOYEE_REGISTER = 'employee:register';

    /** Read sales history. */
    const EMPLOYEE_REGISTER_READ = 'employee:register_read';

    /** View, create, update, and archive items and inventory. */
    const EMPLOYEE_INVENTORY = 'employee:inventory';

    /** View items and inventory. */
    const EMPLOYEE_INVENTORY_READ = 'employee:inventory_read';

    /** View reports. */
    const EMPLOYEE_REPORTS = 'employee:reports';

    /** View, create, update, and archive administrative records. */
    const EMPLOYEE_ADMIN = 'employee:admin';

    /** View, create, update, and archive shops. */
    const EMPLOYEE_ADMIN_SHOPS = 'employee:admin_shops';

    /** View, create, update, and archive employees. */
    const EMPLOYEE_ADMIN_EMPLOYEES = 'employee:admin_employees';

    /** View, create, update, and archive payment types, discounts, and taxes. */
    const EMPLOYEE_ADMIN_PURCHASES = 'employee:admin_purchases';

    /** Void sales. */
    const EMPLOYEE_ADMIN_VOID_SALE = 'employee:admin_void_sale';

    /** View, create, update, and archive vendors and manufacturers. */
    const EMPLOYEE_ADMIN_INVENTORY = 'employee:admin_inventory';

    /** View, create, update, and archive work orders. */
    const EMPLOYEE_WORKBENCH = 'employee:workbench';

    /** View, create, update, and archive customers. */
    const EMPLOYEE_CUSTOMERS = 'employee:customers';

    /** View customer accounts. */
    const EMPLOYEE_CUSTOMERS_READ = 'employee:customers_read';

    /** View, create, update, and archive customer credit accounts. */
    const EMPLOYEE_CUSTOMERS_CREDIT_LIMIT = 'employee:customers_credit_limit';
}
