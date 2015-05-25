<?php

abstract class MFUserIdentity extends MFComponent
{
    
    private $_state = array();
    private $_id;
    
    /**
     * Authenticates the user.
     * The information needed to authenticate the user
     * are usually provided in the constructor.
     * @return boolean whether authentication succeeds.
     */
    abstract public function authenticate();
    
    /**
     * Returns a value indicating whether the identity is authenticated.
     * @return boolean whether the identity is valid.
    */
    public function getIsAuthenticated()
	{
		return !!$this->_id;
	}
    
    /**
     * Returns a value that uniquely represents the identity.
     * @return mixed a value that uniquely represents the identity (e.g. primary key value).
    */
    public function getId()
    {
        return $this->_id;
    }
    
    protected function setId($id)
    {
        $this->_id = $id;
    }
    
	/**
	 * Returns the identity states that should be persisted.
	 * 
	 * @return array the identity states that should be persisted.
	 */
	public function getPersistentStates()
	{
		return $this->_state;
	}
    
	/**
	 * Sets an array of persistent states.
	 *
	 * @param array $states the identity states that should be persisted.
	 */
	public function setPersistentStates($states)
	{
		$this->_state = $states;
	}
	
	/**
	 * Gets the persisted state by the specified name.
	 * @param string $name the name of the state
	 * @param mixed $defaultValue the default value to be returned if the named state does not exist
	 * @return mixed the value of the named state
	 */
	public function getState($name, $defaultValue=null)
	{
	    return isset($this->_state[$name]) ? $this->_state[$name] : $defaultValue;
	}
	
	/**
	 * Sets the named state with a given value.
	 * @param string $name the name of the state
	 * @param mixed $value the value of the named state
	 */
	public function setState($name, $value)
	{
	    $this->_state[$name] = $value;
	}
	
	/**
	 * Removes the specified state.
	 * @param string $name the name of the state
	 */
	public function clearState($name)
	{
	    unset($this->_state[$name]);
	}
}