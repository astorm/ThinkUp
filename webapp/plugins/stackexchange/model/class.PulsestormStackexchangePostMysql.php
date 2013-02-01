<?php
class PulsestormStackexchangePostMysql extends PulsestormStackexchangeAbstractMysql
{
    protected function _getFields()
    {
        return array(
        'post_id',
        'post_type',
        'body',
        'owner_id',
        'creation_date',
        'last_activity_date',
        'score',
        'link',
        'last_edit_date',
        );
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_' . $this->getNetwork() . '_api_post';
    }
    
    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `post_id` int(10) unsigned NOT NULL auto_increment,
  `post_type` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `creation_date` datetime NOT NULL,
  `last_activity_date` datetime NOT NULL,
  `score` int(11) NOT NULL,
  `link` text NOT NULL,
  `last_edit_date` datetime NOT NULL,
  PRIMARY KEY  (`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
SQL;
        $this->execute($sql);    
    }
    
}