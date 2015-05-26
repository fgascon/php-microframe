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
    
    public static function trace($msg, $category='application')
    {
        self::log($msg, 'debug', $category);
    }
    
	public static function log($msg, $level='info', $category='application')
	{
		self::$app->getLogger()->log($level, $msg);
	}
}