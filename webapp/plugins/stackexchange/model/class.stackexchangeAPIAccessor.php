<?php
/**
 *
 * webapp/plugins/stackexchange/model/class.stackexchangeAPIAccessor.php
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
 * @author Your Name  Your Email
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Alan Storm
 */

class stackexchangeAPIAccessor{ 

    public static function apiRequest() {

    }

    public static function rawApiRequest() {

    }
    
    protected function _getThinkupBaseUrl()
    {
        return trim(Utils::getApplicationURL(true),'/');
        //return 'http://stack-exchange-crawl.dev';
    }
    
    protected function _getStackExchangeOAuthUrl()
    {
        return 'https://stackexchange.com/oauth/access_token';
    }
    
    protected function _getCodeFromRequest()
    {
        $code = array_key_exists('code', $_GET) ? $_GET['code'] : false;
        return $code;
    }
    
    protected function _getAccessTokenFromStackExchangeApiUsingCode($options, $code)
    {
        $o = new PulsestormCurl;
        $data = array(        
        'client_id'     => $options['oauth_client_id']->option_value,
        'client_secret' => $options['oauth_consumer_secret']->option_value,
        'code'          => $code,
        'redirect_uri'  => $this->_getThinkupBaseUrl() . '/account/?p=stackexchange'
        );                

        $results        = $o->postToUrl($this->_getStackExchangeOAuthUrl(), $data);                
        
        parse_str($results,$result_vars);
        $access_token   = array_key_exists('access_token',$result_vars) ? $result_vars['access_token'] : false;            

        return $access_token;
    }
    
    protected function _getStackExchangeApiAssociatedUrl()
    {
        return PulsestormStackexchangeCrawler::URL_API . '/me/associated';
    }
    
    protected function _getAssociatedAccounts($options, $access_token)
    {
        $o = new PulsestormCurl;
        $url = $this->_getStackExchangeApiAssociatedUrl() . '?' .
        http_build_query(array(
            'access_token'  => $access_token,
            'key'           => $options['oauth_consumer_key']->option_value
            ));
            
        $results = $o->getUrl($url);            
        $json = json_decode($results);   

        if(!isset($json->items))
        {
            return array();
        }               
        return $json->items;
    }
    
    protected function _getMasterAccountId($options, $access_token)
    {        
        $items = $this->_getAssociatedAccounts($options, $access_token);
        $first      = array_shift($items);
        if(!$first)
        {
            return;
        }
        $account_id = isset($first->account_id) ? $first->account_id : false;                 
        return $account_id;
    }
    
    protected function _createInstanceAndUpdateTokens($account_id, $owner_id, $access_token)
    {
        //START: do instance creation
        $id     = DAOFactory::getDAO('InstanceDAO');        
        $oid    = DAOFactory::getDAO('OwnerInstanceDAO');        
        $i      = $id->getByUserIdOnNetwork($account_id,'stackexchange');
        
        if(isset($i))
        {
            $oi = $oid->get($owner_id, $i->id);
            if ($oi == null) { //Owner_instance doesn't exist
                $oid->insert($owner_id, $i->id, '', '');
            }        
        }
        else
        {
            $id->insert($account_id, $account_id, 'stackexchange');
            $i = $id->getByUserIdOnNetwork($account_id,'stackexchange');
            $oid->insert($owner_id, $i->id, '', '');
        }       
        //END: do instance creation
        
        //START: reset crawler last run
        PulsestormInjectdao::addDaos();
        $result    = DAOFactory::getDAO('PulsestormStackexchangeInstance')
        ->resetLastRun($i->id);
        //END: reset crawler last run
        
        //START: update the tokens
        $oid->updateTokens($owner_id, $i->id, $access_token, '');
        //END: update the tokens
        
        //START: create default user so we don't get errors
        $dao_user = DAOFactory::getDao('UserDAO');
        $user = PulsestormStackexchangeCrawlSyncMysql::generateEmptyUserObjectForUpdate();
        $user->username         = $account_id;
        $user->user_id          = $account_id;
        $user->full_name        = '';
        $user->avatar           = '';
        $user->network          = 'stackexchange';            
        $user->url              = '';
        $dao_user->updateUser($user);
        
        //END: create default user so we don't get errors
    
    }
    
    public function oAuthCodeAction($options, $owner_id)
    {
        error_reporting(E_ALL | E_STRICT);    
        $code = $this->_getCodeFromRequest();
        if(!$code)
        {
            return;
        }
        
        $access_token   = $this->_getAccessTokenFromStackExchangeApiUsingCode($options, $code);                                
        $account_id     = $this->_getMasterAccountId($options,$access_token);        
        if(!$account_id)
        {
            throw new Exception("\$account_id is false");
        }        
        $this->_createInstanceAndUpdateTokens($account_id, $owner_id, $access_token);
    }
        
    public function getAssociatedSiteUrls($options, $access_token)
    {
        $items = $this->_getAssociatedAccounts($options, $access_token);
        $urls  = array();
        foreach($items as $item)
        {
            $urls[] = $item->site_url;
        }
        return $urls;
    }
}
