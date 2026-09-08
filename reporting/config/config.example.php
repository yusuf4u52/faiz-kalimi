<?php

return [
    'jamaatonline' => [
        'base_url' => 'https://domainname',
        'username' => 'nuser',
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
        'from_name' => 'Reporting',
    ],
    'report' => [
        'org_name' => 'Jamaat',
        // Avoid the Rupee sign glyph (₹) — not reliably covered by Dompdf's bundled fonts.
        'currency_symbol' => 'Rs. ',
    ],
    // Gates index.php (the only reporting/ file served over HTTP — see
    // .htaccess). No separate login here: index.php reads the SAME PHP
    // session fmb/index.php's Google Sign-In already sets (same domain,
    // cookie path '/', so the session is already shared) and just checks
    // $_SESSION['email'] against this allowlist. Log in via /fmb/index.php
    // first if you're not already signed in there.
    'access' => [
        'allowed_emails' => [
            'yusuf4u52@gmail.com',
        ],
    ],
];
