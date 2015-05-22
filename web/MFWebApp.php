<?php

class MFWebApp extends MFApp
{
    
    private $_controller;
    private $_httpRequest;
    private $_urlManager;
    
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
            $this->_urlManager = new MFUrlManager(isset($config['urls']) ? $config['urls'] : array());
        }
        return $this->_urlManager;
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
}