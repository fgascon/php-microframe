<?php

abstract class MFJsonController extends MFController
{
    
    protected function processOutput($output)
    {
        header('Content-Type: application/json');
        echo json_encode($output);
    }
    
    protected function handleException($exception)
    {
        $error = array(
            'message'=>$exception->getMessage(),
            'code'=>$exception->getCode(),
        );
        if(MF_DEBUG)
        {
            $error['stack'] = $exception->getTrace();
        }
        return array(
            'error'=>$error,
        );
    }
}