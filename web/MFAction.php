<?php

class MFAction
{
    
    private $_id;
    private $_controller;
    
    public function __construct($controller, $id)
    {
        $this->_controller = $controller;
        $this->_id = $id;
    }
    
    public function getController()
    {
        return $this->_controller;
    }
    
    public function getId()
    {
        return $this->_id;
    }
    
    public function run()
    {
        $methodName = 'action'.$this->getId();
        return $this->getController()->$methodName();
    }
    
    public function runWithParams($params)
    {
        $methodName = 'action'.$this->getId();
        $controller = $this->getController();
        $method = new ReflectionMethod($controller, $methodName);
        if($method->getNumberOfParameters() > 0)
            return $this->runWithParamsInternal($controller, $method, $params);
        else
            return $controller->$methodName();
    }
    
    protected function runWithParamsInternal($object, $method, $params)
    {
        $ps = array();
        foreach($method->getParameters() as $i=>$param)
        {
            $name = $param->getName();
            if(isset($params[$name]))
            {
                if($param->isArray())
                    $ps[] = is_array($params[$name]) ? $params[$name] : array($params[$name]);
                elseif(!is_array($params[$name]))
                    $ps[] = $params[$name];
                else
                    return false;
            }
            elseif($param->isDefaultValueAvailable())
                $ps[] = $param->getDefaultValue();
            else
                return false;
        }
        return $method->invokeArgs($object, $ps);
    }
}