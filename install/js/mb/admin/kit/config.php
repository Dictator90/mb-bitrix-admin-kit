<?php
defined('B_PROLOG_INCLUDED') || die();

return [
    'js' => [
        'vendor/chart.js',
        'dist/kit.bundle.js',
        'src/relation-tilegrid.js',
    ],
    'css' => [
        'dist/kit.bundle.css'
    ],
    'rel' => [
		'main.core.collections',
		'main.core.events',
		'main.loader',
		'ui.entity-selector',
		'main.core',
	],
	'skip_core' => false,
];
