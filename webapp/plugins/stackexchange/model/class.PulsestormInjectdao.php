<?php
class PulsestormInjectdao 
{   
    static public function addDaos()
    {
        DAOFactory::$dao_mapping['PulsestormStackexchangeInstance']['mysql'] 
            = 'PulsestormStackexchangeInstanceMysql';        

        DAOFactory::$dao_mapping['PulsestormStackexchangeRaw']['mysql'] 
            = 'PulsestormStackexchangeRawMysql';        
            
        DAOFactory::$dao_mapping['PulsestormStackexchangeComment']['mysql'] 
            = 'PulsestormStackexchangeCommentMysql';               
            
        DAOFactory::$dao_mapping['PulsestormStackexchangeAnswer']['mysql'] 
            = 'PulsestormStackexchangeAnswerMysql';   

        DAOFactory::$dao_mapping['PulsestormStackexchangeQuestion']['mysql'] 
            = 'PulsestormStackexchangeQuestionMysql';

        DAOFactory::$dao_mapping['PulsestormStackexchangePost']['mysql'] 
            = 'PulsestormStackexchangePostMysql';   
            
        DAOFactory::$dao_mapping['PulsestormStackexchangeUser']['mysql'] 
            = 'PulsestormStackexchangeUserMysql';               

        DAOFactory::$dao_mapping['PulsestormStackexchangeAnonUser']['mysql'] 
            = 'PulsestormStackexchangeAnonUserMysql';     

        DAOFactory::$dao_mapping['PulsestormStackexchangeSites']['mysql']
            = 'PulsestormStackexchangeSitesMysql';    

        DAOFactory::$dao_mapping['PulsestormStackexchangeCrawlSync']['mysql']
            = 'PulsestormStackexchangeCrawlSyncMysql';                

        DAOFactory::$dao_mapping['PulsestormStackexchangeAccountids']['mysql']
            = 'PulsestormStackexchangeAccountidsMysql';                         

        DAOFactory::$dao_mapping['PulsestormStackexchangeAccount']['mysql']
            = 'PulsestormStackexchangeAccountMysql';                                  
         
        
    }
}