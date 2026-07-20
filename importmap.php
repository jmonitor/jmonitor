<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'toast.js' => [
        'path' => './assets/js/Toast.js',
    ],
    'toast.css' => [
        'path' => './css/toast.css',
        'type' => 'css',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    'bootstrap' => [
        'version' => '5.3.5',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.5',
        'type' => 'css',
    ],
    'chart.js' => [
        'version' => '4.4.8',
    ],
    'chartjs-plugin-annotation' => [
        'version' => '3.1.0',
    ],
    'chart.js/helpers' => [
        'version' => '4.4.8',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    'toastify-js' => [
        'version' => '1.12.0',
    ],
    'moment' => [
        'version' => '2.30.1',
    ],
    'chartjs-adapter-moment' => [
        'version' => '1.0.1',
    ],
    'js-cookie' => [
        'version' => '3.0.5',
    ],
    '@stimulus-components/clipboard' => [
        'version' => '5.0.0',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    'date-fns' => [
        'version' => '4.1.0',
    ],
    'date-fns/locale' => [
        'version' => '4.1.0',
    ],
];
