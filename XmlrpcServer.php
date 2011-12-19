<?PHP
require_once("common.php");

class XmlrpcServer extends Plugin
{

    /**
     * Register information about this plugin.
     */
    public function Register()
    {
        $this->Name = "XmlrpcServer";
        $this->Version = "1.0.0";
        $this->Description = "Provides support for serving up resource records, classification  using xmlrpc.";
        $this->Author = "Yazna Garcia Vega";
        $this->Url = "http://";
        $this->Email = "yazna96@gmail.com";
        $this->Requires = array("CWISCore" => "2.1.0");
        $this->EnabledByDefault = FALSE;
    }

    function Install()
    {
        $DB = new Database();

        //create table to save the resource fields available to export with web server        
        $DB->Query("CREATE TABLE IF NOT EXISTS XmlrpcServerFields (FieldId INT NOT NULL, IncludeIn INT NOT NULL, UNIQUE  (FieldId, IncludeIn))");

		  $include_in = array( 1 => 'Return',
		  							  2 => 'Detail',
		  							  3 => 'Insert',
		  							  4 => 'TextSearch',
		  							  5 => 'Filter',
		  							  6 => 'Order');	        
        foreach($include_in as $key => $value) {        
          $fields = _GetDefaultIncludeIn($key); 
          //insert fields into XmlrpcServerFields, with IncludeIn  
          foreach($fields as $fId=>$fName) { 
            $DB->Query("INSERT INTO XmlrpcServerFields (FieldId, IncludeIn) VALUES ( $fId , $key )");
          }    
        }
    }

    /**
     * Hook the events into the application framework.
     * @return an array of events to be hooked into the application framework
     */
    public function HookEvents()
    {
        return array(
                "EVENT_COLLECTION_ADMINISTRATION_MENU" => "AddCollectionAdminMenuItems",
                );
    }
    function AddCollectionAdminMenuItems()
    {
        return array("XmlrpcSConfig" => "Xmlrpc Server Configuration");
    }
}

?>
