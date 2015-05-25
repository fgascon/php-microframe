<?php

class MFStateBackendRedis extends MFAbstractStateBackend
{
    
    public $prefix = 'MF.state.';
    
    public function get($name)
    {
        return MF::app()->redis->get($this->prefix.$name);
    }
    
    public function set($name, $value)
    {
        return MF::app()->redis->set($this->prefix.$name, $value);
    }
}