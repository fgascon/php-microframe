<?php

class MFStateManager extends MFService
{
    private static $typesMap = array(
        'file'=>'MFStateBackendFile',
        'redis'=>'MFStateBackendRedis',
        'db'=>'MFStateBackendDb',
    );
    
    private $_backend;
    
    public $type = 'file';
    public $options = array();
    
    public function init()
    {
        $type = $this->type;
        $backendClass = isset(self::$typesMap[$type]) ? self::$typesMap[$type] : $type;
        $this->_backend = new $backendClass($this->options);
    }
    
    public function get($name)
    {
        return $this->_backend->get($name);
    }
    
    public function set($name, $value)
    {
        $this->_backend->set($name, $value);
    }
}