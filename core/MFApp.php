<?php

class MFApp extends MFComponent
{
    
    protected $_config;
    protected $_databases;
    protected $_servicesConfig;
    protected $_services = array();
    
    public function __construct($config)
    {
        MF::setApp($this);
        $this->initSystemHandlers();
        
        if(is_string($config))
            $config = require($config);
        if(!is_array($config))
            throw new Exception("Config missing");
        
        $this->_servicesConfig = isset($config['services']) ? $config['services'] : array();
        if(!isset($this->_servicesConfig['user']))
        $this->_servicesConfig = MFArrayUtil::merge(array(
            'logger'=>array(
                'class'=>'MFLogger',
            ),
            'redis'=>array(
                'class'=>'MFRedisConnection',
            ),
            'states'=>array(
                'class'=>'MFStateManager',
            ),
            'security'=>array(
                'class'=>'MFSecurity',
            ),
            'mailer'=>array(
                'class'=>'MFMailer',
            ),
        ), $this->_servicesConfig);
        
        unset($config['services']);
        $this->_config = $config;
        if(isset($config['include']))
        {
            MFAutoloader::import(array_map(function($path){
                return $path[0] === '/' ? $path : APP_PATH.'/'.$path;
            }, $config['include']));
        }
    }
    
    public function __get($name)
    {
        if(isset($this->_services[$name]) || isset($this->_servicesConfig[$name]))
            return $this->getService($name);
        else
            return parent::__get($name);
    }
    
    public function getService($name)
    {
        if(isset($this->_services[$name]))
            return $this->_services[$name];
        if(!isset($this->_servicesConfig[$name]))
            throw new Exception(MF::t('core', 'No service named "{name}".', array(
                '{name}'=>$name,
            )));
        $config = $this->_servicesConfig[$name];
        if(!isset($config['class']))
            throw new Exception(MF::t('core', 'Service "{name} has no class defined".', array(
                '{name}'=>$name,
            )));
        $className = $config['class'];
        unset($config['class']);
        $instance = new $className($config);
        $this->_services[$name] = $instance;
        return $instance;
    }
    
    public function getLogger()
    {
        return $this->getService('logger');
    }
    
    public function getRedis()
    {
        return $this->getService('redis');
    }
    
    public function getStates()
    {
        return $this->getService('states');
    }
    
    public function getSecurityManager()
    {
        return $this->getService('securityManager');
    }
    
    public function getDatabases()
    {
        if(!$this->_databases)
        {
            $config = $this->_config;
            $this->_databases = new MFDbConnectionCollection(isset($config['databases']) ? $config['databases'] : array());
        }
        return $this->_databases;
    }
    
    public function getDatabase($name='default')
    {
        return $this->getDatabases()->getConnection($name);
    }
    
    public function run()
    {
    }
    
    public function globalExceptionHandler($exception)
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
        //MF::log($message,CLogger::LEVEL_ERROR,$category);
        
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
    
    public function globalErrorHandler($code, $message, $file, $line)
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
            //MF::log($log,CLogger::LEVEL_ERROR,'php');
            
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
    
    protected function initSystemHandlers()
    {
        if(MF_ENABLE_EXCEPTION_HANDLER)
            set_exception_handler(array($this, 'globalExceptionHandler'));
        if(MF_ENABLE_ERROR_HANDLER)
            set_error_handler(array($this, 'globalErrorHandler'), error_reporting());
    }
    
    public function displayError($code, $message, $file, $line)
    {
        if(MF_DEBUG)
        {
            echo "PHP Error [$code]\n";
            echo "$message ($file:$line)\n";
            
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
        }
        else
        {
            echo "PHP Error [$code]\n";
            echo "$message\n";
        }
    }
    
    public function displayException($exception)
    {
        if(MF_DEBUG)
        {
            echo get_class($exception)."\n";
            echo $exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().")\n";
            echo $exception->getTraceAsString();
        }
        else
        {
            echo get_class($exception)."\n";
            echo $exception->getMessage()."\n";
        }
    }
}