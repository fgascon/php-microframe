<?php

class MFApp
{
    
    private $_api;
    private $_httpRequest;
    private $_databases = array();
    
    public function __construct($config)
    {
        if(self::$app)
            throw new Exception("The MFApp is already created.");
        self::$app = $this;
        if(is_string($config))
            $config = require($config);
        if(!is_array($config))
            throw new Exception("Config missing");
        
        foreach($config as $name=>$value)
        {
            $this->$name = $value;
        }
    }
    
    public function getApi()
    {
        if(!$this->_api)
            $this->_api = new MFApi();
        return $this->_api;
    }
    
    public function getHttpRequest()
    {
        if(!$this->_httpRequest)
            $this->_httpRequest = new MFHttpRequest();
        return $this->_httpRequest;
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
        
    }
}