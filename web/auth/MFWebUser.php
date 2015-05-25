<?php

class MFWebUser extends MFService
{
	const STATES_VAR = '__states';
	const AUTH_TIMEOUT_VAR = '__timeout';
	const AUTH_ABSOLUTE_TIMEOUT_VAR = '__absolute_timeout';

	/**
	 * @var boolean whether to enable cookie-based login. Defaults to false.
	 */
	public $allowAutoLogin = false;
	
	/**
	 * @var string|array the URL for login. If using array, the first element should be
	 * the route to the login action, and the rest name-value pairs are GET parameters
	 * to construct the login URL (e.g. array('auth/login')). If this property is null,
	 * a 403 HTTP exception will be raised instead.
	 * @see MFController::createUrl
	 */
	public $loginUrl = array('auth/login');
	
	/**
	 * @var array the property values (in name-value pairs) used to initialize the identity cookie.
	 * Any property of {@link MFHttpCookie} may be initialized.
	 * This property is effective only when {@link allowAutoLogin} is true.
	 */
	public $identityCookie;
	
	/**
	 * @var integer timeout in seconds after which user is logged out if inactive.
	 * If this property is not set, the user will be logged out after the current session expires
	 * (c.f. {@link CHttpSession::timeout}).
	 * @since 1.1.7
	 */
	public $authTimeout;
	
	/**
	 * @var integer timeout in seconds after which user is logged out regardless of activity.
	 * @since 1.1.14
	 */
	public $absoluteAuthTimeout;
	
	/**
	 * @var boolean whether to automatically renew the identity cookie each time a page is requested.
	 * Defaults to false. This property is effective only when {@link allowAutoLogin} is true.
	 * When this is false, the identity cookie will expire after the specified duration since the user
	 * is initially logged in. When this is true, the identity cookie will expire after the specified duration
	 * since the user visits the site the last time.
	 * @see allowAutoLogin
	 * @since 1.1.0
	 */
	public $autoRenewCookie = false;
	
	/**
	 * @var string value that will be echoed in case that user session has expired during an ajax call.
	 * When a request is made and user session has expired, {@link loginRequired} redirects to {@link loginUrl} for login.
	 * If that happens during an ajax call, the complete HTML login page is returned as the result of that ajax call. That could be
	 * a problem if the ajax call expects the result to be a json array or a predefined string, as the login page is ignored in that case.
	 * To solve this, set this property to the desired return value.
	 *
	 * If this property is set, this value will be returned as the result of the ajax call in case that the user session has expired.
	 * @since 1.1.9
	 * @see loginRequired
	 */
	public $loginRequiredAjaxResponse;
    
	private $_keyPrefix;
	private $_access = array();
    
	/**
	 * PHP magic method.
	 * This method is overriden so that persistent states can be accessed like properties.
	 * @param string $name property name
	 * @return mixed property value
	 */
	public function __get($name)
	{
		if($this->hasState($name))
			return $this->getState($name);
		else
			return parent::__get($name);
	}

	/**
	 * PHP magic method.
	 * This method is overriden so that persistent states can be set like properties.
	 * @param string $name property name
	 * @param mixed $value property value
	 */
	public function __set($name, $value)
	{
		if($this->hasState($name))
			$this->setState($name, $value);
		else
			parent::__set($name, $value);
	}

	/**
	 * PHP magic method.
	 * This method is overriden so that persistent states can also be checked for null value.
	 * @param string $name property name
	 * @return boolean
	 */
	public function __isset($name)
	{
		if($this->hasState($name))
			return $this->getState($name) !== null;
		else
			return parent::__isset($name);
	}

	/**
	 * PHP magic method.
	 * This method is overriden so that persistent states can also be unset.
	 * @param string $name property name
	 * @throws Exception if the property is read only.
	 */
	public function __unset($name)
	{
		if($this->hasState($name))
			$this->setState($name, null);
		else
			parent::__unset($name);
	}

	/**
	 * Initializes the application component.
	 * This method overrides the parent implementation by starting session,
	 * performing cookie-based authentication if enabled, and updating the flash variables.
	 */
	public function init()
	{
		parent::init();
		MF::app()->session->open();
		if($this->getIsGuest() && $this->allowAutoLogin)
			$this->restoreFromCookie();
		elseif($this->autoRenewCookie && $this->allowAutoLogin)
			$this->renewCookie();
        
		$this->updateAuthStatus();
	}

