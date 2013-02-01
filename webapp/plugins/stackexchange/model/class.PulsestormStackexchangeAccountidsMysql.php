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
  KEY `INDEX_ALL` (`account_id_stackexchange`,`account_id_network_site`,`stackexchange_network`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
SQL;

        $this->execute($sql);
    }    

    public function updateIdsFromApi($account_ids_stackexchange=false)
    {
        $o = new stackexchangeCrawler;
        $account_ids_stackexchange = is_array($account_ids_stackexchange) ? $account_ids_stackexchange : array($account_ids_stackexchange);
        if(count($account_ids_stackexchange) > 100)
        {
            throw new Exception("Chunk array");
        }
        
        //grab ALL account information
        $all_items = $o->getAllAccountInformationFromAccountIds($account_ids_stackexchange);
        
        //main update loop
        $sites_by_url   = DAOFactory::getDao('PulsestormStackexchangeSites')->getAll($by='site_url');
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
}