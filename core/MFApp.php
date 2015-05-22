<?php

class MFApp
{
    
    private $_config;
    private $_controller;
    private $_httpRequest;
    private $_urlManager;
    private $_databases = array();
    
    public function __construct($config)
    {
        $this->initSystemHandlers();
        if(is_string($config))
            $config = require($config);
        if(!is_array($config))
            throw new Exception("Config missing");
        $this->_config = $config;
    }
    
    public function getController()
    {
        return $this->_controller;
    }
    
    public function getHttpRequest()
    {
        if(!$this->_httpRequest)
            $this->_httpRequest = new MFHttpRequest();
        return $this->_httpRequest;
    }
    
    public function getUrlManager()
    {
        if(!$this->_urlManager)
        {
            $config = $this->_config;
            $this->_urlManager = new MFUrlManager(isset($config['url']) ? $config['url'] : array());
        }
        return $this->_urlManager;
    }
    
    public function getDatabase($name='default')
    {
        if(isset($this->_databases[$name]))
            return $this->_databases[$name];
        else
            throw new Exception("No database named '$name' configured");
    }
    
    public function setDatabase($config)
    {
        $this->setDatabases(array(
            'default'=>$config,
        ));
    }
    
    public function setDatabases($databasesConfig)
    {
        $databases = array();
        foreach($databasesConfig as $name=>$config)
        {
            $databases[$name] = new MFDatabase($config);
        }
    }
    
    public function run()
    {
        $route = $this->getUrlManager()->detectRoute($this->getHttpRequest());
        $this->runController($route);
    }
    
    public function runController($route)
    {
        if(($ca = $this->createController($route)) !== null)
        {
            list($controller , $actionID) = $ca;
            $oldController = $this->_controller;
            $this->_controller = $controller;
            //$controller->init();
            $controller->run($actionID);
            $this->_controller = $oldController;
        }
        else
            throw new MFHttpException(404, MF::t('core','Unable to resolve the request "{route}".',
                array('{route}'=>$route)));
    }
    
    public function createController($route)
    {
        $routeParts = explode('/', $route);
        $partsCount = count($routeParts);
        $controllerID = null;
        $actionID = 'index';
        if($partsCount === 1)
        {
            $controllerID = $routeParts[0];
        }
        else if($partsCount === 2)
        {
            $controllerID = $routeParts[0];
            $actionID = $routeParts[1];
        }
        else
        {
            return null;
        }
        if(!$controllerID)
            return null;
        
        $className = ucfirst($controllerID).'Controller';
        
        $controller = new $className($controllerID);
        if($controller instanceof MFController)
        {
            return array($controller, $actionID);
        }
        return null;
    }
    
    public function handleException($exception)
    {
        // disable error capturing to avoid recursive errors
        restore_error_handler();
        restore_exception_handler();
        
        $category = 'exception.'.get_class($exception);
        if($exception instanceof MFHttpException)
            $category .= '.'.$exception->statusCode;
        // php <5.2 doesn't support string conversion auto-magically
        $message = $exception->__toString();
        if(isset($_SERVER['REQUEST_URI']))
            $message .= "\nREQUEST_URI=".$_SERVER['REQUEST_URI'];
        if(isset($_SERVER['HTTP_REFERER']))
            $message .= "\nHTTP_REFERER=".$_SERVER['HTTP_REFERER'];
        $message .= "\n---";
        //Yii::log($message,CLogger::LEVEL_ERROR,$category);
        
        try
        {
            // try an error handler
            //if(($handler = $this->getErrorHandler()) !== null)
            //	$handler->handle($event);
            //else
            $this->displayException($exception);
        }
        catch(Exception $e)
        {
            $this->displayException($e);
        }
        
        /*try
         {
        $this->end(1);
        }
        catch(Exception $e)
        {
        // use the most primitive way to log error
        $msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
        $msg .= $e->getTraceAsString()."\n";
        $msg .= "Previous exception:\n";
        $msg .= get_class($exception).': '.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().")\n";
        $msg .= $exception->getTraceAsString()."\n";
        $msg .= '$_SERVER='.var_export($_SERVER,true);
        error_log($msg);
        exit(1);
        }*/
        exit(1);
    }
    
    public function handleError($code, $message, $file, $line)
    {
        if($code & error_reporting())
        {
            // disable error capturing to avoid recursive errors
            restore_error_handler();
            restore_exception_handler();
            
            $log = "$message ($file:$line)\nStack trace:\n";
            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if(count($trace) > 3)
                $trace = array_slice($trace, 3);
            foreach($trace as $i=>$t)
            {
                if(!isset($t['file']))
                    $t['file'] = 'unknown';
                if(!isset($t['line']))
                    $t['line'] = 0;
                if(!isset($t['function']))
                    $t['function'] = 'unknown';
                $log .= "#$i {$t['file']}({$t['line']}): ";
                if(isset($t['object']) && is_object($t['object']))
                    $log .= get_class($t['object']).'->';
                $log .= "{$t['function']}()\n";
            }
            if(isset($_SERVER['REQUEST_URI']))
                $log .= 'REQUEST_URI='.$_SERVER['REQUEST_URI'];
            //Yii::log($log,CLogger::LEVEL_ERROR,'php');
            
            try
            {
                // try an error handler
                //if(($handler=$this->getErrorHandler())!==null)
                //	$handler->handle($event);
                //else
                $this->displayError($code,$message,$file,$line);
            }
            catch(Exception $e)
            {
                $this->displayException($e);
            }
            
            /*try
             {
            $this->end(1);
            }
            catch(Exception $e)
            {
            // use the most primitive way to log error
            $msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
            $msg .= $e->getTraceAsString()."\n";
            $msg .= "Previous error:\n";
            $msg .= $log."\n";
            $msg .= '$_SERVER='.var_export($_SERVER,true);
            error_log($msg);
            exit(1);
            }*/
            exit(1);
        }
    }
    
    public function displayError($code, $message, $file, $line)
    {
        if(MF_DEBUG)
        {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message ($file:$line)</p>\n";
            echo '<pre>';
            
            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if(count($trace) > 3)
                $trace = array_slice($trace,3);
            foreach($trace as $i=>$t)
            {
                if(!isset($t['file']))
                    $t['file']='unknown';
                if(!isset($t['line']))
                    $t['line']=0;
                if(!isset($t['function']))
                    $t['function']='unknown';
                echo "#$i {$t['file']}({$t['line']}): ";
                if(isset($t['object']) && is_object($t['object']))
                    echo get_class($t['object']).'->';
                echo "{$t['function']}()\n";
            }
            
            echo '</pre>';
        }
        else
        {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message</p>\n";
        }
    }
    
    public function displayException($exception)
    {
        if(MF_DEBUG)
        {
            echo '<h1>'.get_class($exception)."</h1>\n";
            echo '<p>'.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().')</p>';
            echo '<pre>'.$exception->getTraceAsString().'</pre>';
        }
        else
        {
            echo '<h1>'.get_class($exception)."</h1>\n";
            echo '<p>'.$exception->getMessage().'</p>';
        }
    }
    
    protected function initSystemHandlers()
    {
        if(MF_ENABLE_EXCEPTION_HANDLER)
            set_exception_handler(array($this, 'handleException'));
        if(MF_ENABLE_ERROR_HANDLER)
            set_error_handler(array($this, 'handleError'), error_reporting());
    }
}