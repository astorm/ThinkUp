<?php
/**
* Handles saving account information
*
* The "StackExchange account" vs. Network User Account problem
* is complicated, so this object isn't a simple representation of
* a stack exchange account.  It's selected fields from network
* user accounts. 
*
* How to grab and sync additional fields
*
* - add database column to tu_pulsestorm_stackexchange_accounts
* - add column PulsestormStackexchangeAccountMysql::_getFields
* - in PulsestormStackexchangeCrawlSyncMysql::_updateUsersFromStackExchangeAccounts populate user object
*/
class PulsestormStackexchangeAccountMysql extends PulsestormStackexchangeAbstractMysql
{

    public function insertOrUpdate($object)
    {
        $object->badge_counts = json_encode($object->badge_counts);
        return parent::insertOrUpdate($object);
    }
    
    protected function _getFields()
    {
        return array(
            'account_id',
            'display_name',
            'profile_image',
            'link',
        );
    }
    
    protected function _getTableName()
    {
        return '#prefix#pulsestorm_stackexchange_accounts';
    }

    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `account_id` int(11) NOT NULL auto_increment,
  `display_name` text NOT NULL,
  `profile_image` text NOT NULL,
  `link` text NOT NULL,
  PRIMARY KEY  (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
        $this->execute($sql);
    
    }
}