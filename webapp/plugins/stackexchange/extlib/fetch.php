<?php
date_default_timezone_set('America/Los_Angeles');
//error_reporting(E_ALL | E_STRICT);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
class PulsestormJsonContainer
{
    protected $_data=array();
    
    public function getData()
    {
        return $this->_data;
    }
    
    public function getPropertiesWithGlob($glob)
    {
        $glob = basename($glob);
        if($glob[strlen($glob)-1] != '*')
        {
            throw new Exception("Unexpected Glob: $glob");
        }
        $glob = rtrim($glob,'*');
        
        if(empty($glob))
        {
            return $this->_data;
        }
        
        $strings = array();
        foreach($this->_data as $key=>$value)
        {
            if(strpos($key, $glob) !== 0)
            {
                continue;
            }
            $strings[] = $value;
        }
        return $strings;
    }
    
    public function saveJson($name, $content, $params, $save_path)
    {
        $name = $name . implode('_',$params);
        $name = preg_replace('%[^a-z_0-9]%i','',$name)  . '.json';            
        $this->_data[$name] = utf8_encode($content);
        file_put_contents($save_path . '/'.$name, utf8_encode($content));
    }
    
    public function loadDevelopmentData()
    {
        $files = glob('output/*.json');
        foreach($files as $file)
        {
            $name = basename($file);
            $this->_data[$name] = file_get_contents($file);
        }
        return $this;
    }   
    
    public function log($message)
    {
        echo $message, "\n";
    }
    
    function getFromDate()
    {
        return strToTime('2005-01-01');
    }
}

class PulsestormStackexchangeCrawler
{
    const URL_API = 'https://api.stackexchange.com/2.1';
    protected $_jsonContainer;   
    function getJsonContainer()
    {
        return $this->_jsonContainer;
    }    
    function setJsonContainter($container)
    {
        if(!is_callable(array($container, 'saveJson')))
        {
            throw new Exception('JSON container needs a saveJson method');
        }
        
        $this->_jsonContainer = $container;
        return $this;
    }
    
    protected $_siteCode;
    
    function setSiteCode($code)
    {
        $this->_siteCode = $code;
        return $this;
    }
    
    function getSiteCode()
    {
        return $this->_siteCode;
        //return 'stackoverflow';
    }
    
    protected $_userId = false;    
    function setUserId($value)
    {    
        if($value == 'me')
        {
            if(!$this->getAccessToken() || !$this->getConsumerKey())
            {
                throw new Exception("Cannot fetch user id for 'me' without access token or consumer key.");
            }
            
            $url = $this->getBaseApiUrl() . '/me';
            $params = array(
            'site'          =>$this->getSiteCode(),
            'access_token'  =>$this->getAccessToken(),            
            'key'           =>$this->getConsumerKey()            
            );            
            $url = $this->addParamsToUrl($url, $params);            
            $this->log('Fetching: ' . $url);
            $json = $this->curlGetApiContents($url);    
            
            $json = json_decode($json);
            $user = array_shift($json->items);
            $value = $user->user_id;
        }
        $this->_userId=$value;
        return $this;
    }
    
    function getUserId()
    {
        return $this->_userId;
        // return '4668';
    }
    
    function getBaseApiUrl()
    {
        return PulsestormStackexchangeCrawler::URL_API;
    }
    
    function getSavePath()
    {
        return '/Users/alanstorm/Dropbox/StackExchange/output';
    }
    
    function getUsersUrl($id=false)
    {
        $id = $id ? $id : $this->getUserId();
        return $this->getBaseApiUrl() . '/users/' . $id;
    }
    
    function getUsersQuestionUrl($id=false)
    {
        return $this->getUsersUrl() . '/questions';
    }
    
    function getUsersAnswerUrl($id=false)
    {
        return $this->getUsersUrl() . '/answers';
    }
    
    function getUsersCommentUrl($id=false)
    {
        return $this->getUsersUrl() . '/comments';
    }
    
    function getUsersMentionUrl($id=false)
    {
        return $this->getUsersUrl() . '/mentioned';
    }
    
