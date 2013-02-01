<?php
class PulsestormStackexchangeSitesMysql extends PulsestormStackexchangeAbstractMysql
{
    protected function _getFields()
    {
        return array(
        'api_site_parameter',
        'site_type',
        'name',
        'logo_url',
        'site_url',
        'audience',
        'icon_url',
        'aliases',
        'site_state',
        'styling',
        'launch_date',
        'favicon_url',
        'related_sites',
        'markdown_extensions',
        'high_resolution_icon_url',);
    }

    protected function _getTableName()
    {
        return '#prefix#pulsestorm_stackexchange_sites';
    } 

    public function createTable()
    {
        $sql = <<<SQL
CREATE TABLE `{$this->_getTableName()}` (
  `api_site_parameter` varchar(255) NOT NULL default '',
  `site_type` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `logo_url` tinytext,
  `site_url` tinytext,
  `audience` tinytext,
  `icon_url` tinytext,
  `aliases` tinytext,
  `site_state` varchar(255) default NULL,
  `styling` tinytext,
  `launch_date` int(11) unsigned default NULL,
  `favicon_url` tinytext,
  `related_sites` tinytext,
  `markdown_extensions` tinytext,
  `high_resolution_icon_url` tinytext,
  PRIMARY KEY  (`api_site_parameter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SQL;

        $this->execute($sql);
    }    

    public function fetchAndUpdate($page=1)
    {
        $o = new stackexchangeCrawler;
        $page = (int) $page;
        $dao = DAOFactory::getDao('PulsestormStackexchangeSites');
            
        $o->fetchAndUpdateSiteList($page, $dao);            
        return true;
    }
    
    public function getApiCodesByUrls($urls)
    {
        //PDO, from hell's heart I stab at thee
        array_unshift($urls, 'null');
        unset($urls[0]);
        $list   = join(',', array_fill(0, count($urls), '?'));
        $sql    = 'SELECT api_site_parameter FROM ' . $this->_getTableName() . ' ' .
        'WHERE site_url IN('.$list.')';
        
        $result = $this->execute($sql,$urls);
        $rows = $this->getDataRowsAsArrays($result);
        $codes = array();
        foreach($rows as $array)
        {
            $codes[] = array_pop($array);
        }
        return $codes;
        //return array_values($rows);
    }
}