<?PHP
include_once("common.php");
if (!CheckAuthorization(PRIV_COLLECTIONADMIN, PRIV_SYSADMIN)) {  return;  }

$Plugin = $GLOBALS["G_PluginManager"]->GetPlugin("XmlrpcServer");

$all = _GetIncludeInFields();
$G_IncludeIn = array( 1 => 'Return',
		  				    2 => 'Detail',
		  					 3 => 'Insert',
		  					 4 => 'TextSearch',
		  					 5 => 'Filter',
		  					 6 => 'Order');	        

//if user clicked a button
if (isset($_POST["Submit"])) {
  switch ($_POST["Submit"]) {
    case "Save Changes":
      //check for required values
      $required = _GetDefaultIncludeIn(3);        
      foreach ($required as $FieldId => $FieldName){
        $key = "F_IncludeInInsert_".$FieldId;
        if (!isset( $_POST[$key] )) {
          $G_ErrorMessages[] = "<i>".$FieldName."</i> is required to Insert Resource.";
        } 
      }
    
      //if no errors found
      if (!isset($G_ErrorMessages)) {
        //save configuration
        //formar $G_AvailableF con lo que viene en POST
        unset($G_AvailableF); 
        $fieldId = 0;       
        foreach ($_POST as $key => $value){
        	 if(substr($key, 0, 11) == 'F_IncludeIn'){
        	 	list( , $inc_opt, $Id) = explode('_', $key);
    	      if($fieldId == $Id){
    		     $i += 1;
            }
    	      else {
    		     $i = 0;
            }
            $fieldId = $Id;
        	   $G_AvailableF[$Id][$i] = $value;
        	 }
        }
        $keys_inc = array_keys($G_IncludeIn);
        foreach ($keys_inc as $optId){
         foreach ($G_AvailableF as $fId => $arr_opt){
          if ( in_array($optId, $arr_opt) ) {
             $opt_used[] = $optId;
             break;
          } 
         }
        }
        $empty = array_diff($keys_inc, $opt_used);
        foreach($empty as $optId){
        	 $opt_val = $G_IncludeIn[$optId];  
          $G_ErrorMessages[] = "The column <i>".$opt_val."</i> is empty, select some fields.";
        }
        $DB = new Database();
        //delete old available fields         
        $DB->Query("DELETE FROM XmlrpcServerFields");
        //insert new available fields 
        foreach($G_AvailableF as $fId => $arr_inclIn){
          foreach($arr_inclIn as $includeIn){
        	   $DB->Query("INSERT INTO XmlrpcServerFields (FieldId, IncludeIn) VALUES ( $fId , $includeIn )");
          }
        }
      }
      else{
        $G_AvailableF = $all;
      }
      break;   
    case "Cancel":
      $G_AvailableF = $all;
      break;   
   }
   if (!isset($G_ErrorMessages))
     $AF->SetJumpToPage("P_XmlrpcServer_XmlrpcSConfig");
}
else{
   $G_AvailableF = $all;
}
?>
