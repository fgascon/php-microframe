<?php

class MFMailer extends MFService
{
    
    private $_transport;
    
    public $host = 'localhost';
    public $port = 25;
    public $username;
    public $password;
    public $encryption;
    
    protected function init()
    {
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
        MF::trace("Sending mail to :".$mail->getHeaders()->get('To')->toString(), 'system.mail.MFMailer');
        return $this->_transport->send($mail);
    }
}