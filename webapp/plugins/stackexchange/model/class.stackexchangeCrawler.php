<?php
/**
 * webapp/plugins/stackexchange/model/class.stackexchangeCrawler.php
 *
 * Copyright (c) 2012 Pulse Storm LLC. 
 * 
 * @author Alan Storm
 * @copyright 2012 Pulse Storm LLC. 
 */
 
/**
* The Crawler is mid-migration away from PulsestormStackexchangeCrawler/fetch.php, 
* so please pardon it's awkward mullet phase and duplicated functionality
*/
class stackexchangeCrawler {

    protected $_insertedRawIds  = array();
    protected $_fromDate        = false;

    //prevents logger output, the @flush interferes with some output buffering
    protected $_silent          = false;
    protected $_consumerKey;
    public function __construct() {
        $plugin_option_dao  = DAOFactory::GetDAO('PluginOptionDAO');        
        $options            = (object) $plugin_option_dao->getOptionsHash('stackexchange',true);        
        
        if(isset($options->oauth_consumer_key))
        {
            $this->_consumerKey = $options->oauth_consumer_key->option_value;    
        }        
    }

    public function getSilent()
    {
        return $this->_silent;
    }
    
    public function setSilent($value)
    {
        $this->_silent = $value;
    }
    
    public function resetInsertedRawIds()
    {
        $this->_insertedRawIds = array();
    }

    public function fetchAllUserJson($access_token)
    {
        require_once THINKUP_WEBAPP_PATH . '/plugins/stackexchange/extlib/fetch.php';            
        $o = new PulsestormStackexchangeCrawler;          

        $consumer_key       = $this->_consumerKey;                
        $o->setAccessToken($access_token);
        $o->setConsumerKey($consumer_key);  //$o->setConsumerKey('LN4BTsE7W3qUkbBiSz9Vhw((');                
        $o->setJsonContainter($this);             
        
        $codes = $this->_getAllUserSiteCodes($access_token);
        $o->taskFetchAllUserInformation($codes);        
    }
    
    protected function _getAllUserSiteCodes($access_token)
    {
        $api_accessor = new stackexchangeAPIAccessor;
        $options = DAOFactory::GetDAO('PluginOptionDAO')->getOptionsHash('stackexchange');
        $urls    = $api_accessor->getAssociatedSiteUrls($options, $access_token);
        $codes   = DAOFactory::getDao('PulsestormStackexchangeSites')->getApiCodesByUrls($urls);
        return $codes;
    }
    
    public function setFromDate($date)
    {
        $this->_fromDate = $date;
        return $this;
    }
    
    public function getFromDate()
    {
        if(!$this->_fromDate)
        {
            throw new Exception("No From Date Set on StackExchange crawler -- bailing");
        }
        return $this->_fromDate;
    }
    
    public function saveJson($name, $content, $params, $save_path)
    {
        $name   = $name . implode('_',$params);
        $name   = preg_replace('%[^a-z_0-9]%i','',$name)  . '.json';        
        $o      = DAOFactory::getDAO('PulsestormStackexchangeRaw');        
        $id     = $o->insertRawJsonResponse($params['site'],$name, $content);
        $this->_insertedRawIds[] = $id;
    }
    
    public function log($message, $type='logUserSuccess')
    {        
        if($this->getSilent())
        {
            return;
        }        
        $logger = Logger::getInstance();        
        call_user_func(array($logger, $type),$message,__METHOD__);
    }
    
    public function logError($message)
    {
        $this->log($message, 'logUserError');
    }
    
    public function parseSingleRowIntoTables($instance)
    {
        $dao  = DAOFactory::getDAO('PulsestormStackexchangeRaw');        
        $rows = $dao->getUnprocessedRows();
        $rows = array_slice($rows, 0, 1);
        $this->_processUnprocessedRows($rows, $dao);        
    }
    
    public function parseJsonIntoTables($instance)
    {    
        $this->log("Starting JSON Parse from Tables");
        $dao = DAOFactory::getDAO('PulsestormStackexchangeRaw');        
        $rows = $dao->getUnprocessedRows();
        $this->_processUnprocessedRows($rows, $dao);        
        $this->log("Finished parseJsonIntoTables");
    }
    
