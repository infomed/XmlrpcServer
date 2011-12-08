<?php
require_once("lib/xmlrpc.inc");
require_once("lib/xmlrpcs.inc");

require_once("conexion.php");
require_once("common.php");

/**
 * GetResourceById: Get a cwis resource using a unique identifier.
 * @param resource code (integer)
 * @return Associative array containing resource information (field name  => field value).
 */
$GetResourceById_sig = array(array($xmlrpcString, $xmlrpcInt));
$GetResourceById_doc =<<<EOD
Get a cwis resource using a unique identifier.
Param:
  resource_id (integer)
Return:
 Array containing resource information.
EOD;

function GetResourceById($m) { 
  global $xmlrpcerruser;
  $err="";

  $resource_id = $m->getParam(0);
  if (!isset($resource_id) ) {
  	 return new xmlrpcresp(0, $xmlrpcerruser, "El codigo del recurso es un parametro obligatorio.");  
  }	
  // extract the value of the state number
  $id=$resource_id->scalarval(); 
  $id=trim($id);
  if (!ereg("^[0-9]{1,}$", $id)) {
	 return new xmlrpcresp(0, $xmlrpcerruser, 'El codigo de recurso debe ser un numero.');
  }

  //get the Resources field names to export with xmlrpc
  $available_fields = _GetAvailableFields();  
  if(!$available_fields ) {
    return new xmlrpcresp(0, $xmlrpcerruser, "La configuracion del plugin XmlrpcServer de CWIS no permite extraer ninguna informacion. Consulte al administrador de CWIS.");
  }
   
  //retornar los datos de resource definidos en la opcion 2 => 'Detail' 
  $return_fields = _GetIncludeIn(2); 
  /*if (!count($return_fields)){
  	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los datos de detalle de un recurso. Consulte al administrador de CWIS.");  
  }*/ 

  list($select_fields, $ret_nameclass) = _PrepareReturnFields($return_fields, $available_fields); 
  $select_str = implode(",", $select_fields);


  $select="SELECT $select_str FROM Resources WHERE (ResourceId=$id and ReleaseFlag =1)";	
  //return new xmlrpcresp(0, $xmlrpcerruser, $select); 
  $result=@mysql_query($select);
  if(@mysql_num_rows($result) == 0) 
    $err="Error recuperando recurso de cwis. No existe el codigo o no esta aprobado por los administradores del sitio";
  else {
    $row = @mysql_fetch_array($result); 
	 $resource_values = array();  
    $resource_values["ResourceId"] = new xmlrpcval($id, "int");
    $cwis_path = "http://".$_SERVER["HTTP_HOST"]."/SPT--FullRecord.php?ResourceId=".$id; 
    $resource_values["CwisPath"] = new xmlrpcval($cwis_path, "string");

    //recorre los campos de Resource que se deben devolver          
    foreach($select_fields as $fname) {
      switch($fname) {
        case 'AddedById':        
        case 'LastModifiedById':        
          $str_sql = "SELECT UserName FROM APUsers WHERE UserId = ". $row[$fname];
          $res = @mysql_query($str_sql);
          if (@mysql_num_rows($res) < 1) {
            //no user by that name
            $user_name = 'Unknown';
          }
          else {
            $record = @mysql_fetch_row($res);
            $user_name = $record[0];
          }
          $resource_values[$fname] = new xmlrpcval($user_name, "string");
          break;
        default:      
          $resource_values[$fname] = new xmlrpcval($row[$fname], "string");
          break;
      }
    }

    $nameclass_values = _GetResourceNameClassValues($id, $ret_nameclass);   

    $resource = array_merge($resource_values, $nameclass_values); 
	 $retStruct=new xmlrpcval( $resource, "struct");
	 return new xmlrpcresp($retStruct);
  }

  // if we generated an error, create an error return response
  if ($err) {
  	 return new xmlrpcresp(0, $xmlrpcerruser, $err);  
  }
}
/**
 * ListResources: List Resources 
 * @param  order_fields (struct): the id of fields and ASC or DESC for each one to order results        
 *         start: start number of record (not required)
 *         count: max number of records to return (not required)  
 * @return Array containing Resource Information:
 *     (total => the total of list elements,
 *      result => array of arrays with the Resource information)
 */
$ListResources_sig=array(array($xmlrpcString, $xmlrpcStruct, $xmlrpcInt, $xmlrpcInt));
$ListResources_doc=<<<EOD
ListResources: List Resources 
Param:
  order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
  start: start number of record (not required)
  count: max number of records to return (not required)  
Return: Array containing Resource Information:
  (total => the total of list elements,
   result => array of arrays  with the Resource information)
EOD;

function ListResources($m)
{
	global $xmlrpcerruser;
	$err="";

	//Get params
   //filter_fields??	
  //retornar los datos de resource definidos en la opcion 1 => 'Return' 
  $return_fields = _GetIncludeIn(1); 
  if (!count($return_fields)){
  	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los datos de una lista de recursos (RETURN). Consulte al administrador de CWIS.");  
  } 

   $order_fields = $m->getParam(0);
   $order_fields = $order_fields->getval();

  	$start=$m->getParam(1); 
  	$start = (int)$start->scalarval();
  	$limit=$m->getParam(2); 
  	$limit = (int)$limit->scalarval();
  	
  	$xmlrpcresponse = _GetResources("", array(), $return_fields, array(), $order_fields, $start, $limit);
   
   return $xmlrpcresponse;
}

/**
 * ListResourcesByVocabElem: List Resources associated to a Vocabulary Element 
 * @param  cod_vocab (integer): code of Vocabulary (FieldId)  
 *         cod_elem (integer): code of Vocabulary Element (ControlledNameId or ClassificationId)
 *         order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
 *         start (integer): star number of record (not required)
 *         count (integer): max number of records to return (not required)  
 * @return (array) containing:
 *     (total => the total of Resources associated to a Vocabulary Element,
 *      result => array of arrays with the Resources information (cod_recurso, titulo, url, descripcion))
 *     ordered by cod_recurso DESC
 */
$ListResourcesByVocabElem_sig=array(array($xmlrpcString, $xmlrpcInt, $xmlrpcInt, $xmlrpcStruct, $xmlrpcInt, $xmlrpcInt));
$ListResourcesByVocabElem_doc =<<<EOD
ListResourcesByVocabElem: List Resources associated to a Vocabulary Element 
Param:
  cod_vocab (integer): code of Vocabulary (FieldId)  
  cod_elem (integer): code of Vocabulary Element (ControlledNameId or ClassificationId)
  order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
  start (integer): start number of record (not required)
  count (integer): max number of records to return (not required)  
Return (array) containing:
  (total => the total of Resources associated to a Vocabulary Element,
   result => array of arrays with the Resource information (cod_recurso, titulo, url, descripcion))
     ordered by cod_recurso DESC
EOD;