    function getUsersNotificationUrl($if=false)
    {
        return $this->getUsersUrl() . '/notifications';
    }
    
    function addParamsToUrl($url, $params)
    {
        if(strpos($url, '?') !== false || strpos($url, '&') !== false)
        {
            throw new Exception("URL already has parameters (found ? or & in string)");
        }
    
        return $url . '?' . http_build_query($params);    
    }
    
    function saveJson($name, $content, $params)
    {
        if($this->_jsonContainer)
        {
            $this->_jsonContainer->saveJson($name, $content, $params, $this->getSavePath());
            return;
        }
                
        $name = $name . implode('_',$params);
        $name = preg_replace('%[^a-z_0-9]%i','',$name)  . '.json';    
        file_put_contents($this->getSavePath() . '/'.$name, utf8_encode($content));
    }
    
    function curlGetApiContents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");    //http://api.stackoverflow.com/1.1/usage/gzip
        $response = curl_exec($ch);
        return $response;
    }
    
    function getFromDate()
    {
        if($this->getJsonContainer())
        {
            return strToTime($this->getJsonContainer()->getFromDate());
        }
        return strToTime('2005-01-01');
    }
    
    protected $_accessToken;
    function setAccessToken($value)
    {
        $this->_accessToken = $value;
        return $this;
    }    
    function getAccessToken()
    {
        return $this->_accessToken;
        //return 'cqYN5*pMzeqn8H8(BYzSVw))';
    }
    
    
    protected $_consumerKey;
    public function setConsumerKey($value)
    {
        $this->_consumerKey = $value;
        return $this;
    }
    
    function getConsumerKey()
    {
        //return 'LN4BTsE7W3JUkbBiSz9Vhw((';
        return $this->_consumerKey;
    }
    
    function taskSaveAll($url_method, $identifier)
    {
        $done = false;
        $page = 1;    
        while(!$done)
        {
            $params = array(
            'site'          =>$this->getSiteCode(),
            'pagesize'      =>100,
            'page'          =>$page,
            'filter'        =>'withbody',
            'fromdate'      =>$this->getFromDate(),//strToTime('2005-01-01'),
            'todate'        =>time(),            
            'access_token'  =>$this->getAccessToken(),            
            'key'           =>$this->getConsumerKey()
            );
    
            $url_base = $url_method;
            if(is_callable(array($this,$url_method)))
            {
                $url_base = call_user_func(array($this,$url_method));
            }
            
            $url = $this->addParamsToUrl($url_base, $params);        
            $this->log("Fetching: " . $url);        
            $json = $this->curlGetApiContents($url);        
            $object = json_decode($json);
            if(is_object($object))
            {
                $this->saveJson($identifier,$json,$params);        
            }
            else
            {
                $this->log("Could not download JSON, bailing for this type.");
            }

            if(!is_object($object) || !$object->has_more)
            {
                $done = true;
            }        
            $page++;
        }
    
    }
    
    function log($message)
    {
        if($this->_jsonContainer)
        {
            $this->_jsonContainer->log($message);
            return;
        }
        echo $message, "\n";        
    }
    
    function taskSaveAllQuestions()
    {
        return $this->taskSaveAll('getUsersQuestionUrl','questions');
    }
    
    function taskSaveAllQuestionsIAnswered()
    {
        $ids = $this->getAllQuestionIdsFromQuestionsIAnswered();
        $chunks_100   = array();
        $index = count($ids);
        for($i=0;$i<$index;$i+=100)
        {
            $chunks_100[] = array_slice($ids,$i,100);
        }        
        $url = $this->getBaseApiUrl() . '/questions';        
        $c=1;
        foreach($chunks_100 as $ids)
        {
            $ids = implode(';',$ids);
            $url = $this->getBaseApiUrl() . '/questions/' . $ids;        
            $this->taskSaveAll($url, 'quesiansw_'.$c.'_');
            $c++;        
        }
    }
    
    function taskSaveAllAnswers()
    {
        return $this->taskSaveAll('getUsersAnswerUrl', 'answers');
    }
    
    function taskSaveAllComments()
    {
        return $this->taskSaveAll('getUsersCommentUrl', 'comments');
    }
    
    function taskSaveAllMentions()
    {
        return $this->taskSaveAll('getUsersMentionUrl', 'mentions');
    }
    
    function taskSaveAllNotifications()
    {
        return $this->taskSaveAll('getUsersNotificationUrl','notifications');
    }
        
    function getAllQuestionIdsFromQuestionsIAnswered()
    {
        return $this->getAllOfOneFieldFromAFileType('question_id','output/answers'.$this->getSiteCode().'*');
    }
    
    function getAllQuestionIdsFromMyQuestions()
    {
        return $this->getAllOfOneFieldFromAFileType('question_id','output/questions'.$this->getSiteCode().'*');
    }
    
    function getAllAnswerIdsFromMyAnswers()
    {
        return $this->getAllOfOneFieldFromAFileType('answer_id','output/answers'.$this->getSiteCode().'*');
    }
    
    function getAllOfOneFieldFromAFileType($field, $file_glob, $filter_callback=false)
    {
        $data_items = $this->getJsonContainer()->getPropertiesWithGlob($file_glob);
        $values = array();
        foreach($data_items as $string_json)
        {
            $object = json_decode($string_json);
            if(!is_object($object))
            {
                $this->log("Could not find items, skipping");
                continue;
            }
            foreach($object->items as $item)
            {
                if(is_callable($filter_callback) && !$filter_callback($item))
                {
                    continue;
                }
                $values[] = $item->{$field};
            }    
        }
        return $values;
    }
    
    function taskFetchAllCommentsOn($method_get_ids, $type, $identifier=false)
    {
        $identifier = $identifier ? $identifier : $type;
        $thing_ids = call_user_func(array($this,$method_get_ids));
        $chunks_100   = array();
        $index = count($thing_ids);
        for($i=0;$i<$index;$i+=100)
        {
            $chunks_100[] = array_slice($thing_ids,$i,100);
        }
        
        $c=1;
        foreach($chunks_100 as $ids)
        {
            $ids = implode(';',$ids);
            $url = $this->getBaseApiUrl() . '/'.$type.'/' . $ids . '/comments';        
            $this->taskSaveAll($url, 'comments_on_'.$identifier.'_'.$c.'_');
            $c++;
        }
    
    }
    
    function taskFetchAllCommentsOnQuestions()
    {
        $this->taskFetchAllCommentsOn('getAllQuestionIdsFromMyQuestions','questions');
    }
    
    function taskFetchAllCommentsOnAnswers()
    {
        $this->taskFetchAllCommentsOn('getAllAnswerIdsFromMyAnswers','answers');
    }
    
    function taskFetchAllCommentsOnPostsWithMention()
    {
        $this->taskFetchAllCommentsOn('getAllPostIdsFromMyMentions','posts');    
    }
    
    function getAllPostIdsFromMyMentions()
    {
        return $this->getAllOfOneFieldFromAFileType('post_id','output/mentions'.$this->getSiteCode().'*');
    }
    
    function splitArrayIntoParts($array, $size_of_parts)
    {
        $chunks   = array();
        $index = count($array);
        for($i=0;$i<$index;$i+=$size_of_parts)
        {
            $chunks[] = array_slice($array,$i,$size_of_parts);
        }
        return $chunks;
    }
    
    function taskFetchAllPostsWithMyMentions()
    {
        $post_ids = $this->getAllPostIdsFromMyMentions();    
        $post_ids_chunked = $this->splitArrayIntoParts($post_ids, 100);
        $c=1;
        foreach($post_ids_chunked as $ids)
        {
            $ids = implode(';',$ids);
            $url = $this->getBaseApiUrl() . '/posts/' . $ids;
            $this->taskSaveAll($url, 'posts_with_my_mentions_' . $c . '_');
            $c++;
        }
    }
    
    function isPostAQuestion($info)
    {
        return $info->post_type == 'question';
    }
    
    function getIdsOfQuestionsWithCommentsOnMyMentions()
    {
        $post_ids_of_questions_with_comments_on_my_mentions 
            = $this->getAllOfOneFieldFromAFileType(
                'post_id','output/posts_with_my_mentions*','isPostAQuestion');
        return $post_ids_of_questions_with_comments_on_my_mentions;
    }
    
    function getAllAnswerIdsFromAnswersToMyQuestions()
    {
        $ids = $this->getAllOfOneFieldFromAFileType('answer_id','answers_to_my_questions_*');        
        return $ids;
    }
    
    function getIdsOfMyQuestions()
    {
        $ids = $this->getAllOfOneFieldFromAFileType(
            'question_id','questions'.$this->getSiteCode().'*');
        return $ids;
    }    

    function resetForNewPass()
    {
        $c = $this->getJsonContainer(); 
        if($c)
        {
            $c->resetInsertedRawIds();
        }
    }
    
    function taskFetchAllUserInformation($site_codes='stackoverflow')
    {
        $site_codes = is_array($site_codes) ? $site_codes : array($site_codes);
        foreach($site_codes as $code)
        {
            $this->resetForNewPass();
            $this->setSiteCode($code);        
            $this->setUserId('me');
            $this->taskSaveAllMentions();    
            $this->taskSaveAllComments();    
            $this->taskSaveAllAnswers();    
            $this->taskSaveAllQuestions();        
            $this->taskSaveAllQuestionsIAnswered();
            $this->taskFetchAllCommentsOnQuestions();  
            $this->taskFetchAllCommentsOnAnswers();        
            $this->taskFetchAllPostsWithMyMentions();    
            $this->taskFetchAllCommentsOnPostsWithMention();            
            //Parent Question if above post is an Answer
            $this->taskFetchAllQuestionsWithCommentsOnMyMentions();        
            $this->taskFetchAllAnswersToMyQuestions();
            $this->taskFetchAllCommentsOnAnswersToMyQuestions();        
       }
    }
    
    function taskFetchAllQuestionsWithCommentsOnMyMentions()
    {
        $this->taskFetchAllIdBasedResults(
            'getIdsOfQuestionsWithCommentsOnMyMentions',
            '/questions/{ids}',
            'questions_with_comments_on_my_mentions');    
    }
    
    function taskFetchAllAnswersToMyQuestions()
    {
        $this->taskFetchAllIdBasedResults(
            'getIdsOfMyQuestions',
            '/questions/{ids}/answers',
            'answers_to_my_questions'
        );    
    }
    
    function taskFetchAllCommentsOnAnswersToMyQuestions()
    {
        $this->taskFetchAllCommentsOn('getAllAnswerIdsFromAnswersToMyQuestions','answers','answers_to_my_questions');
    }
    
    function taskFetchAllIdBasedResults($method_fetch_ids, $url_method_portion, $identifier)
    {
        $ids            = call_user_func(array($this,$method_fetch_ids));
        $ids_chunked    = $this->splitArrayIntoParts($ids, 100);        
        $c=1;
        foreach($ids_chunked as $ids)
        {
            $ids = implode(';',$ids);
            $url = $this->getBaseApiUrl() . $url_method_portion;//.'/' . $ids;
            $url = str_replace('{ids}',$ids,$url);
            $this->taskSaveAll($url, $identifier . '_' . $c . '_');
            $c++;
        }
    }    
    
    function arrayOfStringsToJson(&$data)
    {
        foreach($data as $key=>$value)
        {
            $data[$key] = json_decode($value);
        }
        return $data;
    }
    
    function getTypeLegend()
    {
        return array(
        'items#quota_remaining#quota_max#has_more'
            =>'pulsestorm_stackexchange_api_list',
            
        'question_id#answer_id#creation_date#last_activity_date#score#is_accepted#body#owner'
            =>'pulsestorm_stackexchange_api_answer',
            
        'question_id#answer_id#creation_date#last_edit_date#last_activity_date#score#is_accepted#body#owner'
            =>'pulsestorm_stackexchange_api_answer',
            
        'question_id#answer_id#creation_date#last_edit_date#last_activity_date#score#community_owned_date#is_accepted#body#owner'
            =>'pulsestorm_stackexchange_api_answer',
            
        'question_id#answer_id#creation_date#last_activity_date#score#community_owned_date#is_accepted#body#owner'
            =>'pulsestorm_stackexchange_api_answer',
            
        'comment_id#post_id#creation_date#score#edited#body#owner'
            =>'pulsestorm_stackexchange_api_comment',
            
        'comment_id#post_id#creation_date#score#edited#body#owner#reply_to_user'
            =>'pulsestorm_stackexchange_api_comment',
            
        'post_id#post_type#body#owner#creation_date#last_activity_date#score#link'
            =>'pulsestorm_stackexchange_api_post',
            
        'post_id#post_type#body#owner#creation_date#last_activity_date#last_edit_date#score#link'
            =>'pulsestorm_stackexchange_api_post',
            
        'question_id#creation_date#last_activity_date#score#answer_count#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#answer_count#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#answer_count#accepted_answer_id#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#answer_count#closed_date#body#title#tags#closed_reason#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#answer_count#accepted_answer_id#closed_date#body#title#tags#closed_reason#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#answer_count#accepted_answer_id#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#answer_count#closed_date#body#title#tags#closed_reason#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#answer_count#accepted_answer_id#closed_date#body#title#tags#closed_reason#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#answer_count#accepted_answer_id#closed_date#body#title#tags#closed_reason#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#answer_count#accepted_answer_id#protected_date#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#answer_count#accepted_answer_id#protected_date#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#creation_date#last_activity_date#score#community_owned_date#answer_count#accepted_answer_id#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#answer_count#accepted_answer_id#protected_date#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#answer_count#bounty_amount#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#community_owned_date#answer_count#accepted_answer_id#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#community_owned_date#answer_count#accepted_answer_id#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#community_owned_date#answer_count#accepted_answer_id#body#title#tags#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',
            
        'question_id#last_edit_date#creation_date#last_activity_date#score#community_owned_date#answer_count#accepted_answer_id#closed_date#body#title#tags#closed_reason#view_count#owner#link#is_answered'
            =>'pulsestorm_stackexchange_api_question',     
        'user_id#display_name#reputation#user_type#profile_image#link#accept_rate'
            =>'pulsestorm_stackexchange_api_user',
        'user_id#display_name#reputation#user_type#profile_image#link'
            =>'pulsestorm_stackexchange_api_user',
        'display_name#user_type'
            =>'pulsestorm_stackexchange_api_anon_user',
        'user_id#display_name#reputation#user_type#link'
            =>'pulsestorm_stackexchange_api_user',
        );
    }
    
    function getTypeOfApiObject($value)
    {
        $legend = $this->getTypeLegend();
        $data   = get_object_vars($value);
        $key    = implode('#', array_keys($data));
        if(!array_key_exists($key, $legend))
        {
            exit('No type for: ' . $key . "\n");
        }
        return $legend[$key];
    }
    
    //uses hrustics to guess the type of object    
    function guessTypeOfApiObject($value)
    {
        //user, anon user, answer, comment, post, question, user
        $vars = get_object_vars($value);
        var_dump($vars);
        exit;
    }
    
    function testAllTypesInDataStore()
    {
        $data = $this
        ->getJsonContainer()
        ->loadDevelopmentData()
        ->getData();                
        
        $data = $this->arrayOfStringsToJson($data);        
        foreach($data as $key=>$value)
        {
            $type = $this->getTypeOfApiObject($value);
            foreach($value->items as $item)
            {
                $type = $this->getTypeOfApiObject($item);
                foreach(get_object_vars($item) as $key=>$value)
                {
                    if(!is_object($value))
                    {   
                        continue;
                    }
                    $type = $this->getTypeOfApiObject($value);
                    // if($type == 'api_unknown')
                    // {
                    //     var_dump($key);
                    //     var_dump($value);                        
                    // }
                    // exit(__FUNCTION__);
                }
            }
        }    
    }
    
    function getAllFieldsByType()
    {
        $types = array();
        foreach($this->getTypeLegend() as $fields=>$type)
        {
            if(!array_key_exists($type, $types))
            {
                $types[$type] = array();
            }
            $types[$type][] = $fields;
        }

        $type_fields = array();
        foreach($types as $type=>$fields)
        {
            $fields = implode('#',$fields);
            $type_fields[$type] = explode('#',$fields);            
            $type_fields[$type] = array_values( array_unique($type_fields[$type]) );
        }    
        return $type_fields;
    }
    
    function getFieldSqlDefinition($value)
    {
        $legend = array(
        'question_id'           =>'int unsigned not null',
        'answer_id'             =>'int unsigned not null',
        'creation_date'         =>'datetime not null',
        'last_activity_date'    =>'datetime not null',
        'score'                 =>'int not null',        
        'is_accepted'           =>'tinyint not null',    
        'body'                  =>'text not null',
        'owner'                 =>'NEEDS_ID',
        'owner_id'              =>'int unsigned not null',        
        'last_edit_date'        =>'datetime not null',
        'community_owned_date'  =>'datetime',
        'comment_id'            =>'int unsigned not null',
        'post_id'               =>'int unsigned not null',
        'edited'                =>'tinyint not null',        
        'reply_to_user'         =>'NEEDS_ID',        
        'reply_to_user_id'      =>'int unsigned',        
        'post_type'             =>'varchar(255) not null',        
        'link'                  =>'text not null',
        'answer_count'          =>'int not null',        
        'title'                 =>'text not null',
        'tags'                  =>'text',
        'view_count'            =>'int not null',
        'link'                  =>'text not null',
        'is_answered'           =>'tinyint not null',
        'accepted_answer_id'    =>'int unsigned',
        'closed_date'           =>'datetime',
        'closed_reason'         =>'varchar(255)',
        'protected_date'        =>'datetime',        
        'bounty_amount'         =>'int',
        'user_id'               =>'int unsigned not null',
        'display_name'          =>'text',
        'reputation'            =>'int not null',
        'user_type'             =>'varchar(255)',
        'profile_image'         =>'text',
        'accept_rate'           =>'int not null',        
        );
        
        if(!array_key_exists($value,$legend) || !$legend[$value])
        {
            exit("No Definition for $value"."\n");
            #return false;
        }
        return $legend[$value];
    }
    
    function taskCreateTableDefinitions()
    {        
        $type_fields = $this->getAllFieldsByType();        
        foreach($type_fields as $type=>$fields)
        {
            if($type == 'pulsestorm_stackexchange_api_list'){continue;};
            $table = array('CREATE TABLE ',$type,' (',"\n");                        
            foreach($fields as $field)
            {
                $definition = $this->getFieldSqlDefinition($field);
                if($definition == 'NEEDS_ID')
                {
                    $field = $field . '_id';
                    $definition = $this->getFieldSqlDefinition($field);
                }
                $table[] = $field;
                $table[] = ' ';
                $table[] = $definition;
                $table[] = ',';
                $table[] = "\n";
            }        
            
            array_pop($table);
            array_pop($table);
            $table[] = "\n";
            $table[] = ')';
            
            echo implode('',$table) . "\n\n";
        }    
    }
    
    function main($argv)
    {
        //this kicks off the downloading of all the information
        $this->taskFetchAllUserInformation();   

        //loads information into the container object from the file system
        ###$this->getJsonContainer()->loadDevelopmentData();
        
        //grabs all data from container ensures we know their types
        ###$this->testAllTypesInDataStore();
        
        //creates table definitions
        ###$this->taskCreateTableDefinitions();

//         $data = $this->getJsonContainer()->getData();
//         $data = $this->arrayOfStringsToJson($data);                
//         foreach($data as $key=>$value)
//         {  
//             $type = $this->guessTypeOfApiObject($value);
//             var_dump($type);
//         }
        
        echo "\nDONE\n";
    }    
}

if(isset($argv))
{
    $o = new PulsestormStackexchangeCrawler;    
    $o->setUserId('4668');
    
    $container  = new PulsestormJsonContainer;
    $o->setJsonContainter($container);
    
    $argv = isset($argv) ? $argv : array();
    $o->main($argv);
}
    

