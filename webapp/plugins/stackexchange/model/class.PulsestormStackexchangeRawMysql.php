<?php
class PulsestormStackexchangeRawMysql extends PDODAO
{
    protected function _castAsInt($int)
    {
        return (int) $int;
    }

    static protected $_tableExists=false;
    public function __construct($cfg_vals=null)
    {
        error_reporting(E_ALL | E_STRICT);    
        ini_set('display_errors','1');
        if(!self::$_tableExists)
        {
            try
            {
                $this->createTable();
            }
            catch(Exception $e)
            {
                // var_dump($e->getMessage());
                self::$_tableExists = true;
            }
        }
        
        parent::__construct($cfg_vals);
    }
    
    public function insertRawJsonResponse($site,$name, $content)
    {
        $sql = 'INSERT INTO #prefix#pulsestorm_stackexchange_raw
        (site, name, contents, processed, downloaded_date)
        VALUES
        (:site,:name,:contents,:processed,:downloaded_date)';        
        $rs = $this->execute($sql, array(
            ':site'             =>$site, 
            ':name'             =>$name, 
            ':contents'         =>$content, 
            ':processed'        =>'0', 
            ':downloaded_date'  =>date("Y-m-d H:i:s")
        ));   
        return $this->getInsertId($rs);
    }
    
    public function countUnprocessed()
    {
        $sql = 'SELECT count(*) as count FROM #prefix#pulsestorm_stackexchange_raw
        where processed = 0';        
        $data = $this->getDataRowsAsArrays($this->execute($sql));    
        $row  = array_pop($data);
        return $row['count'];
    }
    
    public function getRows()
    {
        $sql = 'SELECT * FROM #prefix#pulsestorm_stackexchange_raw';        
        return $this->getDataRowsAsArrays($this->execute($sql));
    }
    
    public function getRowsWithIds($ids)
    {
        $ids = array_map(array($this,'_castAsInt'),$ids);
        $ids = implode(',',$ids);
        $sql = 'SELECT * FROM #prefix#pulsestorm_stackexchange_raw
        WHERE pulsestorm_stackexchange_raw_id IN ('.$ids.')';        
        return $this->getDataRowsAsArrays($this->execute($sql));
    }
    
    public function getUnprocessedRows()
    {
        $sql = 'SELECT * FROM #prefix#pulsestorm_stackexchange_raw WHERE processed = 0';        
        return $this->getDataRowsAsArrays($this->execute($sql));    
    }
    
    public function markAsProcessed($row_id)
    {
        $sql = 'UPDATE #prefix#pulsestorm_stackexchange_raw 
        set processed = 1 
        WHERE pulsestorm_stackexchange_raw_id = :row_id';
        $this->execute($sql, array(':row_id'=>$row_id));
    }
    
    protected function _getTableName()
    {
        return '#prefix#pulsestorm_stackexchange_raw';
    }
    
    public function createTable()
    {
        $sql = <<<SQL
        CREATE TABLE `{$this->_getTableName()}` (
          `pulsestorm_stackexchange_raw_id` int(11) NOT NULL auto_increment,
          `site` varchar(255) NOT NULL,
          `name` varchar(255) NOT NULL default '',
          `contents` mediumtext NOT NULL,
          `processed` tinyint(4) NOT NULL,
          `downloaded_date` datetime NOT NULL,
          PRIMARY KEY  (`pulsestorm_stackexchange_raw_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;                
SQL;

        $this->execute($sql);
    }    
}
