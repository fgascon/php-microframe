<?php

abstract class MFController
{
    
    private $_id;
    private $_action;
    
    public function __construct($id)
    {
        $this->_id = $id;
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
    
    public function run($actionID)
    {
        try
        {
            if(($action = $this->createAction($actionID)) !== null)
                $output = $this->runAction($action);
            else
                $output = $this->missingAction($actionID);
        }
        catch(Exception $exception)
        {
            $output = $this->handleException($exception);
        }
        $this->processOutput($output);
    }
    
    public function runAction($action)
    {
        $priorAction = $this->_action;
        $this->_action = $action;
        $output = $action->runWithParams($this->getActionParams());
        if($output === false)
            $output = $this->invalidActionParams($action);
        $this->_action = $priorAction;
        return $output;
    }
    
    public function createAction($actionID)
    {
        if(method_exists($this, 'action'.$actionID) && strcasecmp($actionID, 's')) // we have actions method
            return new MFAction($this, $actionID);
        return null;
    }
    
    public function getActionParams()
    {
        return $_GET;
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
    
    public function redirect($url, $terminate=true, $statusCode=302)
    {
        MF::app()->httpRequest->redirect($url, $terminate, $statusCode);
    }
    
    abstract protected function processOutput($output);
    
    protected function handleException($exception)
    {
        throw $exception;
    }
}