	/**
	 * Logs in a user.
	 *
	 * The user identity information will be saved in storage that is
	 * persistent during the user session. By default, the storage is simply
	 * the session storage. If the duration parameter is greater than 0,
	 * a cookie will be sent to prepare for cookie-based login in future.
	 *
	 * Note, you have to set {@link allowAutoLogin} to true
	 * if you want to allow user to be authenticated based on the cookie information.
	 *
	 * @param IUserIdentity $identity the user identity (which should already be authenticated)
	 * @param integer $duration number of seconds that the user can remain in logged-in status. Defaults to 0, meaning login till the user closes the browser.
	 * If greater than 0, cookie-based login will be used. In this case, {@link allowAutoLogin}
	 * must be set true, otherwise an exception will be thrown.
	 * @return boolean whether the user is logged in
	 */
	public function login($identity, $duration=0)
	{
		$id = $identity->getId();
		$states = $identity->getPersistentStates();
		if($this->beforeLogin($id, $states, false))
		{
			$this->changeIdentity($id, $states);
            
			if($duration>0)
			{
				if($this->allowAutoLogin)
					$this->saveToCookie($duration);
				else
					throw new Exception(MF::t('core', '{class}.allowAutoLogin must be set true in order to use cookie-based authentication.',
						array('{class}'=>get_class($this))));
			}
            
			if($this->absoluteAuthTimeout)
				$this->setState(self::AUTH_ABSOLUTE_TIMEOUT_VAR, time()+$this->absoluteAuthTimeout);
			$this->afterLogin(false);
		}
		return !$this->getIsGuest();
	}

	/**
	 * Logs out the current user.
	 * This will remove authentication-related session data.
	 * If the parameter is true, the whole session will be destroyed as well.
	 * @param boolean $destroySession whether to destroy the whole session. Defaults to true. If false,
	 * then {@link clearStates} will be called, which removes only the data stored via {@link setState}.
	 */
	public function logout($destroySession=true)
	{
		if($this->beforeLogout())
		{
			if($this->allowAutoLogin)
			{
				MF::app()->getHttpRequest()->getCookies()->remove($this->getStateKeyPrefix());
				if($this->identityCookie !== null)
				{
					$cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
					$cookie->value = null;
					$cookie->expire = 0;
					MF::app()->getHttpRequest()->getCookies()->add($cookie->name,$cookie);
				}
			}
			if($destroySession)
				MF::app()->session->destroy();
			else
				$this->clearStates();
			$this->_access = array();
			$this->afterLogout();
		}
	}

	/**
	 * Returns a value indicating whether the user is a guest (not authenticated).
	 * @return boolean whether the current application user is a guest.
	 */
	public function getIsGuest()
	{
		return $this->getState('__id') === null;
	}

	/**
	 * Returns a value that uniquely represents the user.
	 * @return mixed the unique identifier for the user. If null, it means the user is a guest.
	 */
	public function getId()
	{
		return $this->getState('__id');
	}

	/**
	 * @param mixed $value the unique identifier for the user. If null, it means the user is a guest.
	 */
	public function setId($value)
	{
		$this->setState('__id', $value);
	}

	/**
	 * Returns the URL that the user should be redirected to after successful login.
	 * This property is usually used by the login action. If the login is successful,
	 * the action should read this property and use it to redirect the user browser.
	 * @param string $defaultUrl the default return URL in case it was not set previously. If this is null,
	 * the application entry URL will be considered as the default return URL.
	 * @return string the URL that the user should be redirected to after login.
	 * @see loginRequired
	 */
	public function getReturnUrl($defaultUrl=null)
	{
		if($defaultUrl === null)
		{
			$defaultReturnUrl = MF::app()->getHttpRequest()->getBaseUrl().'/';
		}
		else
		{
			//$defaultReturnUrl = CHtml::normalizeUrl($defaultUrl);
		    $defaultReturnUrl = $defaultUrl;
		}
		return $this->getState('__returnUrl', $defaultReturnUrl);
	}

	/**
	 * @param string $value the URL that the user should be redirected to after login.
	 */
	public function setReturnUrl($value)
	{
		$this->setState('__returnUrl', $value);
	}

	/**
	 * Redirects the user browser to the login page.
	 * Before the redirection, the current URL (if it's not an AJAX url) will be
	 * kept in {@link returnUrl} so that the user browser may be redirected back
	 * to the current page after successful login. Make sure you set {@link loginUrl}
	 * so that the user browser can be redirected to the specified login URL after
	 * calling this method.
	 * After calling this method, the current request processing will be terminated.
	 */
	public function loginRequired()
	{
		$app = MF::app();
		$request = $app->getHttpRequest();

		if(!$request->getIsAjaxRequest())
		{
			$this->setReturnUrl($request->getUrl());
			if(($url=$this->loginUrl) !== null)
			{
				if(is_array($url))
				{
					$route = isset($url[0]) ? $url[0] : 'main/index';
					$url = $app->getUrlManager()->createUrl($route, array_splice($url, 1));
				}
				$request->redirect($url);
			}
		}
		elseif(isset($this->loginRequiredAjaxResponse))
		{
			echo $this->loginRequiredAjaxResponse;
			exit(0);
		}

		throw new MFHttpException(403, MF::t('core', 'Login Required'));
	}