    public function processUnprocessedRows($rows, $dao)
    {
        return $this->_processUnprocessedRows($rows, $dao);
    }
    
    protected function _processUnprocessedRows($rows, $dao)
    {
        foreach($rows as $row)
        {
            $o = json_decode($row['contents']);
            if(!is_object($o))
            {
                $this->log("Non-json row found: ".$row['pulsestorm_stackexchange_raw_id']);
                continue;
            }
            $this->log("Processing: " . $row['name']);
            $o->stackexchange_network = $row['site'];
            $this->processItems($o);
            $dao->markAsProcessed($row['pulsestorm_stackexchange_raw_id']);
        }
    
    }
    
    public function processItems($object)
    {           
        foreach($object->items as $item)
        {            
            $this->processItem($item,$object->stackexchange_network);
        }        
    }
    
    public function processItem($object, $network)
    {
        $type = self::getObjectType($object);
        $o    = DAOFactory::getDAO($type);        
        $o->setNetwork($network)->initTable();
        $o->insertOrUpdate($object);
    }
    
    static public function getObjectType($object)
    {
        $vars = get_object_vars($object);
        $keys = array_keys($vars);
        if(count($keys) < 1)
        {
            return 'unknown-object-type-' . __METHOD__;
        }
        
        if($keys[0] == 'question_id' && $keys[1] == 'answer_id')
        {
            return 'PulsestormStackexchangeAnswer';
        }
        
        if($keys[0] == 'comment_id')
        {
            return 'PulsestormStackexchangeComment';
        }

        if($keys[0] == 'question_id')
        {
            return 'PulsestormStackexchangeQuestion';
        }

        if($keys[0] == 'post_id')
        {
            return 'PulsestormStackexchangePost';
        }
        
        if(in_array('user_id',$keys) && in_array('account_id',$keys))
        {
            return 'PulsestormStackexchangeAccount';
        }
        
        return 'unknown-object-type-' . __METHOD__ . ' :: ' . implode('_', $keys);
    }
    
    public function getPropertiesWithGlob($glob)
    {
        $glob = basename($glob);
        if($glob[strlen($glob)-1] != '*')
        {
            throw new Exception("Unexpected Glob: $glob");
        }        
        $glob = rtrim($glob,'*');
        $o    = DAOFactory::getDAO('PulsestormStackexchangeRaw');
        
        
        
        #$rows = $o->getRows();
        $rows = $o->getRowsWithIds($this->_insertedRawIds);        
        
        $data = array();
        foreach($rows as $row)
        {
            $data[$row['name']] = $row['contents'];
        }
        if(empty($glob))
        {
            return $data;
        }
        
        $strings = array();
        foreach($data as $key=>$value)
        {
            if(strpos($key, $glob) !== 0)
            {
                continue;
            }
            $strings[] = $value;
        }        
        return $strings;
    }
    
    public function finishCrawl($owner_instance)
    {        
        DAOFactory::getDAO('InstanceDAO')->updateLastRun($owner_instance->instance_id);
    }
    
    protected function _getAccessTokenFromInstance($instance=false)
    {
        if(!$instance)
        {
            //if no instance, grabs random stackexchange one and use it
            $dao = DAOFactory::getDao('InstanceDAO');
            $all = $dao->getAllInstances('DESC', true, 'stackexchange');
            if(!is_array($all))
            {
                throw new Exception("Could not load instance");
            }
            
            if(count($all) == 0)
            {
                return '';
            }
            $instance = $all[rand(0,count($all)-1)];            
        }
        $owner_instances = DAOFactory::getDao('OwnerInstanceDAO')->getByInstance($instance->id);    
        $owner_instance = array_pop($owner_instances);
        return $owner_instance->oauth_access_token;
    }
    
