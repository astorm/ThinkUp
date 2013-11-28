<?php
class PulsestormStackexchangeAccountidsMysql extends PulsestormStackexchangeAbstractMysql
{
    protected function _getFields()
    {
        return array(
            'pulsestorm_stackexchange_accountids_id',
            'account_id_stackexchange',
            'account_id_network_site',
            'stackexchange_network',        
        );
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_stackexchange_accountids';
    } 

    public function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `pulsestorm_stackexchange_accountids_id` int(11) NOT NULL auto_increment,
  `account_id_stackexchange` int(11) default NULL,
  `account_id_network_site` int(11) default NULL,
  `stackexchange_network` varchar(255) default NULL,
  PRIMARY KEY  (`pulsestorm_stackexchange_accountids_id`),
  UNIQUE KEY `UNIQUE_ALL` (`account_id_stackexchange`,`account_id_network_site`,`stackexchange_network`),
  KEY `INDEX_ALL` (`account_id_stackexchange`,`account_id_network_site`,`stackexchange_network`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
SQL;

        $this->execute($sql);
    }    

    public function updateFromUsersTable()
    {            
        $sql = 'SELECT user_id FROM #prefix#users
        where network="stackexchange"';
        $rows = $this->getDataRowsAsArrays($this->execute($sql));
        $ids = array();
        foreach($rows as $row)
        {
            $ids[] = $row['user_id'];
        }

        $this->updateIdsFromApi($ids);    
    }

    protected function _chunkArray(&$thing_ids,$number)
    {
        $chunks_100   = array();
        $index = count($thing_ids);
        for($i=0;$i<$index;$i+=$number)
        {
            $chunks_100[] = array_slice($thing_ids,$i,$number);
        }    
        return $chunks_100;
    }
    
    public function updateIdsFromApi($account_ids_stackexchange=false)
    {
        $o = new stackexchangeCrawler;
        $account_ids_stackexchange = is_array($account_ids_stackexchange) ? $account_ids_stackexchange : array($account_ids_stackexchange);
        $chunked = $this->_chunkArray($account_ids_stackexchange,10);
        
        foreach($chunked as $account_ids_stackexchange)
        {
            //grab ALL account information
            $all_items = $o->getAllAccountInformationFromAccountIds($account_ids_stackexchange);
        
            //main update loop
            $sites_by_url   = DAOFactory::getDao('PulsestormStackexchangeSites')->getAll($by='site_url');
            
            //empty for some reason — force a refresh even though it's been less than a day
            if(count($sites_by_url) == 0)
            {
                DAOFactory::getDAO('PulsestormStackexchangeSites')->fetchAndUpdate();
                $sites_by_url   = DAOFactory::getDao('PulsestormStackexchangeSites')->getAll($by='site_url');
            }
            
            $dao            = DAOFactory::getDao('PulsestormStackexchangeAccountids');
            foreach($all_items as $item)
            {
                $site_code = $sites_by_url[$item->site_url]['api_site_parameter'];            
                $row = $dao->getSingleItem($item->account_id, $item->user_id, $site_code);
                if($row)
                {
                    continue;
                }
                
                $info = array_fill_keys($this->_getFields(), null);
                $info['account_id_stackexchange']   = $item->account_id; 
                $info['account_id_network_site']    = $item->user_id;
                $info['stackexchange_network']      = $site_code;
                unset($info['pulsestorm_stackexchange_accountids_id']);
                
                $dao->insertOrUpdate((object)$info);
            }              
        }
    }  
    
    public function getByAccountId($account_id)
    {
        $sql = 'SELECT * FROM ' . $this->_getTableName() . ' ' .
        'WHERE  account_id_stackexchange = ?'; 
        $params = array($account_id);
        array_unshift($params, '');
        unset($params[0]);    
        $rows = $this->getDataRowsAsArrays($this->execute($sql, $params));
        return $rows;
    }
    
    public function getSingleItem($account_id, $user_is, $site_code)
    {
        $sql = 'SELECT * FROM ' . $this->_getTableName() . ' ' .
        'WHERE  account_id_stackexchange = ? 
            AND account_id_network_site  = ? 
            AND stackexchange_network    = ?'; 
        $params = array($account_id, $user_is, $site_code);
        array_unshift($params, '');
        unset($params[0]);
        $rows = $this->getDataRowsAsArrays($this->execute($sql, $params));
        if((boolean)$rows)
        {
            return $rows;
        }
        
        $item = array_shift($rows);
        return $item;
    }

    public function getAccountIdFromUserAndNetwork($user_id,$network)
    {
        $sql = "SELECT account_id_stackexchange FROM " . $this->_getTableName() . " " .
        "WHERE account_id_network_site = ? AND stackexchange_network = ?";
        
        $params = array(
            1=>$user_id,
            2=>$network);
        
        $rows = $this->getDataRowsAsArrays($this->execute($sql, $params));
        $row  = array_shift($rows);
        return $row['account_id_stackexchange'];
    }
    
    public function updateAccountAndUserId($o)
    {
        var_dump($o);
        exit;
    }

    /**
    * If primary key is missing, query for it.  This makes an assumption
    * that a mismatched field count means there's a missing key. Should
    * probably be fixed at the abstract class level
    * @todo fix me
    */
    protected function _addMissingPrimaryKeyToObject($object)
    {        
        $count_fields = count($this->_getFields());
        $count_vars   = count(get_object_vars($object));
        if($count_fields != $count_vars)
        {
            $field_primary_key = 'pulsestorm_stackexchange_accountids_id';
            $sql = 'SELECT ' . $field_primary_key . ' FROM ' . $this->_getTableName() . ' ' .
            'WHERE      
                account_id_stackexchange    = ? 
                AND account_id_network_site = ? 
                AND stackexchange_network   = ?';

            $params = array(
                1=>$object->account_id_stackexchange,
                2=>$object->account_id_network_site,
                3=>$object->stackexchange_network,
            );
            $rows = $this->getDataRowsAsArrays($this->execute($sql, $params));
            $row  = array_shift($rows);   
            if(count($rows) != 0)
            {
                throw new Exception('Unique index missing from tu_pulsestorm_stackexchange_accountids');
            }
            $object->{$field_primary_key} = $row[$field_primary_key];
        }
        return $object;
    }
    
    public function insertOrUpdate($object)    
    {
        $object = $this->_addMissingPrimaryKeyToObject($object);
        return parent::insertOrUpdate($object);    
    }
}