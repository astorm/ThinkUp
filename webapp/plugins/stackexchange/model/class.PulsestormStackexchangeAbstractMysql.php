<?php
abstract class PulsestormStackexchangeAbstractMysql extends PDODAO
{    
    abstract protected function _getFields();
    abstract protected function _getTableName();

    protected $_network;
    public function getNetwork()
    {
        return $this->_network;
    }
    public function setNetwork($value)
    {
        $this->_network = $this->_sanatizeForTableName($value);
        return $this;
    }
    
    /**
    * Assumes first field is primary key
    * override in child class if this isn't true
    */    
    public function getPrimaryKey()
    {
        $fields = $this->_getFields();
        return $fields[0];
    }
    
    public function setInstance($instance)
    {
        $this->_instance = $instance;
        return $this;
    }
    public function getInstance()
    {
        return $this->_instance;
    }
    
    protected function _sanatizeForTableName($name)
    {
        return preg_replace('%[^a-z0-9_]%xis','',$name);
    }
    
    static protected $_tableExists=array();
    public function __construct($cfg_vals=null)
    {
        error_reporting(E_ALL | E_STRICT);    
        ini_set('display_errors','1');     
        
        $network='stackoverflow';
        $this->setNetwork($network);
        $this->initTable();
        parent::__construct($cfg_vals);
    }

    public function initTable()    
    {
        if(!array_key_exists($this->_getTableName(), self::$_tableExists))
        {
            self::$_tableExists[$this->_getTableName()] = false;
        }
        
        if(!self::$_tableExists[$this->_getTableName()])
        {
            try
            {
                $this->createTable();
            }
            catch(Exception $e)
            {
                // var_dump($e->getMessage());
                self::$_tableExists[$this->_getTableName()] = true;
            }
        }          
	return $this;
    }
    
    protected function _getIdField()
    {
        $fields = $this->_getFields();
        return array_shift($fields);
    }
    
    protected function _forParam($string)
    {
        return ':'  . $string;
    }
    
    protected function _castAsInt($int)
    {
        return (int) $int;
    }
    
    protected function _getFieldsNamedParameters()
    {
        return array_map(array($this,'_forParam'), $this->_getFields());
    }

    protected function _processValue($object, $field)
    {
        switch($field)
        {
            case 'tags':
                $value = isset($object->{$field}) ? $object->{$field} : '';
                if(!$value)
                {
                    $value = array();
                }
                return array(implode(',',$value), $field);
            case 'owner_id':                
                DAOFactory::getDAO('PulsestormStackexchangeUser')
                ->setNetwork($this->getNetwork())
                ->initTable()
                ->insertOrUpdateUserFromParent($object,'owner');                
                $value = isset($object->owner->user_id) ? $object->owner->user_id : '';
                return array($value, $field);
            case 'reply_to_user_id':   
                DAOFactory::getDAO('PulsestormStackexchangeUser')
                ->setNetwork($this->getNetwork())
                ->initTable()
                ->insertOrUpdateUserFromParent($object, 'reply_to_user');                                                
                $value = isset($object->reply_to_user->user_id) ? $object->reply_to_user->user_id : '';                
                return array($value, $field);            
            case 'last_edit_date':
                $value = isset($object->last_edit_date) ? $object->last_edit_date : '';
                if($value)
                {
                    $value = date('Y-m-d H:i:s',$value);
                }
                return array($value, $field);                
            case 'creation_date':
                $value = isset($object->creation_date) ? $object->creation_date : '';
                if($value)
                {
                    $value = date('Y-m-d H:i:s',$value);
                }
                return array($value, $field);
            case 'last_activity_date':
                $value = isset($object->last_activity_date) ? $object->last_activity_date : '';
                if($value)
                {
                    $value = date('Y-m-d H:i:s',$value);            
                }
                return array($value, $field); 
            case 'community_owned_date':
                $value = isset($object->community_owned_date) ? $object->community_owned_date : '';
                if($value)
                {
                    $value = date('Y-m-d H:i:s',$value);            
                }
                return array($value, $field); 
            default:   
                $value = isset($object->{$field}) ? $object->{$field} : '';            
                return array($value, $field);;
        }
    }
    
    protected function _buildParamsFromObject($object)
    {
        $params = array();        
        foreach($this->_getFields() as $field)
        {                        
            list($value, $field) = $this->_processValue($object, $field);            
            $params[':'.$field]  = $value;
        }
        return $params;
    }
    
    public function insertObject($object)
    {
        $params = $this->_buildParamsFromObject($object);        
        $sql = 'INSERT INTO ' . $this->_getTableName() .
        '('.implode(',',$this->_getFields()).')
        VALUES
        ('.implode(',',$this->_getFieldsNamedParameters()).')';        
        $this->execute($sql,$params);             
    }
    
    public function updateObject($object)
    {
        $params = $this->_buildParamsFromObject($object);            
        //remove anything that's blank to avoid data loss
        $fields_to_use = array();
        foreach($this->_getFields() as $field)
        {
            if($params[':'.$field])
            {
                $fields_to_use[] = $field;
            }
            else
            {
                unset($params[':'.$field]);
            }
        }
        
        $sql  = 'UPDATE ' . $this->_getTableName() . ' set ';
        $sets = array();
        foreach($fields_to_use as $field)
        {
            $sets[] = $field.'=:'.$field;
            $sets[] = ',';
        }
        array_pop($sets);
        $sql .= implode($sets) . ' ' .
        'WHERE ' . $this->_getIdField() . '=:' .$this->_getIdField();
        
        $this->execute($sql, $params);       
    }
    
    public function insertOrUpdate($object)
    {        
        $start_time = microtime();                
        
        try
        {
            $this->insertObject($object);
        }
        catch(Exception $e)
        {            
            $this->updateObject($object);       
        }
        
        $this->log("Time to Finish: " . (string) (microtime() - $start_time));
    }      
    
    abstract protected function createTable();
        
    public function log($message)
    {
        //file_put_contents('/tmp/test.log',"$message\n",FILE_APPEND);       
    }
    
    public function getResultSetAll()
    {
        $sql    = 'SELECT * FROM ' . $this->_getTableName();
        $result = $this->execute($sql);
        return $result;
    }
    public function getAll($by=false)
    {
        $result = $this->getResultSetAll();
        $rows = $this->getDataRowsAsArrays($result);
        $tmp  = array();
        if(!$by)
        {
            return $rows;
        }
        
        foreach($rows as $row)
        {
            $tmp[$row[$by]] = $row;
        }

        return $tmp;
    }    
    
    public function getResultSetBy($field, $value)
    {
        $field = preg_replace('%[^a-z0-9_]six%','',$field);
        
        if(!is_array($value))
        {
            $sql = 'SELECT * FROM ' . $this->_getTableName() . ' ' .
            'WHERE ' . $field . ' = ?';
            return $this->execute($sql, array(1=>$value));    
        }

        //treat array paramaters as IN statments

        //Whether 'tis nobler in the mind to suffer            
        //The slings and arrows of PDO
        array_unshift($value,null); //PDO uses 1 based arrays
        unset($value[0]);           //so we get rid of element 1
        
        //and then create a list of paramater ?
        $str_param = join(',', array_fill(0, count($value), '?'));
        
        $sql = 'SELECT * FROM ' . $this->_getTableName() . ' ' .
        'WHERE ' . $field . ' IN('.$str_param.')';                    
        
        return $this->execute($sql, $value);            
    }
    
    public function getAllBy($field, $value)
    {
        $results = $this->getResultSetBy($field, $value);
        return $this->getDataRowsAsArrays($results);
    }
}
