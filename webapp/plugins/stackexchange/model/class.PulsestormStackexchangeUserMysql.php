<?php
class PulsestormStackexchangeUserMysql extends PulsestormStackexchangeAbstractMysql //PDODAO
{
    protected function _getFields()
    {
        return array(
            'user_id',
            'display_name',
            'reputation',
            'user_type',
            'profile_image',
            'link',
            'accept_rate',
        );
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_' . $this->getNetwork() . '_api_user';
    }

    public function insertUserFromParent($parent, $user_field)
    {
        if(!isset($parent->{$user_field}))
        {
            return;
        }        
        $user = $parent->{$user_field};        
        if(!isset($user->user_id))
        {
            return $this->_insertOrUpdateAnonUser($user);
        }
        
        return $this->insertObject($user);    
    }
    
    public function insertOrUpdateUserFromParent($parent, $user_field)
    {
        if(!isset($parent->{$user_field}))
        {
            return;
        }        
        $user = $parent->{$user_field};        
        if(!isset($user->user_id))
        {
            return $this->_insertOrUpdateAnonUser($user);
        }
        
        return $this->insertOrUpdate($user);
    }
    
    public function insertOrUpdate($user)
    {        
        $o          = new stdClass;
        $o->user    = $user;
        PulsestormEvents::dispatchEvent('stackexchange_user_update', $o);         
        
        return parent::insertOrUpdate($user);
    }
    
    public function syncToUsersTable($observer)
    {
        //don't need, but leaving this as an example of an observer
    }
    
    public function getAllIds()
    {
        $sql = 'select user_id FROM ' . $this->_getTableName();
        $this->execute($sql);
        
        $rows = $this->getDataRowsAsArrays($this->execute($sql));
        $tmp = array();
        foreach($rows as $row)
        {
            $tmp[] = $row['user_id'];
        }
        return $tmp;
    }
    
    protected function _insertOrUpdateAnonUser($object)
    {
        $o = DAOFactory::getDao('PulsestormStackexchangeAnonUser');
        return $o->insertOrUpdate($object);
    }
    
    protected function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `display_name` text,
  `reputation` int(11) NOT NULL,
  `user_type` varchar(255) default NULL,
  `profile_image` text,
  `link` text NOT NULL,
  `accept_rate` int(11) NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
SQL;
        $this->execute($sql);    
    }
    
}
