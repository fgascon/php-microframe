<?php

class MFCookieJar
{
    
    private $_request;
    private $_initialized = false;
    
    /**
     * Constructor.
     * @param MFHttpRequest $request owner of this collection.
     */
    public function __construct(MFHttpRequest $request)
    {
        $this->_request = $request;
        $this->copyfrom($this->getCookies());
        $this->_initialized=true;
    }
    
    /**
     * @return MFHttpRequest the request instance
     */
    public function getRequest()
    {
        return $this->_request;
    }
    
    /**
     * @return array list of validated cookies
     */
    protected function getCookies()
    {
        $cookies = array();
        if($this->_request->enableCookieValidation)
        {
            $sm = MF::app()->getSecurity();
            foreach($_COOKIE as $name=>$value)
            {
                if(is_string($value) && ($value=$sm->validateData($value)) !== false)
                    $cookies[$name] = new MFHttpCookie($name, @unserialize($value));
            }
        }
        else
        {
            foreach($_COOKIE as $name=>$value)
                $cookies[$name]=new CHttpCookie($name,$value);
        }
        return $cookies;
    }
    
    /**
     * Adds a cookie with the specified name.
     * This overrides the parent implementation by performing additional
     * operations for each newly added CHttpCookie object.
     * @param mixed $name Cookie name.
     * @param CHttpCookie $cookie Cookie object.
     * @throws Exception if the item to be inserted is not a CHttpCookie object.
     */
    public function add($name,$cookie)
    {
        if($cookie instanceof CHttpCookie)
        {
            $this->remove($name);
            parent::add($name,$cookie);
            if($this->_initialized)
                $this->addCookie($cookie);
        }
        else
            throw new Exception(MF::t('core','CHttpCookieCollection can only hold CHttpCookie objects.'));
    }
    
    /**
     * Removes a cookie with the specified name.
     * This overrides the parent implementation by performing additional
     * cleanup work when removing a CHttpCookie object.
     * Since version 1.1.11, the second parameter is available that can be used to specify
     * the options of the CHttpCookie being removed. For example, this may be useful when dealing
     * with ".domain.tld" where multiple subdomains are expected to be able to manage cookies:
     *
     * <pre>
     * $options=array('domain'=>'.domain.tld');
     * Yii::app()->httpRequest->cookies['foo']=new CHttpCookie('cookie','value',$options);
     * Yii::app()->httpRequest->cookies->remove('cookie',$options);
     * </pre>
     *
     * @param mixed $name Cookie name.
     * @param array $options Cookie configuration array consisting of name-value pairs, available since 1.1.11.
     * @return CHttpCookie The removed cookie object.
     */
    public function remove($name,$options=array())
    {
        if(($cookie=parent::remove($name))!==null)
        {
            if($this->_initialized)
            {
                $cookie->configure($options);
                $this->removeCookie($cookie);
            }
        }
    
        return $cookie;
    }
    
    /**
     * Sends a cookie.
     * @param CHttpCookie $cookie cookie to be sent
     */
    protected function addCookie($cookie)
    {
        $value=$cookie->value;
        if($this->_request->enableCookieValidation)
            $value=MF::app()->getSecurity()->hashData(serialize($value));
        if(version_compare(PHP_VERSION,'5.2.0','>='))
            setcookie($cookie->name,$value,$cookie->expire,$cookie->path,$cookie->domain,$cookie->secure,$cookie->httpOnly);
        else
            setcookie($cookie->name,$value,$cookie->expire,$cookie->path,$cookie->domain,$cookie->secure);
    }
    
    /**
     * Deletes a cookie.
     * @param CHttpCookie $cookie cookie to be deleted
     */
    protected function removeCookie($cookie)
    {
        if(version_compare(PHP_VERSION,'5.2.0','>='))
            setcookie($cookie->name,'',0,$cookie->path,$cookie->domain,$cookie->secure,$cookie->httpOnly);
        else
            setcookie($cookie->name,'',0,$cookie->path,$cookie->domain,$cookie->secure);
    }
}