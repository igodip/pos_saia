<?php

return [
    'default_currency' => env('INVENTORY_DEFAULT_CURRENCY', 'EUR'),
    'stock_valuation_method' => env('INVENTORY_STOCK_VALUATION_METHOD', 'last_cost'),
    'attachments' => [
        'disk' => env('INVENTORY_ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
        'max_upload_kb' => (int) env('INVENTORY_ATTACHMENTS_MAX_UPLOAD_KB', 5120),
        'allowed_mime_types' => [
            'application/pdf',
        ],
    ],
];
