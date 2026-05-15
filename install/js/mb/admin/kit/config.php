<?php
defined('B_PROLOG_INCLUDED') || die();

return [
    'js' => 'dist/kit.bundle.js',
    'css' => 'dist/kit.bundle.css',
    'rel' => [
		'main.polyfill.core',
	],
    'skip_core' => true,
];
