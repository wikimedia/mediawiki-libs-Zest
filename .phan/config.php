<?php
declare( strict_types = 1 );

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['directory_list'] = [ 'src', 'tests' ];
$cfg['suppress_issue_types'] = [ 'UnusedPluginSuppression' ];

foreach ( [
	'vendor/phpunit/phpunit',
	'vendor/wikimedia/remex-html',
	'vendor/wikimedia/testing-access-wrapper',
	'vendor/wikimedia/zest-jq',
] as $dir ) {
	$cfg['directory_list'][] = $dir;
	$cfg['exclude_analysis_directory_list'][] = $dir;
}

return $cfg;
