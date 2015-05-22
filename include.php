<?php

defined('MF_PATH') || define('MF_PATH', dirname(__FILE__));
defined('MF_DEBUG') || define('MF_DEBUG', false);
defined('MF_ENABLE_EXCEPTION_HANDLER') || define('MF_ENABLE_EXCEPTION_HANDLER', true);
defined('MF_ENABLE_ERROR_HANDLER') || define('MF_ENABLE_ERROR_HANDLER', true);

require_once MF_PATH.'/core/MFAutoloader.php';
MFAutoloader::register();
MFAutoloader::import(array(
    MF_PATH.'/core',
    MF_PATH.'/core/helpers',
));
