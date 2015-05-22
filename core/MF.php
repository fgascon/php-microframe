<?php

class MF
{
    
    private static $app = null;
    
    public static function setApp($app)
    {
        if(self::$app)
            throw new Exception("The app is already loaded.");
        self::$app = $app;
    }
    
    public static function app()
    {
        if(!self::$app)
            throw new Exception("The app is not created yet.");
        return self::$app;
    }
    
    public static function t($category, $message, $params=array())
    {
        return str_replace(array_keys($params), array_values($params), $message);
    }
}