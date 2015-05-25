<?php

abstract class MFService extends MFComponent
{
    
    public function __construct($config=array())
    {
        foreach($config as $key=>$value)
        {
            $this->$key = $value;
        }
        $this->init();
    }
    
    protected function init(){}
}