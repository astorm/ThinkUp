<?php
class PulsestormStackexchangeCrawlSyncMysql
{
    public function syncPosts($observer)
    {        
        $instances = $observer->params->instances;        
        foreach($instances as $item)
        {
            $this->_updatePostsWithInstance($item);
        }
    }

    public function syncUsers($observer)
    {
        $this->_instanceForAuth = array_shift($observer->params->instances);
        if(!$this->_instanceForAuth)
        {
            return;
        }
        //get list of sites
        $sites = DAOFactory::getDao('PulsestormStackexchangeSites')->getAll();
        foreach($sites as $site)
        {
            $network = $site['api_site_parameter'];
            $this->_updateUsersFromStackExchangeNetwork($network);
        }    
        array_push($observer->params->instances,$this->_instanceForAuth);
        
        
        // $this->_updateAccountIdsForUsersInTuUsers();
        
        $this->_instanceForAuth = false;
    }    

    protected function _updateAccountIdsForUsersInTuUsers()
    {        
        DAOFactory::getDao('PulsestormStackexchangeAccountids')->updateFromUsersTable();    
    }
    
    protected function _updatePostsWithInstance($instance)
    {
        $ids_and_networks = DAOFactory::getDao('PulsestormStackexchangeAccountids')
        ->getByAccountId($instance->network_user_id);    

        foreach($ids_and_networks as $id_and_network)
        {
            $this->_updatePostsWithIdAndNetworkQuestions($id_and_network);            
            $this->_updatePostsWithIdAndNetworkAnswers($id_and_network);
            $this->_updatePostsWithIdAndNetworkComments($id_and_network);
        }
    }
    
    protected function _updatPostsWithQuestionsIveAsked($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];
        
        //questions I've own
        $dao_questions  = $this->_getDao('PulsestormStackexchangeQuestion', $stackexchange_network);
        $dao_posts     = DAOFactory::getDAO('PostDAO');
        
