<?php

class MFViewController extends MFController
{
    
    public function setOutput($output)
    {
        $this->getResponse()->setData($output);
    }
}