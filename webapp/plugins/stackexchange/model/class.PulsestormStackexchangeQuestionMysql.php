<?php
class PulsestormStackexchangeQuestionMysql extends PulsestormStackexchangeAbstractMysql
{
    protected function _getFields()
    {
        return array(
            'question_id',
            'creation_date',
            'last_activity_date',
            'score',
            'answer_count',
            'body',
            'title',
            'tags',
            'view_count',
            'owner_id',
            'link',
            'is_answered',
            'last_edit_date',
            'accepted_answer_id',
            'closed_date',
            'closed_reason',
            'protected_date',
            'community_owned_date',
            'bounty_amount',
        );
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_' . $this->getNetwork() . '_api_question';
    }
    
    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `question_id` int(10) unsigned NOT NULL auto_increment,
  `creation_date` datetime NOT NULL,
  `last_activity_date` datetime NOT NULL,
  `score` int(11) NOT NULL,
  `answer_count` int(11) NOT NULL,
  `body` text NOT NULL,
  `title` text NOT NULL,
  `tags` text,
  `view_count` int(11) NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `link` text NOT NULL,
  `is_answered` tinyint(4) NOT NULL,
  `last_edit_date` datetime NOT NULL,
  `accepted_answer_id` int(10) unsigned default NULL,
  `closed_date` datetime default NULL,
  `closed_reason` varchar(255) default NULL,
  `protected_date` datetime default NULL,
  `community_owned_date` datetime default NULL,
  `bounty_amount` int(11) default NULL,
  PRIMARY KEY  (`question_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
SQL;
        $this->execute($sql);    
    }    
}