	/**
	 * This method is called before logging in a user.
	 * You may override this method to provide additional security check.
	 * For example, when the login is cookie-based, you may want to verify
	 * that the user ID together with a random token in the states can be found
	 * in the database. This will prevent hackers from faking arbitrary
	 * identity cookies even if they crack down the server private key.
	 * @param mixed $id the user ID. This is the same as returned by {@link getId()}.
	 * @param array $states a set of name-value pairs that are provided by the user identity.
	 * @param boolean $fromCookie whether the login is based on cookie
	 * @return boolean whether the user should be logged in
	 * @since 1.1.3
	 */
	protected function beforeLogin($id, $states, $fromCookie)
	{
		return true;
	}

	/**
	 * This method is called after the user is successfully logged in.
	 * You may override this method to do some postprocessing (e.g. log the user
	 * login IP and time; load the user permission information).
	 * @param boolean $fromCookie whether the login is based on cookie.
	 * @since 1.1.3
	 */
	protected function afterLogin($fromCookie)
	{
	}

	/**
	 * This method is invoked when calling {@link logout} to log out a user.
	 * If this method return false, the logout action will be cancelled.
	 * You may override this method to provide additional check before
	 * logging out a user.
	 * @return boolean whether to log out the user
	 * @since 1.1.3
	 */
	protected function beforeLogout()
	{
		return true;
	}

	/**
	 * This method is invoked right after a user is logged out.
	 * You may override this method to do some extra cleanup work for the user.
	 * @since 1.1.3
	 */
	protected function afterLogout()
	{
	}

	/**
	 * Populates the current user object with the information obtained from cookie.
	 * This method is used when automatic login ({@link allowAutoLogin}) is enabled.
	 * The user identity information is recovered from cookie.
	 * Sufficient security measures are used to prevent cookie data from being tampered.
	 * @see saveToCookie
	 */
	protected function restoreFromCookie()
	{
		$app = MF::app();
		$request = $app->getHttpRequest();
		$cookie = $request->getCookies()->itemAt($this->getStateKeyPrefix());
		if($cookie && !empty($cookie->value) && is_string($cookie->value) && ($data=$app->getSecurity()->validateData($cookie->value)) !== false)
		{
			$data = @unserialize($data);
			if(is_array($data) && isset($data[0], $data[1], $data[2]))
			{
				list($id, $duration, $states) = $data;
				if($this->beforeLogin($id, $states, true))
				{
					$this->changeIdentity($id, $states);
					if($this->autoRenewCookie)
					{
						$this->saveToCookie($duration);
					}
					$this->afterLogin(true);
				}
			}
		}
	}

	/**
	 * Renews the identity cookie.
	 * This method will set the expiration time of the identity cookie to be the current time
	 * plus the originally specified cookie duration.
	 * @since 1.1.3
	 */
	protected function renewCookie()
	{
		$request = MF::app()->getHttpRequest();
		$cookies = $request->getCookies();
		$cookie = $cookies->itemAt($this->getStateKeyPrefix());
		if($cookie && !empty($cookie->value) && ($data=MF::app()->getSecurity()->validateData($cookie->value)) !== false)
		{
			$data = @unserialize($data);
			if(is_array($data) && isset($data[0], $data[1], $data[2]))
			{
				$this->saveToCookie($data[1]);
			}
		}
	}

	/**
	 * Saves necessary user data into a cookie.
	 * This method is used when automatic login ({@link allowAutoLogin}) is enabled.
	 * This method saves user ID, username, other identity states and a validation key to cookie.
	 * These information are used to do authentication next time when user visits the application.
	 * @param integer $duration number of seconds that the user can remain in logged-in status. Defaults to 0, meaning login till the user closes the browser.
	 * @see restoreFromCookie
	 */
	protected function saveToCookie($duration)
	{
		$app = MF::app();
		$cookie = $this->createIdentityCookie($this->getStateKeyPrefix());
		$cookie->expire = time()+$duration;
		$data = array(
			$this->getId(),
			$duration,
			$this->saveIdentityStates(),
		);
		$cookie->value = $app->getSecurity()->hashData(serialize($data));
		$app->getHttpRequest()->getCookies()->add($cookie->name, $cookie);
	}

	/**
	 * Creates a cookie to store identity information.
	 * @param string $name the cookie name
	 * @return MFHttpCookie the cookie used to store identity information
	 */
	protected function createIdentityCookie($name)
	{
		$cookie = new MFHttpCookie($name, '');
		if(is_array($this->identityCookie))
		{
			foreach($this->identityCookie as $name=>$value)
				$cookie->$name = $value;
		}
		return $cookie;
	}

