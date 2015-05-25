<?php

abstract class MFController
{
    
    private $_id;
    private $_action;
    
    public function __construct($id)
    {
        $this->_id = $id;
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
        if(($action = $this->createAction($actionID)) !== null)
            $this->runAction($action);
        else
            $this->missingAction($actionID);
    }
    
    public function runAction($action)
    {
        $priorAction = $this->_action;
        $this->_action = $action;
        $output = $action->runWithParams($this->getActionParams());
        if($output === false)
            $this->invalidActionParams($action);
        else
            $this->processOutput($output);
        $this->_action = $priorAction;
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
}