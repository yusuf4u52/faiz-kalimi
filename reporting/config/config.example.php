<?php

return [
    'jamaatonline' => [
        'base_url' => 'https://punekalimi.jamaatonline.in',
        'username' => 'cronuser',
        'password' => 'CHANGE_ME',
    ],
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        // 'tls', 'ssl', or '' for an unencrypted connection.
        'encryption' => 'tls',
        'username' => 'reports@example.com',
        'password' => 'CHANGE_ME',
        'from_email' => 'reports@example.com',
        'from_name' => 'Punekalimi Jamaat',
    ],
    'report' => [
        'org_name' => 'Punekalimi Jamaat',
        // Avoid the Rupee sign glyph (₹) — not reliably covered by Dompdf's bundled fonts.
        'currency_symbol' => 'Rs. ',
    ],
];
