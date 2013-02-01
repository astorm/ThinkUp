<?php
/**
* Created to provide method to set last_crawl_date to null
*/
class PulsestormStackexchangeInstanceMysql extends InstanceMySQLDAO
{
    public function resetLastRun($id) {
        $q  = "UPDATE ".$this->getTableName()." ";
        $q .= "SET crawler_last_run = '2006-01-01' ";
        $q .= "WHERE id = :id ";
        $q .= "LIMIT 1 ";
        $vars = array(
            ':id'=>$id
        );
        if ($this->profiler_enabled) Profiler::setDAOMethod(__METHOD__);
        $ps = $this->execute($q, $vars);
        return $this->getUpdateCount($ps);
    }

}