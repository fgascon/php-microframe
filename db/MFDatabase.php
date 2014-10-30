<?php

class MFDatabase
{
    
    private $_connection;
    
    public $host = '127.0.0.1';
    public $dbname;
    public $username;
    public $password;
    public $options = array();
    public $autoconnect = true;
    
    public function __construct($config=array())
    {
        foreach($config as $name=>$value)
        {
            $this->$name = $value;
        }
        if($this->autoconnect)
            $this->connect();
    }
    
    public function connect()
    {
        if($this->_connection)
            return;
        
        $options = $this->options;
        if(!isset($options[PDO::MYSQL_ATTR_INIT_COMMAND]))
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";

        $dsn = "mysql:host={$this->host};dbname={$this->dbname}";
        $connection = new PDO($dsn, $this->username, $this->password, $options);
        
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->_connection = $connection;
    }
    
    public function disconnect()
    {
        $this->_connection = null;
    }
    
    public function execute($sql, $params=array())
    {
        $statement = $this->_connection->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }
    
    public function getLastInsertedId()
    {
        return $this->_connection->lastInsertId;
    }
    
    public function query($sql, $params=array())
    {
        
    }
    
    public function queryRow($sql, $params=array())
    {
        
    }
    
    public function queryColumn($sql, $params=array())
    {
        
    }
    
    public function queryScalar($sql, $params=array())
    {
        
    }
    
    public function insert($table, $attributes)
    {
        $columns = array();
        $placeholders = array();
        $params = array();
        foreach($attributes as $column=>$value)
        {
            $columns[] = "`$column`";
            $placeholders[] = ":$column";
            $params[":$column"] = $value;
        }
        $columns = implode(', ', $columns);
        $placeholders = implode(', ', $placeholders);
        $sql = "INSERT INTO `$table`($columns) VALUES($placeholders)";
        $this->execute($sql, $params);
        return $this->getLastInsertedId();
    }
    
    public function update($table, $attributes, $criteria=null)
    {
        $params = array();
        $set = array();
        foreach($attributes as $column=>$value)
        {
            $set[] = "`$column` = :$column";
            $params[":$column"] = $value;
        }
        $set = implode(', ', $set);
        $sql = "UPDATE `$table` SET $set".$this->criteriaToSql($criteria);
        return $this->execute($sql, $params);
    }
    
    public function delete($table, $criteria=null)
    {
        $sql = "";
    }
    
    private function criteriaToSql($criteria)
    {
        $sql = "";
        if(is_string($criteria))
            $criteria = array('condition'=>$criteria);
        if(is_array($criteria))
        {
            if(isset($criteria['conditions']))
            {
                $condition = '('.implode(') AND (', $criteria['conditions']).')';
                if(isset($criteria['condition']))
                {
                    $condition .= " AND (".$criteria['condition'].")";
                }
                $sql .= " WHERE ".$condition;
            }
            else if(isset($criteria['condition']))
                $sql .= " WHERE ".$criteria['condition'];
            
            if(isset($criteria['order']))
                $sql .= " ORDER BY ".$criteria['order'];
            if(isset($criteria['group']))
                $sql .= " GROUP BY ".$criteria['group'];
            if(isset($criteria['count']))
            {
                if(isset($criteria['offset']))
                    $sql .= " LIMIT ".$criteria['offset'].",".$criteria['count'];
                else
                    $sql .= " LIMIT ".$criteria['count'];
            }
        }
        return $sql;
    }
}