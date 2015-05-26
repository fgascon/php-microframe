<?php

class MFMailer extends MFService
{
    
    private $_transport;
    
    public $swiftPath;
    public $host = 'localhost';
    public $port = 25;
    public $username;
    public $password;
    public $encryption;
    
    protected function init()
    {
        if($this->swiftPath)
            require_once($this->swiftPath);
        
        $transport = Swift_SmtpTransport::newInstance($this->host, $this->port);
        if($this->username)
            $transport->setUsername($this->username);
        if($this->password)
            $transport->setPassword($this->password);
        if($this->encryption)
            $transport->setEncryption($this->encryption);
        $transport->start();
        $this->_transport = $transport;
    }
    
    public function send($mail)
    {
        return $this->_transport->send($mail);
    }
}