    public function getRawAccountInformationFromApi($user_ids, $network, $instance_for_auth)    
    {
        $consumer_key       = $this->_consumerKey;                
        $access_token       = $this->_getAccessTokenFromInstance($instance_for_auth);

        $information    = new stdClass;
        $information->account_information = array();
        $chunked_ids    = $this->_chunkArray($user_ids, 100);
        $dao            = DAOFactory::getDAO('PulsestormStackexchangeRaw');        
        $c = 1;
        $raw_ids        = array();
        foreach($chunked_ids as $user_ids_100)
        {
            $param      = implode(';',$user_ids_100);  
            
            $params = array(
            'site'          =>$network,
            'pagesize'      =>100,
            'page'          =>$c,
            'access_token'  =>$access_token,            
            'key'           =>$consumer_key
            );
            
            $content    = $this->_getUrlWithParams(PulsestormStackexchangeCrawler::URL_API . "/users/$param", $params); //$param
            $id         = $dao->insertRawJsonResponse($network,'account_info_'.$c, $content);
            $raw_ids[]  = $id;
            $c++;
        }
        return $raw_ids;
    
    }
    
    protected function _addParamsToUrl($params,$url)
    {
        if(strpos($url, '?') !== false || strpos($url, '&') !== false)
        {
            throw new Exception("URL already has parameters (found ? or & in string)");
        }
    
        return $url . '?' . http_build_query($params);    
    }

    protected function _chunkArray(&$thing_ids,$number)
    {
        $chunks_100   = array();
        $index = count($thing_ids);
        for($i=0;$i<$index;$i+=$number)
        {
            $chunks_100[] = array_slice($thing_ids,$i,$number);
        }    
        return $chunks_100;
    }

    protected function _getUrlWithParams($url, $params)
    {
        $url = $this->_addParamsToUrl($params, $url);
        return $this->_getUrl($url);
    }
    
    public function getAllAccountInformationFromAccountIds($account_ids_stackexchange)
    {
        $all_items  = array();
        foreach($this->_chunkArray($account_ids_stackexchange, 100) as $ids)
        {
            $ids = implode(';',$ids);                
            #$all_items  = array();
            $has_more   = true;
            $page       = 1;
    
            $params = array(
                'pagesize'      =>100,
                'page'          =>$page,
                'access_token'  =>$this->_getAccessTokenFromInstance(),            
                'key'           =>$this->_consumerKey            
            );
            
            while($has_more)
            {
                $json = $this->_getUrlWithParams(PulsestormStackexchangeCrawler::URL_API . "/users/$ids/associated",$params);
                $json = json_decode($json);        
                $all_items = array_merge($all_items, $json->items);
                $has_more = $json->has_more;
                $page++;
            }
        }
        return $all_items;
    }
    
    protected function _getUrl($url)
    {
        $o = new PulsestormCurl;
        return $o->getUrl($url);
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL,$url);
//         curl_setopt($ch, CURLOPT_FAILONERROR,1);
//         curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
//         curl_setopt($ch, CURLOPT_TIMEOUT, 15);
//         curl_setopt($ch, CURLOPT_ENCODING , "gzip");    //http://api.stackoverflow.com/1.1/usage/gzip
//         $response = curl_exec($ch);
//         return $response;    
    }        
    
    /**
    * Needs an access token/key?
    */    
    public function fetchAndUpdateSiteList($page, $dao, $instance_for_auth=false)
    {
        $consumer_key       = $this->_consumerKey;                
        $access_token       = $this->_getAccessTokenFromInstance($instance_for_auth);
        $params = array(
            'page'=>$page,
            'pagesize'      =>100,
            'page'          =>$page,
            'access_token'  =>$access_token,            
            'key'           =>$consumer_key            
        );
        
        $json = $this->_getUrlWithParams(PulsestormStackexchangeCrawler::URL_API . '/sites',$params);
        $json = json_decode($json);

        foreach($json->items as $item)
        {
            foreach(get_object_vars($item) as $key=>$value)
            {
                if(is_object($value))
                {
                    $item->{$key} = json_encode($value);
                }
                else if(is_array($value))
                {
                    $item->{$key} = json_encode($value);
                }
            }
            $dao->insertOrUpdate($item);
        }
        
        if($json->has_more)
        {
            $page++;
            $this->fetchAndUpdateSiteList($page, $dao);
        }    
    }
    
}
