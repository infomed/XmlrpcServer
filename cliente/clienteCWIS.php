<?php
include "xmlrpc.inc";

//metodos disponibles en el ws
$metodos[] = 'ListAvailableFields';
//$metodos[] = 'ListVocabularies';
//$metodos[] = 'ListVocabularyElements';
//$metodos[] = 'GetResourceById';
//$metodos[] = 'ListResources';
//$metodos[] = 'ListResourcesByVocabElem';
//$metodos[] = 'SimpleSearch';
//$metodos[] = 'AdvancedSearch';
//$metodos[] = 'InsertResource';
//$metodos[] = 'UserInfo';

$debug = 0;//when 1 (or 2) will enable debugging of the underlying xmlrpc call (defaults to 0)

//cliente
$client = new xmlrpc_client('/plugins/XmlrpcServer/server.php', 'cwis220.sld.cu', 80);
$client->path = '/plugins/XmlrpcServer/server.php';
$client->server = 'cwis220.sld.cu';
$client->port = '80';
$client->method = 'http';
$client->errno = 0;
$client->errstr = '';
$client->username = 'root';
$client->password = 'root';
$client->authtype = 1;
$client->setDebug($debug);
$client->return_type = 'xmlrpcvals';
//otros param de $client
/*
$client->errno = 0;
$client->errstr = '';
$client->cert = '';
$client->certpass = '';
$client->cacert = '';
$client->cacertdir = '';
$client->key = '';
$client->keypass = '';
$client->verifypeer = true;
$client->verifyhost = 1;
$client->no_multicall = false;
$client->proxy = '';
$client->proxyport = 0;
$client->proxy_user = '';
$client->proxy_pass = '';
$client->proxy_authtype = 1;
$client->cookies = array (
);
$client->extracurlopts = array (
);
$client->accepted_compression = '';
$client->request_compression = '';
$client->xmlrpc_curl_handle = NULL;
$client->keepalive = true;
$client->accepted_charset_encodings = array (
  0 => 'UTF-8',
  1 => 'ISO-8859-1',
  2 => 'US-ASCII',
);
$client->request_charset_encoding = '';
$client->user_agent = 'XML-RPC for PHP 3.0.0.beta';
*/
foreach($metodos as $metodo){
  echo "</BR>------------$metodo-------------</BR>";
  $res = NULL;
  switch($metodo){
    case 'ListAvailableFields'://lista los campos de Recurso y sus propiedades, disponibles para interactuar con el ws
      echo "</BR>Lista los campos de Recurso y sus propiedades. No todos sino los seleccionados con el plugin XmlrpcServer para exportar con el ws</BR>";
      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.ListAvailableFields');

      //llamado al web service, en $msg metodo, este no tiene parametros 
      $res =& $client->send($msg, 0, '');//llamado al web service
      break;

    case 'ListVocabularies'://lista los campos que contienen vocabularios (tipo ControlledName, Option o Tree) 
      echo "</BR>Lista los campos que contienen clasificadores o vocabularios (tipo ControlledName, Option o Tree)</BR>";
      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.ListVocabularies');

      //1: cadena para filtrar vocab por su tipo
      $p1 = new xmlrpcval('', 'string');//todos los vocab
      //$p1 = new xmlrpcval('Name', 'string');//filtra vocab de tipo ControlledName y Option
      //$p1 = new xmlrpcval('Tree', 'string');//filtra vocab de tipo Tree
      $msg->addparam($p1);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');//llamado al web service
      break;

    case 'ListVocabularyElements'://Lista los elementos de un vocabulario
      echo "</BR>Lista los elementos de un clasificador o vocabulario</BR>";

      //parametros a pasar

      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.ListVocabularyElements');

      //1: code of Vocabulary (FieldId), according to ListVocabularies method
      $p1 = new xmlrpcval(19, 'int');//Publisher
      //$p1 = new xmlrpcval(27, 'int');//Classification
      $msg->addparam($p1);
      
      //2: parent of the tree elements (FieldId of parent), default 0 (all vocab elements, -1 (first branch)
      //$p2 = new xmlrpcval(-1, 'int');
      $p2 = new xmlrpcval(0, 'int');
      //$p2 = new xmlrpcval(1, 'int');
      $msg->addparam($p2);

      //3: start number of records      
      $p3 = new xmlrpcval(0, 'int');
      $msg->addparam($p3);
      
      //4: max number of records to return
      //$p4 = new xmlrpcval(5, 'int');
      $p4= new xmlrpcval(0, 'int');//if start=0 and count=0 return all elements
      $msg->addparam($p4);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      break;

  	 case 'GetResourceById'://Obtiene un recurso a partir de su Id
      echo "</BR>Obtiene un recurso a partir de su Id</BR>";

      //parametros a pasar

      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.GetResourceById');

      //1: resource id
      $p1 = 33;
      $p1 = new xmlrpcval($p1, 'int');
      $msg->addparam($p1);
      
      //2: return_fields: arreglo con los Id (FieldId) de los campos que se deben devolver
		//los FieldId se obtienen de ListAvailableFields
      //1: Title, 3: Description, 2: Alternate Title, 4: Url, 19: Publisher, 27: Classification      
      /*$p2 = array(1, 3, 2, 4, 19,27);
      //$p2 = array();//vacio para devolver todos los datos disponibles del recurso   
      $p2 =& php_xmlrpc_encode($p2);
      $msg->addparam($p2);*/
      
      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      break;

  	 case 'ListResources'://Obtiene una lista de recursos.
      echo "</BR>Obtiene una lista de recursos</BR>";

      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.ListResources');

      //1: return_fields: arreglo con los Id (FieldId) de los campos que se deben devolver      
		//los FieldId se obtienen de ListAvailableFields
      //$p1 = array(1, 3, 2, 4, 19, 27); 
      /*$p1 = array(1, 3, 4, 50); 
      $p1 =& php_xmlrpc_encode($p1);
      $msg->addparam($p1);*/
      
      //2: order_fields: arreglo con los Id (FieldId) y ASC, DESC, de los campos por los que se ordenan los resultados
		//los FieldId se obtienen de ListAvailableFields
      $p2 = array(1=>'ASC');//por titulo ascendente 
      //$p2 = array(-1=>'DESC');//por ResourceId
      //$p2 = array(''=>''); //sin orden
      $p2 =& php_xmlrpc_encode($p2);
      $msg->addparam($p2);
      
      //3: start number of records      
      $p3 = new xmlrpcval(0, 'int');
      $msg->addparam($p3);

      //4: max number of records to return
      $p4 = new xmlrpcval(3, 'int');
      //$p4= new xmlrpcval(0, 'int');//if start=0 and count=0 return all elements
      $msg->addparam($p4);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      break;

  	 case 'ListResourcesByVocabElem'://Obtiene una lista de recursos, asociados a un elemento de vocabulario 
  	   //por ej los recursos que tiene como Publisher(vocabulario 19) a Infomed (elemento del vocab = 1)
      echo "</BR>Obtiene una lista de recursos, asociados a un elemento de vocabulario</BR>";

      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.ListResourcesByVocabElem');

      //1: code of Vocabulary (FieldId), se obtiene de ListVocabularies        
      $p1 = 19;//Publisher
      //$p1 = 27;//Classification 
      $p1 =new xmlrpcval($p1, 'int');
      $msg->addparam($p1);

      //2: code of Vocabulary Element (ControlledNameId or ClassificationId), 
      //se obtiene de ListVocabularyElements (VocElemId)      
      $p2 = 1;//if p1 Publisher, p2 =1: Infomed ...  ; if p1 Classification, p2=1: Ciencias de Informacion 
      $p2 =new xmlrpcval($p2, 'int');
      $msg->addparam($p2);
      
      //3: return_fields: arreglo con los Id (FieldId) de los campos que se deben devolver      
      //$p3 = array(1, 3, 2, 4, 19, 27); 
      /*$p3 = array(1, 3, 4, 19, 27); 
      $p3 =& php_xmlrpc_encode($p3);
      $msg->addparam($p3);*/

      //4: order_fields: arreglo con los Id (FieldId) y ASC, DESC, de los campos por los que se ordenan los resultados
      $p4 = array(13=>'DESC');//por Date Record Checked descendente 
      //$p4 = array(-1=>'DESC');//por ResourceId
      //$p4 = array(''=>''); //sin orden
      $p4 =& php_xmlrpc_encode($p4);
      $msg->addparam($p4);
      
      //5: start number of records      
      $p5 = new xmlrpcval(0, 'int');
      $msg->addparam($p5);

      //6: max number of records to return
      $p6 = new xmlrpcval(5, 'int');
      //$p6= new xmlrpcval(0, 'int');//if start=0 and count=0 return all elements
      $msg->addparam($p6);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      break;

  	 case 'SimpleSearch'://busca recursos, que contiene un texto en alguno de los campos especificados 
      echo "</BR>Búsqueda simple de recursos, que contienen un texto en alguno de los campos especificados</BR>";

      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.SimpleSearch');

      //1: text to search        
      $p1 = 'Australian';
      $p1 =new xmlrpcval($p1, 'string');
      $msg->addparam($p1);

      //2: text_fields: arreglo con los Id (FieldId) de los campos donde se debe buscar el texto      
      /*$p2 = array(1, 3, 2, 4); //1:Title, 3:Description, 2:AlternateTitle, 4:Url
      $p2 =& php_xmlrpc_encode($p2);
      $msg->addparam($p2);

      //3: return_fields: arreglo con los Id (FieldId) de los campos que se deben devolver      
      //$p3 = array(1, 3, 2, 4, 19, 27); 
      $p3 = array(1, 6, 19, 27); 
      $p3 =& php_xmlrpc_encode($p3);
      $msg->addparam($p3);*/

      //4: order_fields: arreglo con los Id (FieldId) y ASC, DESC, de los campos por los que se ordenan los resultados
      $p4 = array(11=>'DESC');//por Date Issued descendente 
      //$p4 = array(-1=>'DESC');//por ResourceId
      //$p4 = array(''=>''); //sin orden
      $p4 =& php_xmlrpc_encode($p4);
      $msg->addparam($p4);
      
      //5: start number of records      
      $p5 = new xmlrpcval(0, 'int');
      $msg->addparam($p5);

      //6: max number of records to return
      $p6 = new xmlrpcval(3, 'int');
      //$p6= new xmlrpcval(0, 'int');//if start=0 and count=0 return all elements
      $msg->addparam($p6);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      break;
 	 case 'AdvancedSearch': 
      echo "</BR>Búsqueda avanzada de recursos, por texto y otras condiciones de filtro sobre los campos disponibles</BR>";

      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.AdvancedSearch');

      //1: text to search        
      $p1 = 'Australian';
      $p1 =new xmlrpcval($p1, 'string');
      $msg->addparam($p1);

      //2: text_fields: arreglo con los Id (FieldId) de los campos donde se debe buscar el texto      
      /*$p2 = array(1, 3, 2, 4); //1:Title, 3:Description, 2:AlternateTitle, 4:Url
      $p2 =& php_xmlrpc_encode($p2);
      $msg->addparam($p2);

      //3: return_fields: arreglo con los Id (FieldId) de los campos que se deben devolver      
      //$p3 = array(1, 3, 2, 4, 19, 27); 
      $p3 = array(1, 3, 4, 6, 19, 27); 
      $p3 =& php_xmlrpc_encode($p3);
      $msg->addparam($p3);*/

      //4: filter_fields: arreglo con los Id (FieldId) ...
      $p4 = array(19=>array(1, '=')/*, 27=>array(167, '!='), 6=>array("Infomed", "LIKE")*/);
      $p4 =& php_xmlrpc_encode($p4);
      $msg->addparam($p4);
      
      //5: order_fields: arreglo con los Id (FieldId) y ASC, DESC, de los campos por los que se ordenan los resultados
      $p5 = array(11=>'DESC');//por Date Issued descendente 
      //$p5 = array(-1=>'DESC');//por ResourceId
      //$p5 = array(''=>''); //sin orden
      $p5 =& php_xmlrpc_encode($p5);
      $msg->addparam($p5);
      
      //6: start number of records      
      $p6 = new xmlrpcval(0, 'int');
      $msg->addparam($p6);

      //7: max number of records to return
      $p7 = new xmlrpcval(4, 'int');
      //$p7= new xmlrpcval(0, 'int');//if start=0 and count=0 return all elements
      $msg->addparam($p7);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      break;
 	 case 'InsertResource': 
      echo "</BR>Chequear usuario que va a insertar recurso</BR>";

      //chequear usuario que va a insertar recurso, si existe y tiene permisos
		//se pide informacion del usuario segun su username      
      $msg = new xmlrpcmsg('xmlrpcserver.UserInfo');
      //$u = 'cwis220';
      //$p = 'cwis220';
      $u = 'XmlrpcServerUser';
      $p = 'XmlrpcServerUser';
      //1: username in CWIS        
      $p1 =new xmlrpcval($u, 'string');
      $msg->addparam($p1);

      //llamado al web service, en $msg metodo y parametros, 
      $res =& $client->send($msg, 0, '');
      
      if($res AND !$res->faultcode()){ //sin error
        $u_info = php_xmlrpc_decode($res->value());       
        $encrypted = crypt($u, $u_info['Salt']);
        //se chequea password
        if ($encrypted != $u_info['Salt']) 
          echo '<BR>Usuario existe, Contraseña incorrecta</BR>';
        //se chequean permisos de crear recurso        
        elseif(!in_array(3, $u_info['Privilege']) AND !in_array(12, $u_info['Privilege'])) {        
          echo '<BR>El usuario no tiene permiso para insertar recurso en CWIS</BR>';
        }
        else {
      echo "</BR>$encrypted</BR>";
      echo "</BR>Insertar un recurso en CWIS desde el cliente</BR>";
          //parametros a pasar
          //0: metodo del ws      
          $msg = new xmlrpcmsg('xmlrpcserver.InsertResource');
      
          $fields_values = array(1=>"Recurso desde clienteCWIS con user:$u",//Title
                          3=>"Prueba clientCWIS.",//Description
                          4=>"http://url.sdl.cu/clienteCWIS.php",//Url
                          6=>"Infomed",//Source
   							  19=>array(1,37),//Publisher 1: infomed. Centro Convenciones..., 37_ Infomed. Biblioteca 
   							  27=>array(167,1),//Classification 167: Enfermedades respiratorias, 1: Ciencia de la Informacion
   							  23=>array(3,27));//Resource type 3: Eventos, 27: Centro Información
          $p1 =& php_xmlrpc_encode($fields_values);
          $msg->addparam($p1);

          $p2 =new xmlrpcval($u_info['UserId'], "int");
          $msg->addparam($p2);
          
          $p3 =new xmlrpcval($encrypted, "string");
          $msg->addparam($p3);

          //llamado al web service, en $msg metodo y parametros
          $res =& $client->send($msg, 0, '');
        }
      }
      break;
 	 case 'UserInfo': 
      echo "</BR>Obtener Informacion de un usuario por su username para chequear si existe y sus permisos</BR>";

      //parametros a pasar
      
      //0: metodo del ws      
      $msg = new xmlrpcmsg('xmlrpcserver.UserInfo');
      //1: username in CWIS        
      //$u = 'cwis220';//can insert resource and publish
      $u = 'XmlrpcServerUser'; //can insert resource but not published
      //$u = 'NoExistUser'; //error user does not exist
      $p1 =new xmlrpcval($u, 'string');
      $msg->addparam($p1);

      //llamado al web service, en $msg metodo y parametros
      $res =& $client->send($msg, 0, '');
      
      $u_info = php_xmlrpc_decode($res->value());       
      $encrypted = crypt($u, $u_info['Salt']);
      if ($encrypted == $u_info['Salt']) 
        echo '<BR>Usuario y Contraseña correctos</BR>';
      else 
        echo '<BR>Usuario existe, Contraseña incorrecta</BR>';
      break;
   }
   if($res){
     if ($res->faultcode()) //error
       print_r($res); 
     else //recuperando resultados
       print_r(php_xmlrpc_decode($res->value()));
   }
   echo "</BR>-----------------------------------------------------------</BR>";
}      
?> //