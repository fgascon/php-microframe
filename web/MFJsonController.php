<?php

use Symfony\Component\HttpFoundation\JsonResponse;

abstract class MFJsonController extends MFController
{
    
    protected function createResponse()
    {
        return new JsonResponse();
    }
    
    public function setOutput($output)
    {
        $this->getResponse()->setData($output);
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
        $output = array(
            'error'=>$error,
        );
        if($exception instanceof MFHttpException)
            return new JsonResponse($output, $exception->statusCode);
        else
            return new JsonResponse($output);
    }
}