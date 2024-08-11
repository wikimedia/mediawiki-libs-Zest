<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['directory_list'] = [ 'src' /*,'tests'*/ ];
$cfg['suppress_issue_types'] = [ 'UnusedPluginSuppression' ];

return $cfg;
