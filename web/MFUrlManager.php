<?php

class MFUrlManager
{
    
    private $_urlMap;
    
    public function __construct($urlMap)
    {
        $this->_urlMap = $urlMap;
    }
    
    public function detectRoute(MFHttpRequest $request)
    {
        $pathInfo = '/'.$request->getPathInfo();
        if(isset($this->_urlMap[$pathInfo]))
        {
            return $this->_urlMap[$pathInfo];
        }
        return ltrim($pathInfo, '/');
    }
}