<?php

class MFMail extends MFComponent
{
    
    private $_message;
    
    public function __construct($config=null)
    {
        $this->_message = Swift_Message::newInstance();
        if(is_array($config))
        {
            foreach($config as $key=>$value)
            {
                $this->$key = $value;
            }
        }
    }
    
    public function getMessage()
    {
        return $this->_message;
    }
    
    public function __get($name)
    {
        $getter = 'get'.$name;
        $message = $this->_message;
        if(method_exists($message, $getter))
            return $message->$getter();
        else
            return parent::__get($name);
    }
    
    public function __set($name, $value)
    {
        $setter = 'set'.$name;
        $message = $this->_message;
        if(method_exists($message, $setter))
            return $message->$setter($value);
        else
            return parent::__set($name, $value);
    }
    
    public function __call($name, $parameters)
    {
        $message = $this->_message;
        if(method_exists($message, $name))
            return call_user_func_array(array($message, $name), $parameters);
        else
            return parent::__call($name, $parameters);
    }
    
    public function renderView($view, $data=array(), $contentType='text/html')
    {
        $viewPath = APP_PATH.'/views/mail/'.$view.'.php';
        $body = $this->renderViewInternal($viewPath, $data);
        $this->setBody($body, $contentType);
    }
    
    private function renderViewInternal($__viewPath__, $__data__)
    {
        extract($__data__, EXTR_SKIP);
        ob_start();
        require($__viewPath__);
        return ob_get_clean();
    }
    
    public function send()
    {
        return MF::app()->mailer->send($this->_message);
    }
}