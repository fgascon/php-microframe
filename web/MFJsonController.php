<?php

abstract class MFJsonController extends MFController
{
    
    protected function processOutput($output)
    {
        header('Content-Type: application/json');
        echo json_encode($output);
    }
}