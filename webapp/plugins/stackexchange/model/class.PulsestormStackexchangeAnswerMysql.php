<?php
class PulsestormStackexchangeAnswerMysql extends PulsestormStackexchangeAbstractMysql //PDODAO
{
    protected function _getFields()
    {
        return array(
        'question_id',
        'answer_id',
        'creation_date',
        'last_activity_date',
        'score',
        'is_accepted',
        'body',
        'owner_id',
        'last_edit_date',
        'community_owned_date');
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_' . $this->getNetwork() . '_api_answer';
    }

    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `question_id` int(10) unsigned NOT NULL,
  `answer_id` int(10) unsigned NOT NULL,
  `creation_date` datetime NOT NULL,
  `last_activity_date` datetime NOT NULL,
  `score` int(11) NOT NULL,
  `is_accepted` tinyint(4) NOT NULL,
  `body` text NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `last_edit_date` datetime NOT NULL,
  `community_owned_date` datetime default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8        
SQL;
        $this->execute($sql);    
    }    
}