        $result = $dao_questions->getResultSetBy('owner_id', $id_network);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        while($row = $result->fetch())
        {                   
            $this->_processQuestionIntoPost($row, $stackexchange_network, $dao_posts);
        }    
    }
    
    protected function _updatePostsWithQuestionsIveAnswered($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];
        
        $dao_answers    = $this->_getDao('PulsestormStackexchangeAnswer', $stackexchange_network);
        $result = $dao_answers->getResultSetBy('owner_id', $id_network);
        $result->setFetchMode(PDO::FETCH_ASSOC); 
        $question_ids = array();
        while($row = $result->fetch())
        {        
            $question_ids[] = $row['question_id'];
        }
        $question_ids = array_unique($question_ids);
        
        if(!$question_ids)
        {
            return;
        }
        $dao_questions  = $this->_getDao('PulsestormStackexchangeQuestion', $stackexchange_network);
        $dao_posts     = DAOFactory::getDAO('PostDAO');

        $result = $dao_questions->getResultSetBy('question_id', $question_ids);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        while($row = $result->fetch())
        {      
            $this->_processQuestionIntoPost($row, $stackexchange_network, $dao_posts);
        }    
    }
    
    protected function _updatePostsWithQuestionsIveCommentedOn($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];    
        $dao_comments           = $this->_getDao('PulsestormStackexchangeComment', $stackexchange_network);  
        $result                 = $dao_comments->getResultSetBy('owner_id', $id_network);
        $result->setFetchMode(PDO::FETCH_ASSOC); 
        $post_ids = array();
        while($row = $result->fetch())
        {
            $post_ids[] = $row['post_id'];
        }        
        if(!$post_ids)
        {
            return;            
        }
        
        //are post ids and question ids identical?
        $dao_posts           = $this->_getDao('PulsestormStackexchangePost', $stackexchange_network);  
        $result              = $dao_posts->getResultSetBy('post_id',$post_ids);
        $question_ids        = array();
        while($row = $result->fetch())
        {
            if($row['post_type'] == 'question')
            {
                $question_ids[] = $row['post_id'];
            }
        }        
        if(!$question_ids)
        {
            return;
        }
        
        $dao_questions      = $this->_getDao('PulsestormStackexchangeQuestion',$stackexchange_network);
        $dao_posts          = DAOFactory::getDAO('PostDAO');
        $result             = $dao_questions->getResultSetBy('question_id', $question_ids);
        while($row = $result->fetch())
        {
            $this->_processQuestionIntoPost($row, $stackexchange_network, $dao_posts);
        }                
    }

    protected function _processCommentIntoPost($row, $stackexchange_network, $dao_posts)
    {
        $post = (array) $this->_generateEmptyCommentObjectForUpdate($row,$stackexchange_network);
        if($dao_posts->isPostInDB($post['post_id'], 'stackexchange'))
        {
            return; //still not sure how/if to handle updates
        }
        $dao_posts->addPost($post);    
    
    }
    
    protected function _processAnswerIntoPost($row, $stackexchange_network, $dao_posts)
    {
        $post = (array) $this->_generateEmptyAnswerObjectForUpdate($row,$stackexchange_network);
        if($dao_posts->isPostInDB($post['post_id'], 'stackexchange'))
        {
            return; //still not sure how/if to handle updates
        }
        $dao_posts->addPost($post);    
    
    }
    
    protected function _processQuestionIntoPost($row, $stackexchange_network, $dao_posts)
    {
        $post = (array) $this->_generateEmptyQuestionObjectForUpdate($row,$stackexchange_network);
        if($dao_posts->isPostInDB($post['post_id'], 'stackexchange'))
        {
            return; //still not sure how/if to handle updates
        }
        $dao_posts->addPost($post);    
    }
    
    protected function _getQuestionIdsFromOwnerId($id_network,$stackexchange_network)
    {
        $dao_questions = $this->_getDao('PulsestormStackexchangeQuestion', $stackexchange_network);
        $result = $dao_questions->getResultSetBy('owner_id', $id_network);        
        $question_ids = array();
        while($row = $result->fetch())
        {
            $question_ids[] = $row['question_id'];
        }    
        return $question_ids;
    }
    
    protected function _updatePostsWithAnswersToMyQuestions($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];
                
        $question_ids = $this->_getQuestionIdsFromOwnerId($id_network, $stackexchange_network);        
        if(!$question_ids)
        {
            return;
        }
        
        $dao_answers  = $this->_getDao('PulsestormStackexchangeAnswer', $stackexchange_network);
        $dao_posts    = DAOFactory::getDAO('PostDAO');
        $result = $dao_answers->getResultSetBy('question_id', $question_ids);        
        while($row = $result->fetch())
        {    
            $this->_processAnswerIntoPost($row, $stackexchange_network, $dao_posts);
        }                    
    }

    protected function _updatePostsWithAnswersIveAsked($id_and_network)
    {
        //answers I own
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];
        

        $dao_answers  = $this->_getDao('PulsestormStackexchangeAnswer', $stackexchange_network);
        $dao_posts    = DAOFactory::getDAO('PostDAO');
        
        $result = $dao_answers->getResultSetBy('owner_id', $id_network);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        
        while($row = $result->fetch())
        {    
            $this->_processAnswerIntoPost($row, $stackexchange_network, $dao_posts);
        }                    
    }
    
    protected function _updatePostWithCommentsIMade($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];

        $dao_comments           = $this->_getDao('PulsestormStackexchangeComment', $stackexchange_network);
        $dao_posts              = DAOFactory::getDAO('PostDAO');
        
        $result = $dao_comments->getResultSetBy('owner_id', $id_network);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        
        while($row = $result->fetch())
        {    
            $this->_processCommentIntoPost($row, $stackexchange_network, $dao_posts);
        }                   
    }
    
    protected function _updatePostsWithCommentsOnMyQuestion($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];        
        $question_ids           = $this->_getQuestionIdsFromOwnerId($id_network, $stackexchange_network);        
        if(!$question_ids)
        {
            return;
        }
        
        $dao_comments           = $this->_getDao('PulsestormStackexchangeComment', $stackexchange_network);
        $dao_posts              = DAOFactory::getDAO('PostDAO');
        
        $result = $dao_comments->getResultSetBy('post_id', $question_ids);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        
        while($row = $result->fetch())
        {    
            var_dump($row);
            //$this->_processCommentIntoPost($row, $stackexchange_network, $dao_posts);
        }          
    }
    
    protected function _updatePostsWithAllComments($id_and_network)
    {
        $stackexchange_network  = $id_and_network['stackexchange_network'];
        $id_stackexchange       = $id_and_network['account_id_stackexchange'];
        $id_network             = $id_and_network['account_id_network_site'];        
        
        $dao_comments           = $this->_getDao('PulsestormStackexchangeComment', $stackexchange_network);
        $dao_posts              = DAOFactory::getDAO('PostDAO');
        
        $result = $dao_comments->getResultSetAll();
        $result->setFetchMode(PDO::FETCH_ASSOC);
        
        while($row = $result->fetch())
        {    
            $this->_processCommentIntoPost($row, $stackexchange_network, $dao_posts);        
        }    
    }
    
    protected function _updatePostsWithIdAndNetworkComments($id_and_network)
    {
        $this->_updatePostsWithAllComments($id_and_network);
        
        //comments I own
        //$this->_updatePostWithCommentsIMade($id_and_network);
        //comments on my questions
        //$this->_updatePostsWithCommentsOnMyQuestion($id_and_network);        
        //comments on my answers
    }

    
    protected function _updatePostsWithIdAndNetworkQuestions($id_and_network)
    {
        //questions I've asked
        $this->_updatPostsWithQuestionsIveAsked($id_and_network);
        
        //questions I've answered
        $this->_updatePostsWithQuestionsIveAnswered($id_and_network);
        
        //questions I've commented on
        $this->_updatePostsWithQuestionsIveCommentedOn($id_and_network);
    }

    protected function _updatePostsWithIdAndNetworkAnswers($id_and_network)
    {
        $this->_updatePostsWithAnswersIveAsked($id_and_network);
        
        $this->_updatePostsWithAnswersToMyQuestions($id_and_network);
    }
        
    protected function _updateUsersFromStackExchangeNetwork($network)
    {        
        $ids = $this->_getUserIdsFromStackExchangeNetwork($network);
        if(!$ids)
        {
            return;
        }        
        
        
        $raw_ids = $this->_getRawAccountInformationFromApi($ids, $network);        
        $this->_processRawIdsWithCrawler($raw_ids);        
        $this->_updateUsersFromStackExchangeAccounts();        
    }
    
    protected function _updateUsersFromStackExchangeAccounts()
    {
        $dao_user = DAOFactory::getDao('UserDAO');
        $dao_stack_exchange_account = DAOFactory::getDao('PulsestormStackexchangeAccount');        
        $result = $dao_stack_exchange_account->getResultSetAll();
        $result->setFetchMode(PDO::FETCH_ASSOC);
        while($row = $result->fetch())
        {
            $user = $this->_generateEmptyUserObjectForUpdate();
            $user->username         = $row['account_id'];
            $user->user_id          = $row['account_id'];
            $user->full_name        = $row['display_name'];
            $user->avatar           = $row['profile_image'];
            $user->network          = 'stackexchange';            
            $user->url              = $row['link'];
            $dao_user->updateUser($user);
        }        
    }
    
    protected function _generateEmptyCommentObjectForUpdate($row, $network)
    {
        $o = new stdClass;
        $o->post_id         = $network . '-comment-' . $row['comment_id'];        
        $o->author_username = $row['owner_id'];
        $o->author_fullname = $row['owner_id'];
        $o->author_avatar   = 'XX';
        $o->author_user_id  = $row['owner_id'];        
        $o->post_text       = $row['body'];
        $o->is_protected    = '0';
        $o->pub_date        = $row['creation_date'];
        $o->source          = 'NA';
        $o->network         = 'stackexchange';
        return $o;    
    
    }
    
    protected function _generateEmptyAnswerObjectForUpdate($row, $network)
    {
        $o = new stdClass;
        $o->post_id         = $network . '-' . $row['answer_id'];        
        $o->author_username = $row['owner_id'];
        $o->author_fullname = $row['owner_id'];
        $o->author_avatar   = 'XX';
        $o->author_user_id  = $row['owner_id'];        
        $o->post_text       = $row['body'];
        $o->is_protected    = '0';
        $o->pub_date        = $row['creation_date'];
        $o->source          = 'NA';
        $o->network         = 'stackexchange';
        return $o;    
    }
    
    protected function _generateEmptyQuestionObjectForUpdate($row, $network)
    {
        $user_id = DAOFactory::getDao('PulsestormStackexchangeAccountids')
        ->getAccountIdFromUserAndNetwork($row['owner_id'],$network);
    
        $o = new stdClass;
        $o->post_id         = $network . '-' . $row['question_id'];        
        $o->author_username = $user_id;
        $o->author_fullname = $user_id;
        $o->author_avatar   = 'XX';
        $o->author_user_id  = $user_id;        
        $o->post_text       = $row['body'];
        $o->is_protected    = '0';
        $o->pub_date        = $row['creation_date'];
        $o->source          = 'NA';
        $o->network         = 'stackexchange';
        return $o;
    }
    
    static public function generateEmptyUserObjectForUpdate()
    {
        $user = new stdClass;
        $user->friend_count     = null;
        $user->favorites_count  = null;
        $user->last_post        = null;
        $user->last_post_id     = null;
        $user->network          = null;
        $user->follower_count   = null;
        $user->post_count       = null;
        $user->user_id          = null;
        $user->location         = null;
        $user->description      = null;
        $user->url              = null;
        $user->is_protected     = null;
        $user->found_in         = null;
        $user->joined           = null;
        $user->network          = null;        
        return $user;    
    }
    
    protected function _generateEmptyUserObjectForUpdate()
    {
        return self::generateEmptyUserObjectForUpdate();
    }
    
    protected function _processRawIdsWithCrawler($raw_ids)
    {
        $dao     = DAOFactory::getDAO('PulsestormStackexchangeRaw');        
        $rows    = $dao->getRowsWithIds($raw_ids);
        $crawler = new stackexchangeCrawler;                
        $crawler->processUnprocessedRows($rows, $dao);    
    }
    
    protected function _getRawAccountInformationFromApi($user_ids, $network)
    {
        $crawler = new stackexchangeCrawler;        
        return $crawler->getRawAccountInformationFromApi($user_ids, $network, $this->_instanceForAuth);        
    }
        
    protected function _getUserIdsFromStackExchangeNetwork($network)
    {
        $user    = $this->_getDao('PulsestormStackexchangeUser',$network);            
        $result = $user->getResultSetAll();
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $ids = array();
        while($row = $result->fetch())
        {
            $ids[] = $row['user_id'];
        }
        return $ids;    
    }    
    
    protected function _getDao($id, $network)
    {
        $dao = DAOFactory::getDao($id)
        ->setNetwork($network)
        ->initTable();        
        return $dao;
    }    
}