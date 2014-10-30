<?php

class MFAutoloader
{
    
    public static $_includePaths = array();
    
    public static function autoload($className)
    {
        // include class file relying on include_path
        if(strpos($className, '\\')===false)  // class without namespace
        {
            foreach(self::$_includePaths as $path)
            {
                $classFile = $path.'/'.$className.'.php';
                if(is_file($classFile))
                {
                    include($classFile);
                    break;
                }
            }
        }
        else  // class name with namespace in PHP 5.3
        {
            return false;
        }
        return class_exists($className, false) || interface_exists($className, false);
    }
    
    public static function import($paths)
    {
        if(is_string($paths))
            self::$_includePaths[] = $paths;
        else
        {
            foreach($paths as $path)
            {
                self::$_includePaths[] = $path;
            }
        }
    }
    
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
}
