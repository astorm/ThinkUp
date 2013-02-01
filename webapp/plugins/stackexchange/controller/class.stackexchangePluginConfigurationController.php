<?php
/**
 *
 * webapp/plugins/stackexchange/controller/class.stackexchangePluginConfigurationController.php
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

class stackexchangePluginConfigurationController extends PluginConfigurationController {

    public function __construct($owner) {
        parent::__construct($owner, 'stackexchange');
        $this->disableCaching();
        $this->owner = $owner;        
    }
    
    public function authControl() {
        //$options['oauth_consumer_secret']->option_value;
        //$options['oauth_client_id']->option_value;
        //$options['oauth_consumer_key']->option_value;        

        error_reporting(E_ALL | E_STRICT);
        $api_accessor = new stackexchangeAPIAccessor;

        $options = DAOFactory::GetDAO('PluginOptionDAO')
        ->getOptionsHash('stackexchange');

        $api_accessor->oAuthCodeAction($options, $this->owner->id);

        $config = Config::getInstance();
        Loader::definePathConstants();
        $this->setViewTemplate( THINKUP_WEBAPP_PATH.'plugins/stackexchange/view/account.index.tpl');
        $this->addToView('message', 'Hello ThinkUp world! This is an auto-generated plugin configuration '.
        'page for ' . $this->owner->email .'.');
        
        $this->addToView('thinkup_site_url',Utils::getApplicationURL(true));
        $this->view_mgr->addHelp('stackexchange', 'contribute/developers/plugins/buildplugin');

        //reads files in from etc/config.json
        $o = new PulsestormThinkupConfigBuilder;
        $o->render($this);
        
        /** set option fields **/
        // name text field
        
        $plugin = new stackexchangePlugin();        
        $plugin->init();        
        $this->addToView('is_configured', $plugin->isConfigured());
        if($plugin->isConfigured())
        {
            $url = 'https://stackexchange.com/oauth?' . http_build_query(array(
            // 'client_id'=>'854',
            'client_id'     => $options['oauth_client_id']->option_value,
            'scope'         => 'no_expiry',
            'redirect_uri'  => Utils::getApplicationURL(true) . 'account/?p=stackexchange'));
            $this->addToView('oauthorize_link',$url);
            
            $instances       = DAOFactory::getDao('InstanceDAO')->getByOwner($this->owner);            
            $owner_instances = array();
            foreach($instances as $instance)
            {
                if($instance->network == 'stackexchange')
                {
                    $owner_instances[] = $instance;        
                    $instance->member_of = array('Baz','Bar','Foo');
                }
            }
            
            $this->addToView('owner_instances', $owner_instances);            
            $this->addToView('network', 'stackexchange');
            
        }
        else
        {
            $this->addInfoMessage('Please complete plugin setup to start using it.', 'setup');
        }

        
        

        return $this->generateView();
    }

    public function saveAccessTokens() {
    
    }

}

