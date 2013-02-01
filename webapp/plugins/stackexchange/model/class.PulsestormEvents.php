<?php
class PulsestormEvents
{
    static protected $_observers=array();

    static public function dispatchEvent($name, $params=false)
    {
        foreach(self::_getObservers($name) as $observer)
        {
            $dao = DAOFactory::getDAO($observer->dao);
            $callback = array($dao, $observer->method);
            if(is_callable($callback))
            {
                $o          = new stdClass;
                $o->params  = $params;
                call_user_func_array($callback, array($o));
            }
            else
            {
                self::log("Could Not Call Observer: " . implode('::',array($observer->dao,$observer->method)));
            }
        }
    }
    
    static public function log($message)
    {
        var_dump($message);
    }
    
    static protected function _getObservers($name)
    {
        if(!self::$_observers)
        {
            self::$_observers = self::_loadObservers();
        }
        
        if(array_key_exists($name, self::$_observers))
        {
            return self::$_observers[$name];
        }
        return array();    
    }
    
    static protected function _loadObservers()
    {
        $path = THINKUP_WEBAPP_PATH.self::_getConfigPath();
        $object = json_decode(file_get_contents($path));
        if(!is_object($object))
        {
            throw new Exception($path . ' is not valid JSON.');
        }
        $observers = array();
        foreach($object->observers as $item)
        {            
            $key = $item->event_name;
            unset($item->event_name);
            if(!array_key_exists($key, $observers))
            {
                $observers[$key] = array();
            }
            $observers[$key][] = $item;
        }
        return $observers;
    }
    
    static protected function _getConfigPath()
    {
        return 'plugins/stackexchange/etc/observers.json';
    }
}