function ListResourcesByVocabElem($m)
{
	global $xmlrpcerruser;
	$err="";

	//Get params
  	$cod_vocab=$m->getParam(0);
  	$cod_vocab= $cod_vocab->scalarval();
  	$cod_elem=$m->getParam(1); 
  	$cod_elem = $cod_elem->scalarval();

   if(!is_numeric($cod_vocab) OR !is_numeric($cod_elem)){
     $err="El codigo de vocabulario y el del elemento del vocabulario son numeros y son obligatorios";
  	  return new xmlrpcresp(0, $xmlrpcerruser, $err);
   }
  	
  	$filter_fields = array($cod_vocab => $cod_elem);

   $order_fields = $m->getParam(2);
   $order_fields = $order_fields->getval();

  	$start=$m->getParam(3); 
  	$start = (int)$start->scalarval();
  	$limit=$m->getParam(4); 
  	$limit = (int)$limit->scalarval();
  	
   //retornar los datos de resource definidos en la opcion 1 => 'Return' 
   $return_fields = _GetIncludeIn(1); 
   if (!count($return_fields)){
   	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los datos de una lista de recursos (RETURN). Consulte al administrador de CWIS.");  
   } 

   $xmlrpcresponse = _GetResources("", array(), $return_fields, $filter_fields, $order_fields, $start, $limit);
   
   return $xmlrpcresponse;
}
/**
 * SimpleSearch: Search Resources by text
 * @param  text (string): text to search  
 *         order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
 *         start: start number of record (not required)
 *         count: max number of records to return (not required)  
 * @return Array containing Resource Information:
 *     (total => the total of search results,
 *      result => array of arrays with the Resource information)
 */

$SimpleSearch_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcStruct, $xmlrpcInt, $xmlrpcInt));
$SimpleSearch_doc=<<<EOD
SimpleSearch: Search Resources by text (on fields Title and Description).
Param:
  text (string): text to search  
  order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
  start: start number of record (not required)
  count: max number of records to return (not required)  
Return Array containing Resource Information:
  (total => the total of search results,
   result => array of arrays with the Resource information )
EOD;

function SimpleSearch($m)
{
	global $xmlrpcerruser;
	$err="";

	//Get Params
	$text=$m->getParam(0);
	$text= $text->scalarval();

   $order_fields = $m->getParam(1);
   $order_fields = $order_fields->getval();

	$start=$m->getParam(2);
	$start= (int)$start->scalarval();

	$limit=$m->getParam(3);
	$limit= (int)$limit->scalarval();

   //campos donde buscar texto definidos en la opcion 4 => 'TextSearch' 
   $text_fields = _GetIncludeIn(4); 
   if (!count($text_fields)){
   	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los campos donde buscar texto (TEXTSEARCH). Consulte al administrador de CWIS.");  
   } 
   //retornar los datos de resource definidos en la opcion 1 => 'Return' 
   $return_fields = _GetIncludeIn(1); 
   if (!count($return_fields)){
   	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los datos de una lista de recursos (RETURN). Consulte al administrador de CWIS.");  
   } 

   $xmlrpcresponse = _GetResources($text, $text_fields, $return_fields, array(), $order_fields, $start, $limit);
   
   return $xmlrpcresponse;
}

/**
 * AdvancedSearch: Search Resources by text and filter
 * @param  text (string): text to search  
 *         filter_fields (struct): the id of fields and the criteria for each one to filter the search     
 *         order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
 *         start: start number of record (not required)
 *         count: max number of records to return (not required)  
 * @return Array containing Resource Information:
 *     (total => the total of search results,
 *      result => array of arrays with the Resource information)
 */

$AdvancedSearch_sig =array(array($xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcStruct,$xmlrpcInt,$xmlrpcInt));
$AdvancedSearch_doc =<<<EOD
AdvancedSearch: Search Resources by text and filter
Param  
   text (string): text to search  
   filter_fields (struct): the id of fields and the criteria for each one to filter the search     
   order_fields (struct): the id of fields and ASC or DESC for each one to order results, default no order       
   start: start number of record (not required)
   count: max number of records to return (not required)  
Return Array containing Resource Information:
   (total => the total of search results,
    result => array of arrays with the Resource information)
EOD;


function AdvancedSearch($m) {
	global $xmlrpcerruser;
	$err="";

	//Get Params
	$text=$m->getParam(0);
	$text= $text->scalarval();

   $filter_fields = $m->getParam(1);
   $filter_fields = $filter_fields->getval();//array de arrays
   foreach($filter_fields as $fId => $val_op){
     foreach($val_op as $key => $item){ 
       $filter_fields[$fId][$key] = $item->scalarval();
     }
   }
   $order_fields = $m->getParam(2);
   $order_fields = $order_fields->getval();

	$start=$m->getParam(3);
	$start= (int)$start->scalarval();

	$limit=$m->getParam(4);
	$limit= (int)$limit->scalarval();

   //campos donde buscar texto definidos en la opcion 4 => 'TextSearch' 
   $text_fields = _GetIncludeIn(4); 
   if (!count($text_fields)){
   	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los campos donde buscar texto (TEXTSEARCH). Consulte al administrador de CWIS.");  
   } 
   //retornar los datos de resource definidos en la opcion 1 => 'Return' 
   $return_fields = _GetIncludeIn(1); 
   if (!count($return_fields)){
   	 return new xmlrpcresp(0, $xmlrpcerruser, "En el plugin XmlrpcServer de CWIS no se han definido los datos de una lista de recursos (RETURN). Consulte al administrador de CWIS.");  
   } 
//$err = /*$text .'--'. print_r($text_fields,TRUE) .'--'. print_r($return_fields,TRUE) .'--'. */print_r($filter_fields,TRUE) .'--'/*. print_r($order_fields,TRUE) .'--'. $start .'--'. $limit*/;
// return new xmlrpcresp(0, $xmlrpcerruser, $err);

   $xmlrpcresponse = _GetResources($text, $text_fields, $return_fields, $filter_fields, $order_fields, $start, $limit);
   
   return $xmlrpcresponse;
   
}
/**
 * InsertResource: Allows client to insert a Resource in Cwis 
 * @param  fields_values (struct): Resource data to insert, associative array (fieldId => field Value)       
 *         user_id (int): identificador del usuario que va a insertar el recurso
 *		     user_passw (string): password encriptado del usuario
 * @return 1: if Resource and all vocabularies were insert ok
 *         2: if Resource was insert ok, but some vocabularies no
 *         error message if some error ocurr 
 */
$InsertResource_sig=array(array($xmlrpcString, $xmlrpcStruct, $xmlrpcInt, $xmlrpcString));
$InsertResource_doc=<<<EOD
InsertResource: Allows client to insert a Resource in Cwis 
Param: 
 fields_values (struct): Resource data to insert, associative array fieldId and field Value       
 user_id (int): identificador del usuario que va a insertar el recurso
 user_passw (string): password encriptado del usuario
Return: 
 Array containing Resource Information:
EOD;

