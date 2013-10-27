<?php
class PulsestormStackexchangeAjaxController
{
    protected $_action;
    public function setAction($action)
    {
        $this->_action = $action;
        return $this;
    }
    
    public function dispatch()
    {
        PulsestormInjectdao::addDaos();
        call_user_func(array($this, $this->_action.'Action'));
    }
    
    public function processRawAction()
    {        
        $crawler    = new stackexchangeCrawler;          
        $dao        = DAOFactory::getDao('InstanceDAO');
        $instances  = $dao->getAllInstances('DESC', true, 'stackexchange');
        $instance   = array_shift($instances);        
        for($i=0;$i<1;$i++)
        {
            $crawler->setSilent(true);
            $crawler->parseSingleRowIntoTables($instance);
        }    

        $dao = DAOFactory::getDAO('PulsestormStackexchangeRaw');
        $rows = $dao->getUnprocessedRows();
        $o = new stdClass;
        $o->rowsLeft = array();

        foreach($rows as $row)
        {
            $o->rowsLeft[] = $row['pulsestorm_stackexchange_raw_id'];
        }
        
        $this->_output($o);
    }
    
    protected function _output($object)
    {
        header('Content-Type: text/javascript');
        echo json_encode($object);
        exit;
    }
}