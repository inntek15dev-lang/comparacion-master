<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logging Enabled
    |--------------------------------------------------------------------------
    |
    | This value determines if the high-performance audit logging system is 
    | enabled. You can toggle this value in your .env file.
    |
    */
    'enabled' => env('AUDIT_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Admin Email for Reports
    |--------------------------------------------------------------------------
    |
    | The email address where the daily Excel log export will be sent.
    |
    */
    'admin_email' => env('AUDIT_LOG_EMAIL', 'admin@example.com'),
];
