<?php

class MFApi
{
    
    private function loadController($controllerName)
    {
        $className = ucfirst($controllerName).'Controller';
        include_once($this->controllersPath.'/'.$className.'.php');
        if(class_exists($className))
        {
            return new $className();
        }
        else
            $this->displayError(404, "Not found");
    }
    
    public function displayResult($result)
    {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }
    
    public function displayError($code, $message)
    {
        $this->displayResult(array(
            'error'=>$message,
            'code'=>$code,
        ));
    }
}