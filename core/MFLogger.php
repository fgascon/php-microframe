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
    
    private $_handlers = array();
    private $_processors = array();
    private $_logger;
    
    public $channel = 'microframe';
    
    public function init()
    {
        $this->_logger = new Logger($this->channel, $this->_handlers, $this->_processors);
    }
    
    public function setHandlers(array $handlers)
    {
        $this->_handlers = $handlers;
    }
    
    public function log($level, $message, array $context=array())
    {
        $this->_logger->log($level, $message, $context);
    }
}