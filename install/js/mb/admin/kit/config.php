<?php
defined('B_PROLOG_INCLUDED') || die();

return [
    'js' => [
        'dist/kit.bundle.js'
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
