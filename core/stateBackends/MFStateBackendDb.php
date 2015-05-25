<?php

class MFStateBackendDb extends MFAbstractStateBackend
{
    
    public $database = 'default';
    public $table = 'global_states';
    
    protected function getConnection()
    {
        return MF::app()->getDatabase($this->database);
    }
    
    public function get($name)
    {
        return $this->getConnection()
            ->select('value')->from($this->table)->where('name = :name', array(
                ':name'=>$name,
            ))
            ->queryScalar();
    }
    
    public function set($name, $value)
    {
        $this->getConnection()
            ->createCommand("INSERT INTO `{$this->table}`(name, value) VALUES(:name, :value) ON DUPLICATE KEY UPDATE value=:value")
            ->execute(array(
                ':name'=>$name,
                ':value'=>$value,
            ));
    }
}