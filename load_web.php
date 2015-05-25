<?php

require_once dirname(__FILE__).'/include.php';

if(!defined('APP_PATH') && isset($appPath) && $appPath)
    define('APP_PATH', rtrim($appPath, '/'));

if(!defined('APP_PATH'))
    throw new Exception("APP_PATH is not defined");

MFAutoloader::import(array(
    MF_PATH.'/i18n',
    MF_PATH.'/web',
    MF_PATH.'/web/auth',
    MF_PATH.'/db',
    MF_PATH.'/db/schema',
    MF_PATH.'/mail',
    APP_PATH.'/controllers',
    APP_PATH.'/models',
));

if(!isset($config) || !$config)
    $config = APP_PATH.'/config.php';

$app = new MFWebApp($config);
$app->run();
