<?php
class PulsestormStackexchangeCommentMysql extends PulsestormStackexchangeAbstractMysql
{
    protected function _getFields()
    {
        return array(
        'comment_id',
        'post_id',
        'creation_date',
        'score',
        'edited',
        'body',
        'owner_id',
        'reply_to_user_id');
    }
    
    protected function _getTableName()
    {
        return '#prefix#pulsestorm_' . $this->getNetwork() . '_api_comment';
    } 
    
    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `comment_id` int(10) unsigned NOT NULL auto_increment,
  `post_id` int(10) unsigned NOT NULL,
  `creation_date` datetime NOT NULL,
  `score` int(11) NOT NULL,
  `edited` tinyint(4) NOT NULL,
  `body` mediumtext NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `reply_to_user_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
SQL;
        $this->execute($sql);    
    }
}