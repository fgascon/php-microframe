<?php

defined('MICROFRAME_PATH') || define('MICROFRAME_PATH', dirname(__FILE__));

class MF
{
    
    private static $app = null;
    
    public static function init($appPath, $config=null)
    {
        if(self::$app)
            throw new Exception("MicroFrame is already initialised");
        if(!$config)
            $config = $appPath.'/config.php';
        if(is_string($config))
            $config = require($config);
        $app = new MFApp($config);
        self::$app = $app;
        return $app;
    }
    
    public static function createApp($config)
    {
        if(self::$app)
            throw new Exception("The app is already created.");
        $app = new MFApp($config);
        self::$app = $app;
        return $app;
    }
    
    public static function app()
    {
        if(!self::$app)
            throw new Exception("The app is not created yet.");
        return self::$app;
    }
}
