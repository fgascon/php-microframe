<?php

class MF
{
    
    private static $app = null;
    
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