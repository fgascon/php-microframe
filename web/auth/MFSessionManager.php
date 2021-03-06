<?php

class MFSessionManager extends MFService implements ArrayAccess, Countable
{
    
    public $autoStart = true;
    
    protected function init()
    {
        if($this->autoStart)
            $this->open();
        register_shutdown_function(array($this, 'close'));
    }
    
	public function getUseCustomStorage()
	{
		return false;
	}
    
    public function open()
    {
		if($this->getUseCustomStorage())
            @session_set_save_handler(array($this, 'openSession'), array($this, 'closeSession'), array($this, 'readSession'), array($this, 'writeSession'), array($this, 'destroySession'), array($this, 'gcSession'));
        
        @session_start();
        if(MF_DEBUG && session_id() == '')
        {
            $message = MF::t('core', 'Failed to start session.');
            if(function_exists('error_get_last'))
            {
                $error = error_get_last();
                if(isset($error['message']))
                    $message = $error['message'];
            }
            MF::log($message, 'warning', 'system.web.MFSessionManager');
        }
    }
    
	public function close()
	{
		if(session_id() !== '')
			@session_write_close();
	}
	
	public function destroy()
	{
		if(session_id() !== '')
		{
			@session_unset();
			@session_destroy();
		}
	}
	
	public function getIsStarted()
	{
		return session_id() !== '';
	}
	
	public function getSessionID()
	{
		return session_id();
	}
	
	public function setSessionID($value)
	{
		session_id($value);
	}
	
	public function regenerateID($deleteOldSession=false)
	{
	    if($this->getIsStarted())
			session_regenerate_id($deleteOldSession);
	}
	
	public function getSessionName()
	{
		return session_name();
	}
	
	public function setSessionName($value)
	{
		session_name($value);
	}
	
	public function getSavePath()
	{
		return session_save_path();
	}
	
	public function setSavePath($value)
	{
		if(is_dir($value))
			session_save_path($value);
		else
			throw new Exception(MF::t('core', 'MFSessionManager.savePath "{path}" is not a valid directory.',
				array('{path}'=>$value)));
	}
	
	public function getCookieParams()
	{
		return session_get_cookie_params();
	}

	public function setCookieParams($value)
	{
	    $data=session_get_cookie_params();
	    extract($data);
	    extract($value);
	    if(isset($httponly))
	        session_set_cookie_params($lifetime,$path,$domain,$secure,$httponly);
	    else
	        session_set_cookie_params($lifetime,$path,$domain,$secure);
	}
	
	public function getCookieMode()
	{
		if(ini_get('session.use_cookies')==='0')
			return 'none';
		elseif(ini_get('session.use_only_cookies')==='0')
			return 'allow';
		else
			return 'only';
	}
	
	public function setCookieMode($value)
	{
		if($value==='none')
		{
			ini_set('session.use_cookies','0');
			ini_set('session.use_only_cookies','0');
		}
		elseif($value==='allow')
		{
			ini_set('session.use_cookies','1');
			ini_set('session.use_only_cookies','0');
		}
		elseif($value==='only')
		{
			ini_set('session.use_cookies','1');
			ini_set('session.use_only_cookies','1');
		}
		else
			throw new Exception(MF::t('core', 'MFSessionManager.cookieMode can only be "none", "allow" or "only".'));
	}
	
	public function getGCProbability()
	{
		return (float)(ini_get('session.gc_probability')/ini_get('session.gc_divisor')*100);
	}
	
	public function setGCProbability($value)
	{
		if($value>=0 && $value<=100)
		{
			// percent * 21474837 / 2147483647 ≈ percent * 0.01
			ini_set('session.gc_probability', floor($value*21474836.47));
			ini_set('session.gc_divisor', 2147483647);
		}
		else
			throw new Exception(MF::t('core','MFSessionManager.gcProbability "{value}" is invalid. It must be a float between 0 and 100.',
				array('{value}'=>$value)));
	}
	
	public function getUseTransparentSessionID()
	{
		return ini_get('session.use_trans_sid') == 1;
	}
	
	public function setUseTransparentSessionID($value)
	{
		ini_set('session.use_trans_sid', $value?'1':'0');
	}
	
	public function getTimeout()
	{
		return (int)ini_get('session.gc_maxlifetime');
	}
	
	public function setTimeout($value)
	{
		ini_set('session.gc_maxlifetime', $value);
	}
	
	public function openSession($savePath,$sessionName)
	{
		return true;
	}
	
	public function closeSession()
	{
		return true;
	}
	
	public function readSession($id)
	{
		return '';
	}
	
	public function writeSession($id,$data)
	{
		return true;
	}
	
	public function destroySession($id)
	{
		return true;
	}
	
	public function gcSession($maxLifetime)
	{
		return true;
	}
	
	public function getCount()
	{
	    return count($_SESSION);
	}
	
	public function count()
	{
	    return $this->getCount();
	}
	
	public function getKeys()
	{
	    return array_keys($_SESSION);
	}
	
	public function get($key, $defaultValue=null)
	{
	    return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
	}
	
	public function itemAt($key)
	{
	    return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}
	
	public function add($key, $value)
	{
	    $_SESSION[$key] = $value;
	}
	
	public function remove($key)
	{
	    if(isset($_SESSION[$key]))
	    {
	        $value = $_SESSION[$key];
	        unset($_SESSION[$key]);
	        return $value;
	    }
	    else
	        return null;
	}
	
	public function clear()
	{
	    foreach(array_keys($_SESSION) as $key)
	        unset($_SESSION[$key]);
	}
	
	public function contains($key)
	{
	    return isset($_SESSION[$key]);
	}
	
	public function toArray()
	{
	    return $_SESSION;
	}
	
	public function offsetExists($offset)
	{
	    return isset($_SESSION[$offset]);
	}
	
	public function offsetGet($offset)
	{
	    return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
	}
	
	public function offsetSet($offset, $item)
	{
	    $_SESSION[$offset] = $item;
	}
	
	public function offsetUnset($offset)
	{
	    unset($_SESSION[$offset]);
	}
}