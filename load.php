<?php

defined('MICROFRAME_PATH') || define('MICROFRAME_PATH', dirname(__FILE__));

if(defined('APP_PATH') && isset($appPath) && $appPath)
    define('APP_PATH', ltrim($appPath, '/'));

if(!defined('APP_PATH'))
    throw new Exception("APP_PATH is not defined");

require_once MICROFRAME_PATH.'/core/MFAutoloader.php';
MFAutoloader::import(array(
    MICROFRAME_PATH.'/core',
    MICROFRAME_PATH.'/db',
    APP_PATH.'/controllers',
    APP_PATH.'/models',
));
MFAutoloader::register();

if(!isset($config) || !$config)
    $config = APP_PATH.'/config.php';

MF::createApp($configPath)->run();