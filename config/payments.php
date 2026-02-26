<?php

return [
    // 'real_time' => transfer immediately to vendors after payment
    // 'scheduled' => admin will trigger transfers manually or via cron
    'transfer_mode' => env('PAYMENTS_TRANSFER_MODE', 'scheduled'),
    // number of retry attempts for automatic transfers
    'transfer_retries' => env('PAYMENTS_TRANSFER_RETRIES', 3),
    // default method to use for transfers in mock env
    'default_transfer_method' => env('PAYMENTS_TRANSFER_METHOD', 'mock')
];