function InsertResource($m) {

  global $xmlrpcerruser;
  $err="";
 
	
   $available_insert = _GetIncludeIn(3);        
	//Get Params
   $param_fields_values = $m->getParam(0);
   $param_fields_values = $param_fields_values->getval();

   $fields_values = array();
   foreach($param_fields_values as $fId => $val){
    if(in_array($fId, $available_insert)){//fields with Insert=1
     if(is_array($val)){
       foreach($val as $key => $item){ 
         $fields_values[$fId][$key] = $item->scalarval();
       }
     }
     else {
       $fields_values[$fId] = $val;
     }
    }
   }
   $user_id = $m->getParam(1);
   $user_id = $user_id->getval();
   $user_passw = $m->getParam(2);
   $user_passw = $user_passw->getval();
   
   $str_sql = "SELECT UserPassword FROM APUsers WHERE UserId = $user_id";
   $res = @mysql_query($str_sql);
  
   if (@mysql_num_rows($res) < 1) {
     //no user by that Id
  	  return new xmlrpcresp(0, $xmlrpcerruser, 'No existe el usuario.');
   }
   else {
     $record = @mysql_fetch_row($res);
     $db_passw = $record[0];
     //se chequea password
     if ($db_passw != $user_passw){ 
  	    return new xmlrpcresp(0, $xmlrpcerruser, "Usuario existe, Contraseña incorrecta.");
     }
   }
   
   $user_priv = _GetUserPrivileges($user_id);
   if (!in_array(3, $user_priv) AND !in_array(12, $user_priv)) {        
     return new xmlrpcresp(0, $xmlrpcerruser, "El usuario no tiene permiso para insertar recurso en CWIS.");
   }

   $available_fields = _GetAvailableFields();  
   if(!$available_fields ) {
  	  return new xmlrpcresp(0, $xmlrpcerruser, "La configuracion del plugin XmlrpcServer de CWIS no permite extraer ninguna informacion. Consulte al administrador de CWIS.");
   }
   list($all, $resource, $nameclass, $multiple) = $available_fields;
   $resource_keys = array_keys($resource);
   $nameclass_keys = array_keys($nameclass);

   $insert_keys = array_keys($fields_values);
   
   //check required fields    
   $required = _GetDefaultIncludeIn(0);        
   foreach ($required as $reqId => $fname){
   	if( $fname != 'Added By Id' && !in_array($reqId,$insert_keys)){
  		  $missing_req[] = $fname;// field name
   	}
   }
   if(isset($missing_req)){
	  return new xmlrpcresp(0, $xmlrpcerruser, "Son obligatorios los campos: ". implode(", ", $missing_req));
   }
   
   $system_fields = ",ReleaseFlag,AddedById,LastModifiedById,DateOfRecordCreation,DateOfRecordRelease,DateLastModified";

   if(!$user_priv OR !in_array(7, $user_priv)){//release flag privilege
     $system_values = ",0,";//not published
   }
   else {   
     $system_values = ",1,";//published   
   }
   $system_values .=   "$user_id,$user_id,now(),now(),now()";

   foreach($resource as $fId => $value){
	  if( in_array($fId, $insert_keys)){
       $res_fields[] = $resource[$fId][0];//field name
       if(in_array($resource[$fId][1], array('Text', 'Paragraph', 'Url'))) 
         $res_values[] = "'". $fields_values[$fId] ."'";
       else 
         $res_values[] = $fields_values[$fId];
     }
   }
	$res = @mysql_query("SELECT MAX(ResourceId) FROM Resources");
	$max = @mysql_fetch_row($res);
	$ResourceId = $max[0] + 1;

   $str_res_fields = "(ResourceId, ". implode(", ", $res_fields) . $system_fields .")";
   $str_res_values = "($ResourceId, ". implode(", ", $res_values) . $system_values .")";

   //Insert into Resources table  
   $str_query = "INSERT INTO Resources ". $str_res_fields ." VALUES ". $str_res_values;
   $query1=@mysql_query($str_query);
   if(!$query1)
  	  return new xmlrpcresp(0, $xmlrpcerruser, "Ocurrió un error y no se insertó el recurso.");
   
   $ret = 1;   
   foreach($nameclass as $fId => $value){
	  if( in_array($fId, $insert_keys)){
       switch($value[1]){//field type
         case 'ControlledName':
         case 'Option':
           $table = "ResourceNameInts";
           $field = "ControlledNameId";   
           break;
         case 'Tree':
           $table = "ResourceClassInts";
           $field = "ClassificationId";   
           break;
       }
       $str_insert = "INSERT INTO $table (ResourceId, $field) VALUES ($ResourceId,";   
       if(is_array($fields_values[$fId])){
	      if( $value[1] == 'tree' OR in_array($fId, $multiple)){//allow multiple, ??tree even if no multiple 
           foreach($fields_values[$fId] as $fval){
           	 if(_checkVocabValue($fId, $fval, $field))
	            $res = @mysql_query($str_insert . $fval .")");
	          else 
	            $ret = 2;//no se inserta pq no existe
     	     }	
         }	
         else {//if array but no multiple insert only the first value
           $ret = 3;//insert only the first 
           $fval = $fields_values[$fId][0];           
           if(_checkVocabValue($fId, $fval, $field))
	          $res = @mysql_query($str_insert . $fval .")");
	        else 
	          $ret = 2;//no se inserta pq no existe
         }	
       }
       else {//if no array
         $fval = $fields_values[$fId];           
         if(_checkVocabValue($fId, $fval, $field))
	        $res = @mysql_query($str_insert . $fval .")");
	      else 
	        $ret = 2;//no se inserta pq no existe
       }	
     }
   }

   $ok = new xmlrpcval($ret, "int");
	return new xmlrpcresp($ok);    

}

/**
 * ListAvailableFields: Lists a CWIS fields available to export, and their properties
 * @return array containing available fields information (FieldId, FieldName, FieldType, 
 *               Description, fields for the edit form).
 */
$ListAvailableFields_sig = array(array($xmlrpcString));
$ListAvailableFields_doc =<<<EOD
ListAvailableFields: Lists a CWIS fields available to export, and their properties
Return Array containing available fields information (FieldId, FieldName, FieldType, 
Description, fields for the edit form).
EOD;

