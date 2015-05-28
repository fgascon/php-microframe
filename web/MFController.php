<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class MFController
{
    
    private $_id;
    private $_action;
    private $_response;
    
    public function __construct($id)
    {
        $this->_id = $id;
        $this->_response = $this->createResponse();
    }
    
    protected function createResponse()
    {
        return new Response();
    }
    
    public function init()
    {
    }
    
    public function getId()
    {
        return $this->_id;
    }
    
    public function getAction()
    {
        return $this->_action;
    }
    
    public function setAction($action)
    {
        $this->_action = $action;
    }
    
    public function getResponse()
    {
        return $this->_response;
    }
    
    public function run($actionID)
    {
        try
        {
            if(($action = $this->createAction($actionID)) !== null)
                $this->runAction($action);
            else
                $this->missingAction($actionID);
        }
        catch(Exception $exception)
        {
            $this->_response = $this->handleException($exception);
        }
        return $this->_response;
    }
    
    public function runAction($action)
    {
        $priorAction = $this->_action;
        $this->_action = $action;
        $output = $action->runWithParams($this->getActionParams());
        if($output === false)
            $output = $this->invalidActionParams($action);
        $this->_action = $priorAction;
        if(is_a($output, 'Symfony\Component\HttpFoundation\Response'))
        {
            $this->_response = $output;
        }
        else
        {
            $this->setOutput($this->processOutput($output));
        }
    }
    
    public function setOutput($output)
    {
        $this->_response->setContent($output);
    }
    
    public function getActionParams()
    {
        return $_GET;
    }
    
    public function createAction($actionID)
    {
        if(method_exists($this, 'action'.$actionID) && strcasecmp($actionID, 's')) // we have actions method
            return new MFAction($this, $actionID);
        return null;
    }
    
    public function invalidActionParams($actionID)
    {
        throw new MFHttpException(400, MF::t('core', 'Your request is invalid.'));
    }
    
    public function missingAction($actionID)
    {
        throw new MFHttpException(404, MF::t('core', 'The system is unable to find the requested action "{action}".',
            array('{action}'=>$actionID)));
    }
    
    public function redirect($url, $statusCode=302, $headers=array())
    {
        return new RedirectResponse($url, $statusCode, $headers);
    }
    
    protected function processOutput($output)
    {
        return $output;
    }
    
    protected function handleException($exception)
    {
        throw $exception;
    }
}
