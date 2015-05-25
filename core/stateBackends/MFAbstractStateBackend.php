<?php

abstract class MFAbstractStateBackend
{
    
    public function __construct($config)
    {
        foreach($config as $key=>$value)
        {
            $this->$key = $value;
        }
        $this->init();
    }
    
    protected function init(){}
    
    abstract public function get($name);
    
    abstract public function set($name, $value);
}