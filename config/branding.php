<?php

return [
    // Keep this in sync with resources/branding/icon-config.json's
    // "backgroundColors" map — the Vite icon generator and this file must
    // resolve the same background color for the same environment, or the
    // favicon and the web app manifest's theme_color will visibly disagree.
    'colors' => [
        'local' => '#CC0000',
        'development' => '#CC0000',
        'staging' => '#0077CC',
        'production' => '#FF6200',
    ],

    'default_color' => '#FF6200',
];
