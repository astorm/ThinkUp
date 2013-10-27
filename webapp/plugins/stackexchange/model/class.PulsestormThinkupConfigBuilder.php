<?php
class PulsestormThinkupConfigBuilder
{
    public function render($controller)
    {
        $path = THINKUP_WEBAPP_PATH.$this->_getConfigPath();
        $object = json_decode(file_get_contents($path));
        if(!isset($object->fields))
        {
            exit(__METHOD__ . '::' . __LINE__);
            return;
        }
        foreach($object->fields as $name=>$values)
        {
            if($name == 'end' || $name == 'example')
            {
                continue;
            }
            //validate all fields present
            $this->_validateAllFieldsPresent($values, $name);

            //remove null values
            $values = (array) $values;
            
            
            if(!$values['advanced'])
            {
                unset($values['advanced']);
            }
            foreach($values as $key=>$value)
            {
                if(is_null($value))
                {
                    unset($values[$key]);
                }
            }
            


            $values['name'] = $name;
            
            //validate based on text, select, or radio
            
            //add the option
            $controller->addPluginOption($this->_getTypeConstant($values['type']),$values);
            
            //add a header, if needed
            if(isset($values['header']))
            {
                $controller->addPluginOptionHeader($name, $values['header']);
            }
            
            //add a validation message, if needed            
            if(isset($values['validation_message']))
            {
                $controller->addPluginOptionRequiredMessage($name, $values['validation_message']);
            }
            
            //set not required
            if(isset($values['required']) && !$values['required'])
            {
                $controller->setPluginOptionNotRequired($name);
            }            
        }
    }
    
    protected function _getTypeConstant($type)
    {
        $values = array(
        'text'   =>PluginConfigurationController::FORM_TEXT_ELEMENT,
        'select' =>PluginConfigurationController::FORM_SELECT_ELEMENT,        
        'radio'  =>PluginConfigurationController::FORM_RADIO_ELEMENT,                
        );
        
        return $values[$type];
    }
    
    protected function _getConfigPath()
    {
        return 'plugins/stackexchange/etc/config.json';
    }
    
    protected $_all_fields = array('advanced','default_value','end','header','id','label','required','size','type','validation_message','validation_regex','value','values');
    protected function _validateAllFieldsPresent($values, $name)
    {
        if($name == 'end')
        {
            return;
        }
        $fields = array_keys(get_object_vars($values));
        sort($fields);        
        if($fields != $this->_all_fields)
        {
            throw new Exception("Missing field in $name (".$this->_getConfigPath().")");
        }
        return true;
    }
    
}