	/**
	 * @return string a prefix for the name of the session variables storing user session data.
	 */
	public function getStateKeyPrefix()
	{
		if($this->_keyPrefix!==null)
			return $this->_keyPrefix;
		else
			return $this->_keyPrefix = md5('MF.'.get_class($this));
	}

	/**
	 * @param string $value a prefix for the name of the session variables storing user session data.
	 */
	public function setStateKeyPrefix($value)
	{
		$this->_keyPrefix = $value;
	}

	/**
	 * Returns the value of a variable that is stored in user session.
	 *
	 * This function is designed to be used by MFWebUser descendant classes
	 * who want to store additional user information in user session.
	 * A variable, if stored in user session using {@link setState} can be
	 * retrieved back using this function.
	 *
	 * @param string $key variable name
	 * @param mixed $defaultValue default value
	 * @return mixed the value of the variable. If it doesn't exist in the session,
	 * the provided default value will be returned
	 * @see setState
	 */
	public function getState($key, $defaultValue=null)
	{
		$key = $this->getStateKeyPrefix().$key;
		return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
	}

	/**
	 * Stores a variable in user session.
	 *
	 * This function is designed to be used by MFWebUser descendant classes
	 * who want to store additional user information in user session.
	 * By storing a variable using this function, the variable may be retrieved
	 * back later using {@link getState}. The variable will be persistent
	 * across page requests during a user session.
	 *
	 * @param string $key variable name
	 * @param mixed $value variable value
	 * @param mixed $defaultValue default value. If $value===$defaultValue, the variable will be
	 * removed from the session
	 * @see getState
	 */
	public function setState($key, $value, $defaultValue=null)
	{
		$key = $this->getStateKeyPrefix().$key;
		if($value === $defaultValue)
			unset($_SESSION[$key]);
		else
			$_SESSION[$key] = $value;
	}

	/**
	 * Returns a value indicating whether there is a state of the specified name.
	 * @param string $key state name
	 * @return boolean whether there is a state of the specified name.
	 */
	public function hasState($key)
	{
		$key = $this->getStateKeyPrefix().$key;
		return isset($_SESSION[$key]);
	}

	/**
	 * Clears all user identity information from persistent storage.
	 * This will remove the data stored via {@link setState}.
	 */
	public function clearStates()
	{
		$keys = array_keys($_SESSION);
		$prefix = $this->getStateKeyPrefix();
		$n = strlen($prefix);
		foreach($keys as $key)
		{
			if(!strncmp($key,$prefix,$n))
				unset($_SESSION[$key]);
		}
	}

	/**
	 * Changes the current user with the specified identity information.
	 * This method is called by {@link login} and {@link restoreFromCookie}
	 * when the current user needs to be populated with the corresponding
	 * identity information. Derived classes may override this method
	 * by retrieving additional user-related information. Make sure the
	 * parent implementation is called first.
	 * @param mixed $id a unique identifier for the user
	 * @param string $name the display name for the user
	 * @param array $states identity states
	 */
	protected function changeIdentity($id, $states)
	{
		MF::app()->session->regenerateID(true);
		$this->setId($id);
		$this->loadIdentityStates($states);
	}

	/**
	 * Retrieves identity states from persistent storage and saves them as an array.
	 * @return array the identity states
	 */
	protected function saveIdentityStates()
	{
		$states = array();
		foreach($this->getState(self::STATES_VAR, array()) as $name=>$dummy)
			$states[$name] = $this->getState($name);
		return $states;
	}

	/**
	 * Loads identity states from an array and saves them to persistent storage.
	 * @param array $states the identity states
	 */
	protected function loadIdentityStates($states)
	{
		$names = array();
		if(is_array($states))
		{
			foreach($states as $name=>$value)
			{
				$this->setState($name, $value);
				$names[$name]=true;
			}
		}
		$this->setState(self::STATES_VAR,$names);
	}

	/**
	 * Updates the authentication status according to {@link authTimeout}.
	 * If the user has been inactive for {@link authTimeout} seconds, or {link absoluteAuthTimeout} has passed,
	 * he will be automatically logged out.
	 * @since 1.1.7
	 */
	protected function updateAuthStatus()
	{
		if(($this->authTimeout!==null || $this->absoluteAuthTimeout!==null) && !$this->getIsGuest())
		{
			$expires = $this->getState(self::AUTH_TIMEOUT_VAR);
			$expiresAbsolute = $this->getState(self::AUTH_ABSOLUTE_TIMEOUT_VAR);

			if ($expires !== null && $expires < time() || $expiresAbsolute !== null && $expiresAbsolute < time())
				$this->logout(false);
			else
				$this->setState(self::AUTH_TIMEOUT_VAR, time()+$this->authTimeout);
		}
	}
}
