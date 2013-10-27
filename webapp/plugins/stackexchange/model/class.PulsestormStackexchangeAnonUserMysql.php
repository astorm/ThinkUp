<?php
class PulsestormStackexchangeAnonUserMysql extends PulsestormStackexchangeAbstractMysql //PDODAO
{
    protected function _getFields()
    {
        return array(
            'pulsestorm_stackexchange_api_anon_user_id',
            'display_name',
            'user_type',
        );
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_' . $this->getNetwork() . '_api_anon_user';
    }    

    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `pulsestorm_stackexchange_api_anon_user_id` int(10) unsigned NOT NULL auto_increment,
  `display_name` text,
  `user_type` varchar(255) default NULL,
  PRIMARY KEY  (`pulsestorm_stackexchange_api_anon_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
SQL;
        $this->execute($sql);    
    }    
}
