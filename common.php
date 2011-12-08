<?php
/**
 * _GetAvailableFields: Auxiliary Function, return arrays of fields available to export
 * @return 
 *     available_fields (array): the id of all available fields
 *     resource_fields (array): associative array FieldId => (FieldName, FieldType, Optional) of available fields
 *                              that belong to Resources table
 *     nameclass_fields (array): associative array FieldId => (FieldName, FieldType, Optional, AllowMultiple) of available fields 
 *                              with type (ControlledName, Option or Tree) 
 *     required_fields (array): the id of available required fields
 *     multiple_fields (array): the id of available fields that allow multiple selection
 */
function _GetAvailableFields() {
	$available_fields = array();
	$resource_fields = array(); 
	$nameclass_fields = array();

   //get the Resources field names to export with xmlrpc
   $select="SELECT DISTINCT MetadataFields.FieldId, FieldName, FieldType, AllowMultiple "
           ."FROM MetadataFields, XmlrpcServerFields "
           ."WHERE (MetadataFields.FieldId = XmlrpcServerFields.FieldId) "
           ."AND MetadataFields.Enabled = 1 AND MetadataFields.Viewable = 1";
   $result = @mysql_query($select);
	if (@mysql_num_rows($result)) {
	  while ($field = @mysql_fetch_row($result)){
       $fieldId = $field[0];
       $fieldName = $field[1]; 
       $fieldType = $field[2]; 
       $available_fields[] = $fieldId;
       if($field[3]){
       	$multiple_fields[] = $fieldId;
       }
       switch ($fieldType){
         case "Date":
           //NormalizeFieldNameForDB
           $norm_fname = preg_replace("/[^a-z0-9]/i", "", $fieldName) ."Begin";
           $resource_fields[$fieldId] = array($norm_fname, $fieldType);
           break;  
         case 'ControlledName':
         case 'Option':
         case 'Tree':
       	  $nameclass_fields[$fieldId] = array($fieldName, $fieldType);
           break;
         default:    
           //NormalizeFieldNameForDB
           $norm_fname = preg_replace("/[^a-z0-9]/i", "", $fieldName);
           $resource_fields[$fieldId] = array($norm_fname, $fieldType);
       }
	  }
	  $ret_array = array( $available_fields, $resource_fields, $nameclass_fields, $multiple_fields);
   }
   else {
     $ret_array = NULL;
   }
   @mysql_free_result($result);
   return $ret_array;  	
}


/**
 * _GetDefaultIncludeIn: Auxiliary Function, return array of default metadata fields, used in specific option (except system fields)
 * @return 
 *     fields (array): associative array FieldId => FieldName of fields 
 */
function _GetDefaultIncludeIn($option) {
	
  $fields = NULL;  
  switch($option) {
    case 0: //Required 
    case 3: //Insert
      $select="SELECT FieldId, FieldName FROM MetadataFields "
             ."WHERE Optional != 1 AND Enabled = 1 AND Viewable = 1";
      $result = @mysql_query($select);
	   if (@mysql_num_rows($result)) {
        $system_fields = array(5, //Release Flag
									 12, //Date Of Record Creation
									 13, //Date Record Checked
									 14, //Date Last Modified
									 17, //Last Modified By Id	
									 62  //Date Of Record Release   
                           );
        while ($field = @mysql_fetch_row($result)){
          $fieldId = $field[0];
          $fieldName = $field[1]; 
          if(!in_array($fieldId, $system_fields)){
       	   $fields[$fieldId] = $fieldName;
          }
        }
      }
      break;
    case 1: //Return 
      $select="SELECT FieldId, FieldName FROM MetadataFields "
             ."WHERE Enabled = 1 AND Viewable = 1 " 
             ."AND (FieldName = 'Title' OR FieldName = 'Description' OR FieldName = 'Url')";
      $result = @mysql_query($select);
	   if (@mysql_num_rows($result)) {
        while ($field = @mysql_fetch_row($result)){
          $fieldId = $field[0];
          $fieldName = $field[1]; 
     	    $fields[$fieldId] = $fieldName;
        }
      }
      break;
    case 2: //Detail 
      $select="SELECT FieldId, FieldName FROM MetadataFields "
             ."WHERE Enabled = 1 AND Viewable = 1";
      $result = @mysql_query($select);
	   if (@mysql_num_rows($result)) {
        while ($field = @mysql_fetch_row($result)){
          $fieldId = $field[0];
          $fieldName = $field[1]; 
          if($fieldName != 'Release Flag'){
       	   $fields[$fieldId] = $fieldName;
          }
        }
      }
      break;
    case 4: //TextSearch
      $select="SELECT FieldId, FieldName FROM MetadataFields "
             ."WHERE Enabled = 1 AND Viewable = 1 " 
             ."AND IncludeInKeywordSearch = 1 "
             ."AND (FieldType = 'Text' OR FieldType = 'Paragraph' OR FieldType = 'Url')";
      $result = @mysql_query($select);
	   if (@mysql_num_rows($result)) {
        while ($field = @mysql_fetch_row($result)){
          $fieldId = $field[0];
          $fieldName = $field[1]; 
     	    $fields[$fieldId] = $fieldName;
        }
      }
      break;
    case 5: //Filter
      $select="SELECT FieldId, FieldName FROM MetadataFields "
             ."WHERE Enabled = 1 AND Viewable = 1 " 
             ."AND IncludeInAdvancedSearch = 1 AND IncludeInKeywordSearch != 1";
      $result = @mysql_query($select);
	   if (@mysql_num_rows($result)) {
        while ($field = @mysql_fetch_row($result)){
          $fieldId = $field[0];
          $fieldName = $field[1]; 
     	    $fields[$fieldId] = $fieldName;
        }
      }
      break;
    case 6: //Order
      $fields[1] = 'Title';
      break;
    }
      
   if(isset($result) AND $result)  
     @mysql_free_result($result);

   return $fields;  	
}
/**
 * _GetIncludeIn: Auxiliary Function, return array of fields, used in specific option
 * @param
 *     option (int): the id of option ( 1: Return, 2: Detail, 3: Insert, 4: TextSearch, 5: Filter, 6: Order) 
 * @return 
 *     fields (array): array of FieldId selected to IncludeIn specific option 
 */
function _GetIncludeIn($option) {
	
  $fields = array();  
  $select="SELECT FieldId FROM XmlrpcServerFields WHERE IncludeIn = $option";
  $result = @mysql_query($select);
  if (@mysql_num_rows($result)) {
    while ($field = @mysql_fetch_row($result)){
      $fields[] = $field[0];
    }
  }
  @mysql_free_result($result);

  return $fields;  	
}
/**
 * _GetIncludeInFields: Auxiliary Function, return array of arrays, associates filedId with array of options where include it
 * @return 
 *     fields (array of arrays): FieldId => array of options where include the Field 
 */
function _GetIncludeInFields() {
	
  $select="SELECT FieldId, IncludeIn FROM XmlrpcServerFields ORDER BY  FieldId";
  $result = @mysql_query($select);
  if (@mysql_num_rows($result)) {
    while ($field = @mysql_fetch_row($result)){
    	if($fieldId == $field[0]){
    		$i += 1;
      }
    	else {
    		$i = 0;
      }
   	$fieldId = $field[0];
     	$include_field[$fieldId][$i] = $field[1];
    }
  }
  @mysql_free_result($result);

  return $include_field;  	
}
?>
