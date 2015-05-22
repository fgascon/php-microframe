<?php

class MFComponent
{
    
    private $_e;
    
    /**
     * Returns a property value, an event handler list or a behavior based on its name.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to read a property or obtain event handlers:
     * <pre>
     * $value=$component->propertyName;
     * $handlers=$component->eventName;
     * </pre>
     * @param string $name the property name or event name
     * @return mixed the property value, event handlers attached to the event, or the named behavior
     * @throws Exception if the property or event is not defined
     * @see __set
     */
    public function __get($name)
    {
        $getter='get'.$name;
        if(method_exists($this,$getter))
            return $this->$getter();
        elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
        {
            // duplicating getEventHandlers() here for performance
            $name=strtolower($name);
            if(!isset($this->_e[$name]))
                $this->_e[$name]=new CList;
            return $this->_e[$name];
        }
        throw new Exception(MF::t('core','Property "{class}.{property}" is not defined.',
            array('{class}'=>get_class($this), '{property}'=>$name)));
    }
    
    /**
     * Sets value of a component property.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to set a property or attach an event handler
     * <pre>
     * $this->propertyName=$value;
     * $this->eventName=$callback;
     * </pre>
     * @param string $name the property name or the event name
     * @param mixed $value the property value or callback
     * @return mixed
     * @throws Exception if the property/event is not defined or the property is read only.
     * @see __get
     */
    public function __set($name,$value)
    {
        $setter='set'.$name;
        if(method_exists($this,$setter))
            return $this->$setter($value);
        elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
        {
            // duplicating getEventHandlers() here for performance
            $name=strtolower($name);
            if(!isset($this->_e[$name]))
                $this->_e[$name]=new CList;
            return $this->_e[$name]->add($value);
        }
        if(method_exists($this,'get'.$name))
            throw new Exception(MF::t('core','Property "{class}.{property}" is read only.',
                array('{class}'=>get_class($this), '{property}'=>$name)));
        else
            throw new Exception(MF::t('core','Property "{class}.{property}" is not defined.',
                array('{class}'=>get_class($this), '{property}'=>$name)));
    }
    
    /**
     * Checks if a property value is null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using isset() to detect if a component property is set or not.
     * @param string $name the property name or the event name
     * @return boolean
     */
    public function __isset($name)
    {
        $getter='get'.$name;
        if(method_exists($this,$getter))
            return $this->$getter()!==null;
        elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
        {
            $name=strtolower($name);
            return isset($this->_e[$name]) && $this->_e[$name]->getCount();
        }
        return false;
    }
    
