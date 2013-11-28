<?php
/**
 *
 * webapp/plugins/stackexchange/model/class.stackexchangePlugin.php
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkupapp.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 * 
 *
 * stackexchange (name of file)
 *
 * Description of what this class does
 *
 * Copyright (c) 2012 Alan Storm
 * 
 * @author Alan Storm
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Alan Storm
 */
require_once dirname(__FILE__).'/../../../plugins/stackexchange/model/class.stackexchangeCrawler.php';

class stackexchangePlugin extends Plugin implements CrawlerPlugin, DashboardPlugin, PostDetailPlugin {

    protected $_crawler;
    public function __construct($vals=null) {
        parent::__construct($vals);
        $this->folder_name = 'stackexchange';
        
        $this->addRequiredSetting('oauth_client_id');
        $this->addRequiredSetting('oauth_consumer_key');        
        $this->addRequiredSetting('oauth_consumer_secret');
        
        $this->_crawler = new stackexchangeCrawler;                        
    }

    protected function _initMapping()
    {
        PulsestormInjectdao::addDaos();
    }
    
    protected function _toDo()
    {
        require_once 'plugins/stackexchange/bugs.php';            
    }
    
    protected function _initErrorHandling()
    {
        date_default_timezone_set('America/Los_Angeles');
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);    
    }
    
    public function init()
    {
        $this->_initErrorHandling();
        $this->_initMapping();        
        $this->_toDo();
        $this->_updateSitesList();                
    }
    
    protected function _getPluginId($namespace)
    {        
        $plugin_option_dao  = DAOFactory::GetDAO('PluginOptionDAO');    
        $hash               = $plugin_option_dao->getOptionsHash('stackexchange');                        
        $first = array_pop($hash);
        
        //no values set yet means no key to set
        if(!$first)
        {
            return;
        }
        
        //no plugin id means things are really wrong, lets not get blamed!
        if(!array_key_exists('plugin_id',$first))
        {
            return;
        }
        return $first->plugin_id;    
    }
    
    protected function _updateSitesList()
    {
        $namespace          = 'stackexchange';
        $option_name        = 'last_downloaded_network_sites';
        $plugin_id          = $this->_getPluginId($namespace);        
        if(!$plugin_id)
        {
            return;
        }
        $plugin_option_dao  = DAOFactory::GetDAO('PluginOptionDAO');   
        $hash               = $plugin_option_dao->getOptionsHash('stackexchange');
        
        //insert option if it doesn't already exist
        if(!array_key_exists($option_name, $hash))
        {
            $plugin_option_dao->insertOption($plugin_id, $option_name, 123);
        }

        $hash               = $plugin_option_dao->getOptionsHash($namespace);        
        $time               = time();        
        $difference = $time - $hash[$option_name]->option_value;

        if($difference > (60 * 60 * 24 * 1) ) //1 day
        {
            $option_id = $hash[$option_name]->id;
            $plugin_option_dao->updateOption($option_id, $option_name, $time);
            DAOFactory::getDAO('PulsestormStackexchangeSites')->fetchAndUpdate();  
        }        
    }
    
    public function activate() {
    
    }

    public function deactivate() {
    
    }

    public function renderConfiguration($owner) {
        $controller = new stackexchangePluginConfigurationController($owner);
        return $controller->go();
    }

    protected function _isCli()
    {
        global $argv;
        return count($argv) > 0;
    }
    
    public function crawl() {   
        $this->init();        
        
        $o          = DAOFactory::getDao('InstanceDAO');
        $instances  =  $o->getAllInstances('DESC', true, 'stackexchange');
        
        //fetches JSON from API
        $this->_updateRawContent($instances);
                
        //parse JSON into tables
        $dao_raw = DAOFactory::getDAO('PulsestormStackexchangeRaw');
        $count   = $dao_raw->countUnprocessed();
        if($count > 100 && !$this->_isCli())
        {
            $this->_crawler->logError("Skipping $count rows of crawled results to parse. Please use the Crawl Queue link.");
            $insight_dao = DAOFactory::getDAO('InsightDAO');

            foreach($instances as $instance)
            {       
                $server = strip_tags($_SERVER['HTTP_HOST']);
                
                $id = (int) $instance->network_user_id;
                $insight_dao->insertInsight('frequency', $instance->id, date('Y-m-d',time()),
                "Action Required:", 
                "Too many Stack Exchange entries to process at once, please use the AJAX Queue." .
                    'http://' . $server . "/dashboard.php?v=crawlqueue&u=".$id."&n=stackexchange", 
                "_textonly",
                Insight::EMPHASIS_HIGH);
            }        
        }
        else
        {
            $this->_parseRawJsonIntoTables($instances);
        }
        
        
        //once JSON is parsed into tables (including site users), update StackExchange accounts IDs
        $this->_updateAccountIds($instances);
        
        $o = new stdClass;
        $o->instances = $instances;
        PulsestormEvents::dispatchEvent('pulsestorm_stackexchange_crawl_finished',$o);
    }
    
    protected function _updateRawContent($instances)
    {
        foreach($instances as $instance)
        {                    
            $owner_instances = DAOFactory::getDao('OwnerInstanceDAO')->getByInstance($instance->id);
            foreach($owner_instances as $owner_instance)
            {   
                $this->_crawler->setFromDate($instance->crawler_last_run);
                $this->_crawler->fetchAllUserJson($owner_instance->oauth_access_token);            
                $this->_crawler->finishCrawl($owner_instance);                    
            }            
        }     
    }
    
    protected function _parseRawJsonIntoTables($instances)
    {
        foreach($instances as $instance)
        {        
            $owner_instances = DAOFactory::getDao('OwnerInstanceDAO')->getByInstance($instance->id);
            foreach($owner_instances as $owner_instance)
            {    
                $this->_crawler->parseJsonIntoTables($owner_instance);                
            }
        }
    }
    
    protected function _updateAccountIds($instances)
    {
        $ids = array();
        foreach($instances as $item)        
        {
            $ids[] = $item->network_user_id;
        }
        
        DAOFactory::getDao('PulsestormStackexchangeAccountids')->updateIdsFromApi($ids);
    }
    
    public function getDashboardMenuItems($instance) {
        $this->_interceptAjax();
        $this->_initMapping();

        $object = DAOFactory::getDao('PulsestormStackexchangeUser');
        $ids = $object->getAllIds();
                
        $menus = array();

        $links_data_tpl = Utils::getPluginViewDirectory('stackexchange').'recent.tpl';
        $links_menu_item = new MenuItem("Recent", "Recent Responses", $links_data_tpl);
        $menus['recent'] = $links_menu_item;

        $links_data_tpl = Utils::getPluginViewDirectory('stackexchange').'process.tpl';
        $links_menu_item = new MenuItem("Crawl Queue", "Crawl Queue", $links_data_tpl);
        
        $menus['crawlqueue'] = $links_menu_item;        
        return $menus;
        
    }
    
    protected function _interceptAjax()
    {
        if(array_key_exists('action', $_GET))
        {        
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors','1');
            $controller = new PulsestormStackexchangeAjaxController;
            $controller->setAction($_GET['action'])
            ->dispatch();
            exit; //controller shold bail, but just in case
        }
    }
    
    public function getPostDetailMenuItems($post) {
    
    }
    public function renderInstanceConfiguration($owner, $instance_username, $instance_network) {
        return '';
    }
}
