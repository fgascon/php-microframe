<?php

class MFDbConnectionCollection
{
    
    private $_configs;
    private $_connections;
    
    public function __construct($configs)
    {
        $this->_configs = $configs;
        $this->_connections = array();
    }
    
    public function getConnection($name='default')
    {
        if(isset($this->_connections[$name]))
            return $this->_connections[$name];
        if(!isset($this->_configs[$name]))
            throw new Exception(MF::t('core', 'No database connection named "{db}".', array(
                '{db}'=>$name,
            )));
        
        $config = $this->_configs[$name];
        $connection = new MFDbConnection();
        foreach($config as $key=>$value)
            $connection->$key = $value;
        $this->_connections[$name] = $connection;
        return $connection;
    }
}