function ListAvailableFields() { 
  global $xmlrpcerruser;
  $err="";

  $FIncludeIn = _GetIncludeInFields();
  $include_in = array( 1 => 'Return',
  							  2 => 'Detail',
  							  3 => 'Insert',
  							  4 => 'TextSearch',
  							  5 => 'Filter',
  							  6 => 'Order');	        
  
  $arrtmp=array();
  //first field is ResourceId, it isn't in Metadata fields
  $arrtmp[] = new xmlrpcval( array("FieldId" => new xmlrpcval(-1, "int"),
					                    "FieldName" => new xmlrpcval('ResourceId', "string"),
					                    "FieldType" => new xmlrpcval('Number', "string"),
					                    "Description" => new xmlrpcval('The Id of Resource', "string"),
					                    "Optional" => new xmlrpcval(0, "int"),
					                    "Return" => new xmlrpcval(1, "int"),
					                    "Detail" => new xmlrpcval(1, "int"),
					                    "Insert" => new xmlrpcval(0, "int"),
					                    "TextSearch" => new xmlrpcval(0, "int"),
					                    "Filter" => new xmlrpcval(0, "int"),
					                    "Order" => new xmlrpcval(0, "int"),//?
					                    "AllowMultiple" => new xmlrpcval(0, "int"),
					                    "TextFieldSize" => new xmlrpcval(NULL, "int"),
					                    "MaxLength" => new xmlrpcval(NULL, "int"),
					                    "DefaultValue" => new xmlrpcval(NULL, "string"),
					                    "MinValue" => new xmlrpcval(NULL, "int"),
					                    "MaxValue" => new xmlrpcval(NULL, "int"),
					                    "DisplayOrderPosition" => new xmlrpcval(NULL, "int"),
					                    "EditingOrderPosition" => new xmlrpcval(NULL, "int")
					                   ), "struct");
  $retStruct=new xmlrpcval();
  
  //get the Resources fields to export with xmlrpc
  $select="SELECT DISTINCT MetadataFields.FieldId, FieldName, FieldType, Description, Optional, "
           ."AllowMultiple, TextFieldSize, MaxLength, DefaultValue, MinValue, MaxValue, "
           ."DisplayOrderPosition, EditingOrderPosition " 
           ."FROM MetadataFields, XmlrpcServerFields "
           ."WHERE (MetadataFields.FieldId = XmlrpcServerFields.FieldId) "
           ."AND MetadataFields.Enabled = 1 AND MetadataFields.Viewable = 1";
   $result = @mysql_query($select);
   $total = @mysql_num_rows($result); 
	if ($total) {
	  while ($field = @mysql_fetch_row($result)){
       switch ($field[2]){
         case "Date":
           //NormalizeFieldNameForDB
           $db_fname = preg_replace("/[^a-z0-9]/i", "", $field[1]) ."Begin";
           break;  
         case 'ControlledName':
         case 'Option':
         case 'Tree':
           $db_fname = $field[1];
           break;
         default:    
           //NormalizeFieldNameForDB
           $db_fname = preg_replace("/[^a-z0-9]/i", "", $field[1]);
       }
       $fId = $field[0];
       $struct_elem = array("FieldId" => new xmlrpcval($fId, "int"),
					             "FieldName" => new xmlrpcval($db_fname, "string"),
					             "FieldType" => new xmlrpcval($field[2], "string"),
					             "Description" => new xmlrpcval($field[3], "string"),
					             "Optional" => new xmlrpcval($field[4], "int"),
					             "AllowMultiple" => new xmlrpcval($field[5], "int"),
					             "TextFieldSize" => new xmlrpcval($field[6], "int"),
					             "MaxLength" => new xmlrpcval($field[7], "int"),
					             "DefaultValue" => new xmlrpcval($field[8], "string"),
					             "MinValue" => new xmlrpcval($field[9], "int"),
					             "MaxValue" => new xmlrpcval($field[10], "int"),
					             "DisplayOrderPosition" => new xmlrpcval($field[11], "int"),
					             "EditingOrderPosition" => new xmlrpcval($field[12], "int")
					            );
	    //en que opciones esta incluido el campo
	    foreach( $include_in as $opt_name){//se inicializan en 0
	    	$struct_elem[$opt_name] = new xmlrpcval(0, "int");
	    }
	    foreach( $FIncludeIn[$fId] as $value){//se ponen a 1 los seleccionados
	    	$opt_name = $include_in[$value];
	    	$struct_elem[$opt_name] = new xmlrpcval(1, "int");
	    }
	    $arrtmp[] = new xmlrpcval( $struct_elem, "struct");
					        
	  }
     @mysql_free_result($result);

     $retStruct=new xmlrpcval(array("total" => new xmlrpcval($total, "int"),
                                    "result"=> new xmlrpcval($arrtmp, "array")), 
                           "struct");
     return new xmlrpcresp($retStruct);
	} 
	else {
	  return new xmlrpcresp(0, $xmlrpcerruser, "No hay campos disponibles para exportar con el ws.");
	} 
}

/**
 * ListVocabularies: List cwis ControlledName, Option and Tree fields available to export.
 * @param (string) to filter vocabularies, posible values: 'Name' (to get ControlledName and Option), 
 *        'Tree', if no value return ControlledName, Option and Tree,  (not required)  
 * @return (array) containing:
 *     (total => the total of ControlledName, Option and Tree fields,
 *      result => array of arrays with vocabulary available to export information (FieldId, FieldName, FieldType))
 */
$ListVocabularies_sig = array(array($xmlrpcString, $xmlrpcString));
$ListVocabularies_doc = <<<EOD
List cwis ControlledName, Option and Tree fields available to export.
Param (string) to filter vocabularies, posible values: 'Name' (to get ControlledName and Option), 
      'Tree', if no value return ControlledName, Option and Tree,  (not required)  
Return:
- Array containing:
  (total => the total of ControlledName, Option and Tree fields,
   result => array of arrays with vocabulary available to export information (FieldId, FieldName, FieldType), (codigo, nombre, tipo))
EOD;

function ListVocabularies($m)
{
  global $xmlrpcerruser;
  $err="";

  $param=$m->getParam(0);
  $filter= $param->scalarval();

  $arrtmp=array();

  //get the ControlledNames to export with xmlrpc
  $fname_select="SELECT DISTINCT MetadataFields.FieldId, FieldName, FieldType FROM MetadataFields, XmlrpcServerFields "
           ."WHERE (MetadataFields.FieldId = XmlrpcServerFields.FieldId) "
           ."AND MetadataFields.Enabled = 1 AND MetadataFields.Viewable = 1 ";
  if(isset($filter)){
    $filter = trim($filter);
    switch($filter){
    	case 'Name':
    	  $fname_select .= "AND (MetadataFields.FieldType = 'ControlledName' OR MetadataFields.FieldType = 'Option')";
    	  break;
    	case 'Tree':
    	  $fname_select .= "AND (MetadataFields.FieldType = 'Tree')";
        break;    
      default:	
    	  $fname_select .= "AND (MetadataFields.FieldType = 'ControlledName' OR MetadataFields.FieldType = 'Option' OR MetadataFields.FieldType = 'Tree')";
    	  break;
    }  
  }
  else {   
    $fname_select .= "AND (MetadataFields.FieldType = 'ControlledName' OR MetadataFields.FieldType = 'Option' OR MetadataFields.FieldType = 'Tree')";
  }
  $fname_result = @mysql_query($fname_select);
  //return new xmlrpcresp(0, $xmlrpcerruser, $fname_select);
  $arrtmp = array();
  $total = @mysql_num_rows($fname_result); 

  if ($total) {
	 while ($field = @mysql_fetch_row($fname_result)){
	   $arrtmp[] = new xmlrpcval( array("FieldId" => new xmlrpcval($field[0], "int"),
					                        "FieldName" => new xmlrpcval($field[1], "string"),
					                        "FieldType" => new xmlrpcval($field[2], "string")
					                       ), "struct");
    }
  } 
  else {
    return new xmlrpcresp(0, $xmlrpcerruser, "No hay vocabularios disponibles para exportar con el ws.");
  } 
  	
  @mysql_free_result($fname_result);

  $retStruct=new xmlrpcval(array("total" => new xmlrpcval($total, "int"),
                                 "result"=> new xmlrpcval($arrtmp, "array")), 
                           "struct");
  return new xmlrpcresp($retStruct);
}

/**
 * ListVocabularyElements: List Elements of a Vocabulary (ControlledName, Option, Tree).
 * @param  cod (integer): code of Vocabulary (FieldId), according to 'FieldId' returned by ListVocabularies method  
 *         parent: used only for trees, parentId of the tree elements to recover, default 0 (all elements), if -1 (first branch)
 *         start (integer): start number of records (not required)
 *         count (integer): max number of records to return (not required)  
 * @return (array) containing:
 *     (total => the total of Vocabulary Elements,
 *      result => array of arrays with the Elements information: VocId, ElemId, Name
 *                          if Tree also: ParentId, ResourceCount)
 *     ordered alphabetically
 */
