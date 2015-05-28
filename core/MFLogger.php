<?php

use Monolog\Logger;

class MFLogger extends MFService
{
    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    const ALERT = 550;
    const EMERGENCY = 600;
    
    private $_channelsConfig = array();
    private $_channels = array();
    private $_aliases = array();
    
    public function getAliases()
    {
        return $this->_aliases;
    }
    
    public function setAliases($aliases)
    {
        $this->_aliases = $aliases;
    }
    
    public function setChannels($channels)
    {
        $this->_channelsConfig = $channels;
    }
    
    public function getChannels()
    {
        $channels = array();
        foreach($this->_channelsConfig as $name=>$config)
        {
            $channels[$name] = $this->getChannel($name);
        }
        return $channels;
    }
    
    public function getChannel($name='application')
    {
        $name = $this->resolveAlias($name);
        
        if(isset($this->_channels[$name]))
            return $this->_channels[$name];
        
        $config = isset($this->_channelsConfig[$name]) ? $this->_channelsConfig[$name] : array();
        $handlers = isset($config['handlers']) ? $config['handlers'] : array();
        $processors = isset($config['processors']) ? $config['processors'] : array();
        $logger = new Logger($name, $handlers, $processors);
        
        $this->_channels[$name] = $logger;
        return $logger;
    }
    
    private function resolveAlias($name)
    {
        $aliases = $this->_aliases;
        foreach($aliases as $alias=>$resolveTo)
        {
            if(strpos($name, $alias) === 0)
                return $resolveTo;
        }
        return $name;
    }
}