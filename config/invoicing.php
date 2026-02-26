<?php

return [
    // Max retries when generating invoices
    'max_retries' => env('INVOICING_MAX_RETRIES', 3),

    // OSE provider config (mock)
    'ose' => [
        'provider' => env('OSE_PROVIDER', 'mock'),
    ],

    // Admin email for critical notifications
    'admin_email' => env('ADMIN_EMAIL', null),
];