$ListVocabularyElements_sig = array(array($xmlrpcString, $xmlrpcInt, $xmlrpcInt, $xmlrpcInt, $xmlrpcInt));
$ListVocabularyElements_doc = <<<EOD
List Elements of a Vocabulary (ControlledName, Option, Tree).
Param:
  cod (integer): code of Vocabulary (FieldId), according to 'FieldId' returned by ListVocabularies method  
  parent: used only for trees, default 0 (all elements), if -1 (initial branch)
  start: start number of records (not required)
  count: max number of records to return (not required)  
Return: Array containing
  (total => the total of Vocabulary Elements,
   result => array of arrays with the Elements information: VocId, ElemId, Name 
        if Tree also return ParentId, ResourceCount
  ordered alphabetically
EOD;

function ListVocabularyElements($m)
{
  global $xmlrpcerruser;
  $err="";

  $codigo=$m->getParam(0);
  $field_id= $codigo->scalarval();
 
  $parent=$m->getParam(1);
  $parent = (int) $parent->scalarval();
  if(!isset($parent)) {
  	 $parent = 0;//all vocabulary elements
  }	 

  $start=$m->getParam(2); 
  $start = $start->scalarval();

  $limit=$m->getParam(3); 
  $limit = $limit->scalarval();

  $arrtmp=array();

  if(is_numeric($field_id)){
    $ftype_sel="SELECT FieldType FROM MetadataFields, XmlrpcServerFields "
           ."WHERE (MetadataFields.FieldId = XmlrpcServerFields.FieldId) "
           ."AND MetadataFields.Enabled = 1 AND MetadataFields.Viewable = 1 "
           ."AND (MetadataFields.FieldType = 'ControlledName' OR MetadataFields.FieldType = 'Option' OR MetadataFields.FieldType = 'Tree') "
           ."AND MetadataFields.FieldId = $field_id";;
    $ftype_res = @mysql_query($ftype_sel);
  
    if(@mysql_num_rows($ftype_res)){ 
      $type = @mysql_fetch_row($ftype_res);
      switch($type[0]) {
        case 'ControlledName':
        case 'Option':	           
	       $select= "SELECT COUNT(*) FROM ControlledNames WHERE FieldId = $field_id";
	       break;
	     case 'Tree':
	       $select= "SELECT COUNT(*) FROM Classifications WHERE FieldId = $field_id "; 
	                //."AND ResourceCount != 0 ";
            if($parent != 0){
              $select .= "AND ParentId = $parent ";
            }
	       break;
	   }
      @mysql_free_result($ftype_res);

	   $res = @mysql_query($select);
      $tot_arr = @mysql_fetch_row($res);
      $total = $tot_arr[0];

      if ($total) {
        switch($type[0]) {
          case 'ControlledName':
          case 'Option':	           
            $select = "SELECT ControlledNameId AS ElemId, ControlledName AS Name FROM ControlledNames "
                     ."WHERE FieldId  = $field_id "
	                  ."ORDER BY ControlledName ASC "; //ordenar alfabeticamente
	         break;
	       case 'Tree':
            $select = "SELECT ClassificationId AS ElemId, SegmentName AS Name, ParentId, ResourceCount FROM Classifications "
                     ."WHERE FieldId = $field_id ";
                     //."AND ResourceCount != 0 "
            if($parent != 0){
              $select .= "AND ParentId = $parent ";
            }
            $select .= "ORDER BY SegmentName "; //ordenar alfabeticamente
	         break;
	     }
	 
	     if ((isset($limit) && $limit > 0) && isset($start)){
          $select .= " LIMIT $start, $limit";
        }
        //return new xmlrpcresp(0, $xmlrpcerruser, $select);

	     $res=@mysql_query($select);
        if (!@mysql_num_rows($res)) {//Si fallo la consulta asigna mensaje de error
	       $err="Existen elementos pero no se pudieron recuperar, revise START y LIMIT";
	     }
        else {     	
          while($row=@mysql_fetch_array($res)) {
          	$elem = array("VocId" => new xmlrpcval($field_id, "int"),
          	              "ElemId" => new xmlrpcval($row["ElemId"], "int"),
						        "Name" => new xmlrpcval($row["Name"], "string"));
			   if($type[0] == 'Tree'){
			     $elem["ParentId"] = new xmlrpcval($row["ParentId"], "int");
			     $elem["ResourceCount"] = new xmlrpcval($row["ResourceCount"], "int");
			   }  
		      $arrtmp[] = new xmlrpcval( $elem, "struct");
	       }
          @mysql_free_result($res);
          $retStruct=new xmlrpcval(array("total" => new xmlrpcval($total, "int"),
                                         "result"=> new xmlrpcval($arrtmp, "array")), 
                                    "struct");
	       return new xmlrpcresp($retStruct);
        }//else
      }//total
      else{  
        $err="El vocabulario no tiene elementos";
      }
    }//type
    else{  
      $err="El vocabulario no existe o no esta permitida su exportacion";
    }
  }//numeric
  else{  
    $err="El codigo del vocabulario debe ser un numero";
  }
  
  if ($err) {
  	 return new xmlrpcresp(0, $xmlrpcerruser, $err);
  }
}
/**
 * UserInfo: Checks if user exists in DB, and return some information
 * @param  UserName (string)
 * @return (array) (UserId, Salt, Privilege) of user if exist or error if not  
 */
$UserInfo_sig = array(array($xmlrpcString, $xmlrpcString));
$UserInfo_doc = <<<EOD
Checks if user exists in DB
Param:  UserName (string)
Return: (array) (UserId, Salt, Privilege) of user if exist or error if not  
EOD;
function UserInfo($m) { 
  global $xmlrpcerruser;
  $err="";

  $UserName=$m->getParam(0);
  $UserName= $UserName->scalarval();

  // if user not found in DB
  $str_sql = "SELECT UserId, UserPassword FROM APUsers" 
                ." WHERE UserName = '". addslashes(trim($UserName))."'";
  $res = @mysql_query($str_sql);
  
  if (@mysql_num_rows($res) < 1) {
    //no user by that name
  	 return new xmlrpcresp(0, $xmlrpcerruser, 'No existe usuario con ese username.');
  }
  else {
    $record = @mysql_fetch_row($res);
    $user_id = $record[0];
    $arrtmp = array("UserId" => new xmlrpcval($user_id, "int"),
			           "Salt" => new xmlrpcval($record[1], "string"));

    $str_sql = "SELECT Privilege FROM APUserPrivileges WHERE UserId = $user_id";  
    $res = @mysql_query($str_sql);
    if (@mysql_num_rows($res)) {
	   while($row=@mysql_fetch_row($res)) {
        $privileges[] = new xmlrpcval($row[0], "int");
      }
      $arrtmp['Privilege'] = new xmlrpcval($privileges, "array");
    }

    $ret=new xmlrpcval($arrtmp, "struct");
    
	 return new xmlrpcresp($ret);
  }
}	


/**
 * _GetResources: Auxiliary Function, common to all methods to get resources as search, list
 *                return (xmlrpc) struct with resources or error
 * @param  text (string): text to search  
 *         text_fields (array): the id of fields where to search the text, default (Title and Description)      
 *         return_fields (array): the id of fields to return as resource information, default (id, title, url, description)
 *         filter_fields (struct): the id of fields and the criteria for each one to filter resources     
 *         order_fields (struct): the id of fields and ASC or DESC for each one to order results, default Tile ASC        
 *         start: start number of record (not required)
 *         count: max number of records to return (not required)  
 * @return Array containing Resource Information:
 *     (total => the total of search results),
 *      result => array of arrays with the Resource information)
 */
function _GetResources($text, $text_fields, $return_fields, $filter_fields, $order_fields, $start, $limit) {
	global $xmlrpcerruser;
   //get the Resources field names to export with xmlrpc
   $available_fields = _GetAvailableFields();  
   if(!$available_fields ) {
  	  return new xmlrpcresp(0, $xmlrpcerruser, "La configuracion del plugin XmlrpcServer de CWIS no permite extraer ninguna informacion. Consulte al administrador de CWIS.");
   }

   $text_condition = _PrepareTextSearch($text, $text_fields, $available_fields);

   list($select_fields, $ret_nameclass) = _PrepareReturnFields($return_fields, $available_fields); 
   $select_str = implode(",", $select_fields);
   
   list($filter_condition, $count_names, $count_class) = _PrepareFilter($filter_fields, $available_fields);  

   $order_condition = _PrepareOrderBy($order_fields, $available_fields); 
	 	
   $limit_condition = _PrepareLimit($start, $limit);
    
	//Build the query
	$select = "SELECT Resources.ResourceId,". $select_str;

   $count = 0;
   $from = " FROM Resources ";
	if($count_class !=0) {//hay classifications en el filtro
	  $from .= " INNER JOIN ResourceClassInts on Resources.ResourceId = ResourceClassInts.ResourceId "
                ."INNER JOIN Classifications on Classifications.ClassificationId = ResourceClassInts.ClassificationId";
     $count = $count_class;//para GroupBy having count, que se cumplan todos los OR de clasifications  
	}
	if ($count_names != 0) {//hay ControlledNames en el filtro
	  $from .= " INNER JOIN ResourceNameInts on Resources.ResourceId = ResourceNameInts.ResourceId "
               ."INNER JOIN ControlledNames on ControlledNames.ControlledNameId = ResourceNameInts.ControlledNameId";
     //para GroupBy having count, que se cumplan todos los OR de Names y los de class 
     $count = $count ? ($count * $count_names) : $count_names;
   }

	$where = " WHERE Resources.ReleaseFlag=1 ";//only published

	if($text_condition) {
	  $where .= " AND ". $text_condition;
   }

	if($filter_condition) {
	  $where .= " AND (". $filter_condition .")";
   }

   $group_by = "";
   if ($count) { //Resources que cumplan con todos los filtros de vocab (estan con OR)   
     $group_by = " GROUP BY ResourceNameInts.ResourceId HAVING count(*)=$count ";
   }
   
   $order_by = "";
   if ($order_condition) {   
     $order_by = "ORDER BY ". $order_condition;
   }

   $str_sql_count = "SELECT Resources.ResourceId ". $from . $where . $group_by;

   $str_sql = $select . $from . $where . $group_by . $order_by . $limit_condition;
     
	$arrtmp=array();

   //total of search results	
	$res_count = @mysql_query($str_sql_count);
   $total = @mysql_num_rows($res_count);
   @mysql_free_result($res_count);
   if (!$total) {
	  $err='No hay resultados o no estan publicados.';
	}
   else {
     $result=@mysql_query($str_sql);
     if (!@mysql_num_rows($result)) {
	    $err="Hay resultados pero no se pudieron recuperar, revise START y LIMIT";
	    //$err=$str_sql;
	  }
     else {     	
	    while($row=@mysql_fetch_array($result)) {
	    	$resource_values = array();  
         $resource_values["ResourceId"] = new xmlrpcval($row["ResourceId"], "int");
         $cwis_path = "http://".$_SERVER["HTTP_HOST"]."/SPT--FullRecord.php?ResourceId=".$row["ResourceId"]; 
         $resource_values["CwisPath"] = new xmlrpcval($cwis_path, "string");

         //recorre los campos de Resource que se deben devolver          
         foreach($select_fields as $fname) {
           switch($fname) {
             case 'AddedById':        
             case 'LastModifiedById':        
               $str_sql = "SELECT UserName FROM APUsers WHERE UserId = ". $row[$fname];
               $res = @mysql_query($str_sql);
               if (@mysql_num_rows($res) < 1) {
                 //no user by that name
                 $user_name = 'Unknown';
               }
               else {
                 $record = @mysql_fetch_row($res);
                 $user_name = $record[0];
               }
               $resource_values[$fname] = new xmlrpcval($user_name, "string");
               break;
             default:      
               $resource_values[$fname] = new xmlrpcval($row[$fname], "string");
               break;
           }
         }
         //campos Name y Classification que se deben devolver          
         $nameclass_values = _GetResourceNameClassValues($row["ResourceId"], $ret_nameclass);
         $resource = array_merge($resource_values, $nameclass_values); 
         //agrega resource al array de resultados
	  	   $arrtmp[]=new xmlrpcval($resource, "struct");
       }
       $retStruct=new xmlrpcval(array("total" => new xmlrpcval($total, "int"),
                                      "result"=> new xmlrpcval($arrtmp, "array")),
                                "struct");

	    return new xmlrpcresp($retStruct);
	  }
     @mysql_free_result($result);
   }
   if ($err) {
  	 return new xmlrpcresp(0, $xmlrpcerruser, $err);
   }
}

/**
 * _GetResourceNameClassValues: Auxiliary Function, return an array of a Resource field values 
 *                     for fields with type ControlledName, Option or Tree
 * @param  id (integer): id of Resource  
 *         ret_nameclass (array): array of fields with type ControlledName, Option or Tree       
 *                                to return their values
 * @return 
 *     nameclass_values (array): associative array FieldName => ValueStr of fields values 
 *                              for fields in ret_nameclass and for resource with id  
 */
function _GetResourceNameClassValues($id, $ret_nameclass) {
	$nameclass_values = array();

   //recorre los campos names y classif que se deben devolver          
   foreach($ret_nameclass as $fId => $fvalue) {
     switch ($fvalue[1]){//type
       case 'ControlledName':
       case 'Option':
         $select="SELECT ControlledNames.ControlledName "
             ."FROM ResourceNameInts,ControlledNames "
             ."WHERE (ControlledNames.ControlledNameId = ResourceNameInts.ControlledNameId) "
             ."AND (ControlledNames.FieldId =". $fId .") "
             ."AND (ResourceNameInts.ResourceId=". $id .")";
         break;
       case 'Tree':
         $select = "SELECT Classifications.ClassificationName "
             ."FROM ResourceClassInts,Classifications "
             ."WHERE (Classifications.ClassificationId = ResourceClassInts.ClassificationId) "
             ."AND (Classifications.FieldId =". $fId .") "
             ."AND (ResourceClassInts.ResourceId=". $id .")";
         break;
     }
     $res_select = @mysql_query($select);
     if (@mysql_num_rows($res_select)) {
     	 $values_arr = array();
       while ($value = @mysql_fetch_row($res_select))
		   $values_arr[] = $value[0];

		 $values_str = '['. implode("], [", $values_arr) .']';
       $fname = $fvalue[0];//name         	 
   	 $nameclass_values[$fname] = new xmlrpcval($values_str, "string");
     }
     @mysql_free_result($res_select);
   }
   return $nameclass_values;  	
}
/**
 * _PrepareTextSearch: Auxiliary Function, prepares string with condition to search text
 * @param  text (string): text to search  
 *         text_fields (array): the id of fields where to search the text, default (1: Title and 3: Description)      
 *         available_fields (array): the result of function _GetAvailableFields
 * @return 
 *     text_condition (string): the condition to search text, as part of WHERE stament
 */
function _PrepareTextSearch($text, $text_fields, $available_fields) {

   //1: Title, 3: Description  
   $deftext_fields = array(1, 3);
   $text_condition = "";

	if($text) {
     //elimina espacios innecesarios	  
	  $text=trim($text); 
	  $text=ereg_replace(" {1,}"," ",$text);
     //se buscan Resources que contengan 'todas' las palabras de text        
     $text = "+" . eregi_replace(" "," +",$text);       
   }
   else {
     return NULL;//no text to search
   }

   if(!count($text_fields)) { 
  	  $text_fields = $deftext_fields; 
   }
   
   list($all, $resource, $nameclass, ) = $available_fields;
   $resource_keys = array_keys($resource);

   //busqueda de texto en los campos disponibles de 'Resources'
   foreach($text_fields as $fId) {
     if (in_array($fId, $resource_keys) ) {
   	 $fields_name[] = $resource[$fId][0]; //FieldName
     }
   } 
   $text_condition = " MATCH (". implode(",", $fields_name) .") AGAINST ('$text' IN BOOLEAN MODE) "; 

	return $text_condition;
}	

/**
 * _PrepareReturnFields: Auxiliary Function, return two arrays with fields to return: one with Resource fields, other with name class fields
 * @param  return_fields (array): the id of fields to return, default (1: Title, 3: Description, 4: Url)      
 *         available_fields (array): the result of function _GetAvailableFields
 * @return 
 *     select_f (array): the names of Resource (table) fields to return
 *     ret_nameclass (array): associative array FieldId => (FieldName, FieldType, Optional, AllowMultiple) of fields to return 
 *                              with type (ControlledName, Option or Tree)
 */
function _PrepareReturnFields($return_fields, $available_fields) {
 
  $select_f = array();
  $ret_nameclass = array();

  list($all, $resource, $nameclass, ) = $available_fields;

   //1: Title, 3: Description, 4: Url   
  $defret_fields = array(1, 3, 4);
  if(!count($return_fields)) { 
    $return_fields = $defret_fields; 
  }

  $resource_keys = array_keys($resource);
  $nameclass_keys = array_keys($nameclass);

  foreach($return_fields as $fId) {
    if (in_array($fId, $resource_keys) ) {
      //en Select solo campos disponibles en 'Resources' 	  
      if($resource[$fId][1]  == 'Point'){
   	  $select_f[] = $resource[$fId][0] .'X'; //FieldNameX
   	  $select_f[] = $resource[$fId][0] .'Y'; //FieldNameY
      }
      else {   	
   	  $select_f[] = $resource[$fId][0]; //FieldName
   	}
    }
    elseif(in_array($fId, $nameclass_keys)) {
      //campos ControlledName, Option o Tree disponibles   	  
      $ret_nameclass[$fId] = $nameclass[$fId];
    }
  }
  
  return array($select_f, $ret_nameclass);
}

/**
 * _PrepareFilter: Auxiliary Function, prepares a string with filter condition and number of filters in Class and Name fields 
 * @param  filter_fields (array): associative array FieldId => (Value, Operator) to build the filter. 
 *                                Value the value of the field to filter. 
 *                                  If field type is Date or TimeStamp you may filter by range, 
 *                                   if you set Value as range_ini:range_end
 *                                  If field type is Point you have to set Value as valueX:valueY     
 *                                Operator (for example: =, !=, <, >, LIKE, ...) 
                                   It is optional, if not set the default is:
 *                                  '=' for fields with type: Number, Flag, User, Point
 *                                  'LIKE' for other Resource table fields
 *                                  in case of ControlledName, Option or Tree dont set operator, is always '=' 
 *                                 In field type is Point you have to set Operator as operatorX:operatorY
 *         available_fields (array): the result of function _GetAvailableFields
 * @return 
 *     filter_condition (string): the filter condition to include in WHERE stament
 *     count_names (int): number of filters on fields with type ControlledName, Option
 *     count_class (int): number of filters on fields with type Tree
 */
function _PrepareFilter($filter_fields, $available_fields) {
  
  if(!count($filter_fields)) //no hay condiciones para filtrar
    return array("", 0, 0);

  $filter_condition = "";
  $filter_resource = array();
  $filter_name = array();
  $filter_class = array();
  $count_names = 0;
  $count_class = 0;
  
  list($all, $resource, $nameclass, ) = $available_fields;
  $resource_keys = array_keys($resource);
  $nameclass_keys = array_keys($nameclass);

  $valid_op['number'] = array('=', '<', '<=', '>', '>=','!=');
  $valid_op['date'] = array('=', '<', '<=', '>', '>=','!=', 'LIKE', 'NOT LIKE');
  $valid_op['string'] = array('LIKE', 'NOT LIKE', '=','!=');

  $available_filter = _GetIncludeIn(5);
  foreach($filter_fields as $fId => $value) {
  	if(in_array($fId,$available_filter)){//filter fields with Filter=1  
    if(is_array($value)){
      $val = $value[0];     	
      $op = $value[1];
    }
    else{
    	$val = $value;
      $op = '';
    }
    if (in_array($fId, $resource_keys) ) {
    	$fname = $resource[$fId][0];
    	$ftype = $resource[$fId][1];
    	
    	if($op AND in_array($op, array('IS NULL', 'IS NOT NULL'))){
        $filter_resource[] = " Resources.$fname $op ";
    	}
    	else {
	      switch($ftype) {
	        case 'Date'://date
	        case 'TimeStamp'://datetime
	          if(strchr(':',$val)){
	          	$range = explode(':', $val);
	          	if(strtotime($range[0]) != -1 && strtotime($range[1]) != -1){
	    	        $filter_resource[] = " (Resources.$fname >= '" . $range[0] ."' AND Resources.$fname <= '". $range[1] ."') ";
	          	}  
	          }
	          else{
	            $timestamp = strtotime($val);
	            if($timestamp != -1){
	              if(!$op OR !in_array($op, $valid_op['date'])){
	              	 $op = 'LIKE'; //default
	              }
	              if(strstr($op,'LIKE')){
	    	          $filter_resource[] = " Resources.$fname $op '%". $val ."%'";
	              }
	              else {
	    	          $filter_resource[] = " Resources.$fname $op '" . $val ."' ";
	    	        }
	    	      }  
	    	    }
	          break;
	        case 'Number':
	        case 'Flag':
	        case 'User':
	          if(!$op OR !in_array($op, $valid_op['number'])){
	            $op = '='; //default
	          }
	    	    $filter_resource[] = " Resources.$fname $op ". (int)$val;
	          break;
	        case 'Point':
	          $opx = '='; 
	          $opy = '='; 
	          if($op){
	            if(strchr(':',$op)){
	          	  list($opx, $opy) = explode(':', $op);
	              if(!$opx OR !in_array($opx, $valid_op['number'])){
	                $opx = '='; //default
	              }
	              if(!$opy OR !in_array($opy, $valid_op['number'])){
	                $opy = '='; //default
	              }
	          	}
	          	else {
	          		$opx = $op;
	          	}
	          }	
	          if(strchr(':',$val)){
	          	list($valx, $valy) = explode(':', $val);
               if(!is_null($valx)){	    	      
	    	        $filter_resource[] = " Resources.$fname". "X $opx ". $valx;
               }	    	      
               if(!is_null($valy)){	    	      
	    	        $filter_resource[] = " Resources.$fname". "Y $opy ". $valy;
               }	    	      
	          }
	          else {
	    	      $filter_resource[] = " Resources.$fname". "X $opx ". $val;
	          }
	          break;
	        default:
	          if(!$op OR !in_array($op, $valid_op['string'])){
	            $op = 'LIKE'; //default
	          }
	          if(strstr($op,'LIKE')){
	    	      $filter_resource[] = " Resources.$fname $op '%". $val ."%'";
	          }
	          else {
	    	      $filter_resource[] = " Resources.$fname $op '". $val ."'";
	    	    }
	    	    break; 
	      }
	    }
    }
    elseif(in_array($fId, $nameclass_keys)) {
    	$ftype = $nameclass[$fId][1];
	   if(!$op OR !in_array($op, array('=', '!='))){
	     $op = '='; //default
	   }
      switch($ftype) {
        case 'ControlledName':
        case 'Option':
    	    $filter_name[] = "( ResourceNameInts.ControlledNameId $op ". (int)$val ." AND ControlledNames.FieldId =". $fId .") ";
          $count_names++;
          break;  
        case 'Tree':
    	    $filter_class[] = "( ResourceClassInts.ClassificationId $op ". (int)$val ." AND Classifications.FieldId =". $fId .") ";
          $count_class++;
          break;  
      }
    }
   }
  }
  if(count($filter_resource)) {
  	$filter_condition = implode(" AND ", $filter_resource);
  }  	
  if($count_names) {
  	if($filter_condition)
  	  $filter_condition .= " AND ";
  	$filter_condition .= " (". implode(" OR ", $filter_name) .") ";
  }  	
  if($count_class) {
  	if($filter_condition)
  	  $filter_condition .= " AND ";
  	$filter_condition .= " (". implode(" OR ", $filter_class) .") ";
  }  	
 
  return array($filter_condition, $count_names, $count_class);
}

function _PrepareOrderBy($order_fields, $available_fields) {

   $orderby = "";
   $order_arr = array();
   
   if(!count($order_fields)) { 
     $default_order = "Title ASC";
     return $default_order; 
   }
   
   $available_order_fields = _GetIncludeIn(6);   
   list(, $resource, , ) = $available_fields;
   
   foreach ($order_fields as $fId => $value) {
     //ordenar por order_fields disponibles para ordenar
     if(in_array($fId, $available_order_fields)) {
   	 $order_arr[] = $resource[$fId][0]. " " .$value;
     }
     elseif($fId == -1) {	
   	 $order_arr[] = "Resources.ResourceId " .$value;
     }
   }
   if(count($order_arr)) {
     $orderby = implode(",", $order_arr);
   }
     
	return $orderby;
}	

function _PrepareLimit($start, $limit) {
  $limit_condition = ""; 
  if ($limit > 0){
	 if($start < 0) {
	   $start=0;
	 }
    $limit_condition .= " LIMIT $start, $limit";
   }
	return $limit_condition;
}	
function _checkVocabValue($FieldId, $VocabId, $field) {
	
	$table = substr($field, 0, strlen($field)-2) .'s';//quita Id y concatena s 
   $str_sql = "SELECT $field, FieldId FROM ". $table ." WHERE $field=$VocabId AND FieldId=$FieldId";
	$res = @mysql_query($str_sql);
   $exist = @mysql_num_rows($res);
	return $exist;
}	
/**
 * _GetUserPrivileges: Return the user Privileges
 * @param  UserId (int)
 * @return array of user privileges  
 */
function _GetUserPrivileges($UserId){
  $str_sql = "SELECT Privilege FROM APUserPrivileges WHERE UserId = $UserId";  
  $res = @mysql_query($str_sql);
  if (@mysql_num_rows($res) < 1) {
    $privileges = NULL;
  }
  else {
	 while($row=@mysql_fetch_row($res)) {
      $privileges[] = $row[0];
    }
  }
  return $privileges;
}

/**
 * UserExist: Checks if user exists in DB
 * @param  UserName (string)
 *         UserPassword (string): encrypted password (with crypt) 
 * @return UserId of user if exist or NULL if not  
 */
/*function _UserExist($UserName, $UserPassword) { 
  // if user not found in DB
  $str_sql = "SELECT UserId FROM APUsers" 
                ." WHERE UserName = '". addslashes(trim($UserName))."'"
                ." AND UserPassword = '". addslashes(trim($UserPassword))."'";
  $res = @mysql_query($str_sql);
  
  if (@mysql_num_rows($res) < 1) {
    //no user by that name
    return NULL;
  }
  else {
    $record = @mysql_fetch_row($res);
    $user_id = $record[0];
    $user_info['id'] = $user_id;

    $str_sql = "SELECT Privilege FROM APUserPrivileges WHERE UserId = $user_id";  
    $res = @mysql_query($str_sql);
    if (@mysql_num_rows($res) < 1) {
      $user_info['privilege'] = array();
    }
    else {
	   while($row=@mysql_fetch_row($res)) {
        $privileges = $row[0];
      }
      $user_info['privilege'] = $privileges;
    }
    return $user_info;
   }
}	*/

$recursoDispMap= 
	array("xmlrpcserver.GetResourceById" =>
		 		array("function" => "GetResourceById",
				 "signature" => $GetResourceById_sig,
				 "docstring" => $GetResourceById_doc),
         "xmlrpcserver.ListResources" =>
		 		array("function" => "ListResources",
				 "signature" => $ListResources_sig,
				 "docstring" => $ListResources_doc),
         "xmlrpcserver.ListResourcesByVocabElem" =>
	 			array("function" => "ListResourcesByVocabElem",
				 "signature" => $ListResourcesByVocabElem_sig,
				 "docstring" => $ListResourcesByVocabElem_doc),				 
		   "xmlrpcserver.SimpleSearch" =>
	 			array("function" => "SimpleSearch",
				 "signature" => $SimpleSearch_sig,
				 "docstring" => $SimpleSearch_doc),
			"xmlrpcserver.AdvancedSearch" =>
	 			array("function" => "AdvancedSearch",
				 "signature" => $AdvancedSearch_sig,
				 "docstring" => $AdvancedSearch_doc),		 
         "xmlrpcserver.InsertResource" =>
		 		array("function" => "InsertResource",
				 "signature" => $InsertResource_sig,
				 "docstring" => $InsertResource_doc),
			"xmlrpcserver.ListAvailableFields" =>
		 		array("function" => "ListAvailableFields",
				 "signature" => $ListAvailableFields_sig,
				 "docstring" => $ListAvailableFields_doc),
			"xmlrpcserver.ListVocabularies" =>
		 		array("function" => "ListVocabularies",
				 "signature" => $ListVocabularies_sig,
				 "docstring" => $ListVocabularies_doc),
         "xmlrpcserver.ListVocabularyElements" =>
		 		array("function" => "ListVocabularyElements",
				 "signature" => $ListVocabularyElements_sig,
				 "docstring" => $ListVocabularyElements_doc),
         "xmlrpcserver.UserInfo" =>
		 		array("function" => "UserInfo",
				 "signature" => $UserInfo_sig,
				 "docstring" => $UserInfo_doc),
	 );

$s=new xmlrpc_server($recursoDispMap);

?>
			