    /**
     * Sets a component property to be null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using unset() to set a component property to be null.
     * @param string $name the property name or the event name
     * @throws Exception if the property is read only.
     * @return mixed
     */
    public function __unset($name)
    {
        $setter='set'.$name;
        if(method_exists($this,$setter))
            $this->$setter(null);
        elseif(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
            unset($this->_e[strtolower($name)]);
        elseif(method_exists($this,'get'.$name))
            throw new Exception(MF::t('core','Property "{class}.{property}" is read only.',
                array('{class}'=>get_class($this), '{property}'=>$name)));
    }
    
    /**
     * Calls the named method which is not a class method.
     * Do not call this method. This is a PHP magic method that we override
     * to implement the behavior feature.
     * @param string $name the method name
     * @param array $parameters method parameters
     * @throws Exception if current class and its behaviors do not have a method or closure with the given name
     * @return mixed the method return value
     */
    public function __call($name,$parameters)
    {
        if(class_exists('Closure', false) && ($this->canGetProperty($name) || property_exists($this, $name)) && $this->$name instanceof Closure)
            return call_user_func_array($this->$name, $parameters);
        throw new Exception(MF::t('core','{class} and its behaviors do not have a method or closure named "{name}".',
            array('{class}'=>get_class($this), '{name}'=>$name)));
    }
    
    /**
     * Determines whether a property is defined.
     * A property is defined if there is a getter or setter method
     * defined in the class. Note, property names are case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property is defined
     * @see canGetProperty
     * @see canSetProperty
     */
    public function hasProperty($name)
    {
        return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
    }
    
    /**
     * Determines whether a property can be read.
     * A property can be read if the class has a getter method
     * for the property name. Note, property name is case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property can be read
     * @see canSetProperty
     */
    public function canGetProperty($name)
    {
        return method_exists($this,'get'.$name);
    }
    
    /**
     * Determines whether a property can be set.
     * A property can be written if the class has a setter method
     * for the property name. Note, property name is case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property can be written
     * @see canGetProperty
     */
    public function canSetProperty($name)
    {
        return method_exists($this,'set'.$name);
    }
    
    /**
     * Determines whether an event is defined.
     * An event is defined if the class has a method named like 'onXXX'.
     * Note, event name is case-insensitive.
     * @param string $name the event name
     * @return boolean whether an event is defined
     */
    public function hasEvent($name)
    {
        return !strncasecmp($name,'on',2) && method_exists($this,$name);
    }
    
    /**
     * Checks whether the named event has attached handlers.
     * @param string $name the event name
     * @return boolean whether an event has been attached one or several handlers
     */
    public function hasEventHandler($name)
    {
        $name=strtolower($name);
        return isset($this->_e[$name]) && $this->_e[$name]->getCount()>0;
    }
    
    /**
     * Returns the list of attached event handlers for an event.
     * @param string $name the event name
     * @return CList list of attached event handlers for the event
     * @throws Exception if the event is not defined
     */
    public function getEventHandlers($name)
    {
        if($this->hasEvent($name))
        {
            $name=strtolower($name);
            if(!isset($this->_e[$name]))
                $this->_e[$name]=new CList;
            return $this->_e[$name];
        }
        else
            throw new Exception(MF::t('core','Event "{class}.{event}" is not defined.',
                array('{class}'=>get_class($this), '{event}'=>$name)));
    }
    
    /**
     * Attaches an event handler to an event.
     *
     * An event handler must be a valid PHP callback, i.e., a string referring to
     * a global function name, or an array containing two elements with
     * the first element being an object and the second element a method name
     * of the object.
     *
     * An event handler must be defined with the following signature,
     * <pre>
     * function handlerName($event) {}
     * </pre>
     * where $event includes parameters associated with the event.
     *
     * This is a convenient method of attaching a handler to an event.
     * It is equivalent to the following code:
     * <pre>
     * $component->getEventHandlers($eventName)->add($eventHandler);
     * </pre>
     *
     * Using {@link getEventHandlers}, one can also specify the execution order
     * of multiple handlers attaching to the same event. For example:
     * <pre>
     * $component->getEventHandlers($eventName)->insertAt(0,$eventHandler);
     * </pre>
     * makes the handler to be invoked first.
     *
     * @param string $name the event name
     * @param callback $handler the event handler
     * @throws Exception if the event is not defined
     * @see detachEventHandler
     */
    public function attachEventHandler($name,$handler)
    {
        $this->getEventHandlers($name)->add($handler);
    }
    
    /**
     * Detaches an existing event handler.
     * This method is the opposite of {@link attachEventHandler}.
     * @param string $name event name
     * @param callback $handler the event handler to be removed
     * @return boolean if the detachment process is successful
     * @see attachEventHandler
     */
    public function detachEventHandler($name,$handler)
    {
        if($this->hasEventHandler($name))
            return $this->getEventHandlers($name)->remove($handler)!==false;
        else
            return false;
    }
    
    /**
     * Raises an event.
     * This method represents the happening of an event. It invokes
     * all attached handlers for the event.
     * @param string $name the event name
     * @param CEvent $event the event parameter
     * @throws Exception if the event is undefined or an event handler is invalid.
     */
    public function raiseEvent($name,$event)
    {
        $name=strtolower($name);
        if(isset($this->_e[$name]))
        {
            foreach($this->_e[$name] as $handler)
            {
                if(is_string($handler))
                    call_user_func($handler,$event);
                elseif(is_callable($handler,true))
                {
                    if(is_array($handler))
                    {
                        // an array: 0 - object, 1 - method name
                        list($object,$method)=$handler;
                        if(is_string($object))	// static method call
                            call_user_func($handler,$event);
                        elseif(method_exists($object,$method))
                        $object->$method($event);
                        else
                            throw new Exception(MF::t('core','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
                                array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>$handler[1])));
                    }
                    else // PHP 5.3: anonymous function
                        call_user_func($handler,$event);
                }
                else
                    throw new Exception(MF::t('core','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
                        array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>gettype($handler))));
                // stop further handling if param.handled is set true
                if(($event instanceof CEvent) && $event->handled)
                    return;
            }
        }
        elseif(MF_DEBUG && !$this->hasEvent($name))
        throw new Exception(MF::t('core','Event "{class}.{event}" is not defined.',
            array('{class}'=>get_class($this), '{event}'=>$name)));
    }
    
    /**
     * Evaluates a PHP expression or callback under the context of this component.
     *
     * Valid PHP callback can be class method name in the form of
     * array(ClassName/Object, MethodName), or anonymous function (only available in PHP 5.3.0 or above).
     *
     * If a PHP callback is used, the corresponding function/method signature should be
     * <pre>
     * function foo($param1, $param2, ..., $component) { ... }
     * </pre>
     * where the array elements in the second parameter to this method will be passed
     * to the callback as $param1, $param2, ...; and the last parameter will be the component itself.
     *
     * If a PHP expression is used, the second parameter will be "extracted" into PHP variables
     * that can be directly accessed in the expression. See {@link http://us.php.net/manual/en/function.extract.php PHP extract}
     * for more details. In the expression, the component object can be accessed using $this.
     *
     * A PHP expression can be any PHP code that has a value. To learn more about what an expression is,
     * please refer to the {@link http://www.php.net/manual/en/language.expressions.php php manual}.
     *
     * @param mixed $_expression_ a PHP expression or PHP callback to be evaluated.
     * @param array $_data_ additional parameters to be passed to the above expression/callback.
     * @return mixed the expression result
     * @since 1.1.0
     */
    public function evaluateExpression($_expression_,$_data_=array())
    {
        if(is_string($_expression_))
        {
            extract($_data_);
            return eval('return '.$_expression_.';');
        }
        else
        {
            $_data_[]=$this;
            return call_user_func_array($_expression_, $_data_);
        }
    }
}