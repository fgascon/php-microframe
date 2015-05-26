<?php

defined('MF_PATH') || define('MF_PATH', dirname(__FILE__));
defined('MF_DEBUG') || define('MF_DEBUG', false);
defined('MF_ENABLE_EXCEPTION_HANDLER') || define('MF_ENABLE_EXCEPTION_HANDLER', true);
defined('MF_ENABLE_ERROR_HANDLER') || define('MF_ENABLE_ERROR_HANDLER', true);

if(!defined('APP_PATH'))
    throw new Exception("APP_PATH is not defined");

require_once MF_PATH.'/core/MFAutoloader.php';

MFAutoloader::import(array(
    MF_PATH.'/core',
    MF_PATH.'/core/helpers',
    MF_PATH.'/core/stateBackends',
    MF_PATH.'/i18n',
    MF_PATH.'/web',
    MF_PATH.'/web/auth',
    MF_PATH.'/db',
    MF_PATH.'/db/schema',
    MF_PATH.'/mail',
    APP_PATH.'/controllers',
    APP_PATH.'/models',
));
MFAutoloader::register();
