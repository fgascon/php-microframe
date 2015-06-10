<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class MFWebApp extends MFApp
{
    
    private $_controller;
    private $_request;
    private $_routes;
    private $_urlMatcher;
    
    public function __construct($config)
    {
        parent::__construct($config);
        $this->_servicesConfig = MFArrayUtil::merge(array(
            'session'=>array(
                'class'=>'MFSessionManager',
            ),
            'user'=>array(
                'class'=>'MFWebUser',
            ),
        ), $this->_servicesConfig);
    }
    
    public function getController()
    {
        return $this->_controller;
    }
    
    public function getRequest()
    {
        if(!$this->_request)
            $this->_request = Request::createFromGlobals();
        return $this->_request;
    }
    
    public function getRoutes()
    {
        if(!$this->_routes)
        {
            $config = $this->_config;
            $urls = isset($config['urls']) ? $config['urls'] : array();
            $routes = new RouteCollection();
            foreach($urls as $pattern=>$routeConfig)
            {
                $route = new Route($pattern);
                if(is_string($routeConfig))
                    $routes->add($routeConfig, $route);
                else if(is_array($routeConfig) && isset($routeConfig[0]))
                {
                    $params = isset($routeConfig['defaultParams']);
                    $routeName = $routeConfig[0];
                    unset($routeConfig[0]);
                    foreach($routeConfig as $key=>$value)
                    {
                        $route->$key = $name;
                    }
                    $routes->add($routeName, $route);
                }
                else
                    throw new Exception(MF::t('core','Invalid route configuration for "{pattern}".',array(
                        '{pattern}'=>"$pattern",
                    )));
            }
            $this->_routes = $routes;
        }
        return $this->_routes;
    }
    
    public function getUrlMatcher()
    {
        if(!$this->_urlMatcher)
        {
            $context = new RequestContext();
            $context->fromRequest($this->getRequest());
            $this->_urlMatcher = new UrlMatcher($this->getRoutes(), $context);
        }
        return $this->_urlMatcher;
    }
    
    protected function resolveRoute()
    {
        $urlMatcher = $this->getUrlMatcher();
        try
        {
            $params = $urlMatcher->matchRequest($this->getRequest());
            $route = $params['_route'];
            unset($params['_route']);
            $replaces = array();
            foreach($params as $key=>$value)
            {
                $replaces['{'.$key.'}'] = $value;
            }
            $route = strtr($route, $replaces);
            foreach($params as $key=>$value)
            {
                $_GET[$key] = $value;
                if(!isset($_POST[$key]))
                    $_REQUEST[$key] = $value;
            }
            return $route;
        }
        catch(Routing\Exception\ResourceNotFoundException $exception)
        {
            throw new MFHttpException(404, "Not Found");
        }
    }
    
    public function run()
    {
        try
        {
            $route = $this->resolveRoute();
            $response = $this->runController($route);
        }
        catch(Exception $exception)
        {
            $response = $this->handleException($exception);
        }
        $response->send();
    }
    
    public function runController($route)
    {
        if(($ca = $this->createController($route)) !== null)
        {
            list($controller , $actionID) = $ca;
            $oldController = $this->_controller;
            $this->_controller = $controller;
            $controller->init();
            $response = $controller->run($actionID);
            $this->_controller = $oldController;
            return $response;
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
    
    protected function handleException($exception)
    {
        if($exception instanceof MFHttpException)
            return new Response($exception->getMessage(), $exception->statusCode);
        throw $exception;
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