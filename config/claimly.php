<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employee used by the claim desk
    |--------------------------------------------------------------------------
    |
    | The seeder creates this account as the first admin.
    |
    */

    'employee_email' => env('CLAIMLY_EMPLOYEE_EMAIL', 'aeris@claimly.test'),

    /*
    |--------------------------------------------------------------------------
    | Employee purchase policy
    |--------------------------------------------------------------------------
    |
    | Each employee may buy up to this many pieces of each SKU per calendar
    | year (WIB). Every product has its own quota. It resets on 1 January.
    |
    */

    'yearly_limit' => (int) env('CLAIM_YEARLY_LIMIT', 5),

    'warehouse_address' => env('CLAIM_WAREHOUSE_ADDRESS', 'Gudang pusat, ambil di jam kerja (09.00–16.00)'),

    'default_delivery_address' => env('CLAIM_DEFAULT_DELIVERY_ADDRESS', 'Kantor'),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Employees receive email and in-app notifications. The ops team (admins)
    | receives in-app notifications, optional email, and a Lark group message
    | when LARK_WEBHOOK_URL is set in config/services.php.
    |
    */

    'notify_mail' => (bool) env('CLAIM_NOTIFY_MAIL', true),

    'notify_ops_mail' => (bool) env('CLAIM_NOTIFY_OPS_MAIL', true),

];
