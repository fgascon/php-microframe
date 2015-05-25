<?php

class MFRedisConnection extends MFService
{
    
    private $_redis;
    
    public $persistent = true;
    public $host = 'localhost';
    public $port = 6379;
    public $socket;
    public $password;
    public $timeout = 0;
    public $retryInterval;
    public $database;
    
    protected function init()
    {
        $redis = new Redis();
        $connectionMethod = $this->persistent ? 'pconnect' : 'connect';
        $connectionArgs = array();
        if($this->socket)
            $connectionArgs[] = $this->socket;
        else
        {
            $connectionArgs[] = $this->host;
            $connectionArgs[] = $this->port;
        }
        $connectionArgs[] = $this->timeout;
        $connectionArgs[] = null;
        if($this->retryInterval)
            $connectionArgs[] = $this->retryInterval;
        $result = call_user_func_array(array($redis, $connectionMethod), $connectionArgs);
        if($this->password)
            $redis->auth($this->password);
        if($this->database)
            $redis->select($this->database);
        $this->_redis = $redis;
    }
    
    public function __call($name, $parameters)
    {
        $redis = $this->_redis;
        if(method_exists($redis, $name))
            return call_user_func_array(array($redis, $name), $parameters);
        else
            return parent::__call($name, $parameters);
    }
}