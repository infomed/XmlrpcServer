XmlrpcServer Plugin 1.0.0
Autor: Yazna Garcia Vega
Email: yazna96@gmail.com, yazna@infomed.sld.cu

Noviembre 2011

DESCRIPCION:
- El plugin permite utilizar a CWIS como un servidor web, dando la posibilidad en las aplicaciones 
Cliente de:
  - listar recursos,  
  - realizar búsquedas simple y avanzada de estos,
  - insertar un recurso si el usuario tiene permiso para ello en CWIS,  
  - listar los clasificadores utilizados,
  - listar los metadatos de los campos asociados a un Recurso,
- El plugin tiene una opción de configuración, ubicada en la página de administración de CWIS, 
en el grupo Collection Administration, con el nombre Xmlrpc Server Configuration.  
- En la opción de configuración los administradores pueden seleccionar los campos de un Recurso 
que pondrán a disposición de las aplicaciones clientes, es decir, aquellos que desean sean públicos 
para otras aplicaciones. Se seleccionan los campos para las diferentes opciones del server.
 - En la columna RETURN: marcar los campos que se desean exportar en los métodos que devuelven una
                         lista de recursos.
 - En la columna DETAIL: marcar campos para dar información del detalle de un recurso.
 - En la columna INSERT: marcar campos que el cliente debe brindar para insertar un recurso en CWIS.
 - En la columna TEXTSEARCH: marcar campos de un recurso donde se pueden hacer búsquedas de un texto.
 - En la columna FILTER: marcar campos de un recurso para definir la búsqueda o filtro avanzado.
 - En la columna ORDER: marcar campos por los que se puede ordenar una lista de recursos.

- Los campos seleccionados se guardan en la tabla 'XmlrpcServerFields', creada por el plugin al ser 
instalado. 

Ficheros incluidos en el plugin:
- XmlrpcServer.php : codigo del plugin, donde se definen los hooks Register, Install, HookEvents, 
							y funcion para agregar la opcion de configuración del plugin en la administracion. 
- XmlrpcSConfig.php, XmlrpcSConfig.html: pagina de configuracion del plugin, que presenta un formulario
							con la lista de campos de un recurso, para seleccionar los que estarán disponibles
							para las aplicaciones.
- server.php : código del web service, con los métodos que pueden ser utilizados por los clientes.
- common.php : métodos auxiliares que son comunes al web service y a la opcion de configuración  
- conexion.php : se establece la conexión con la Base de datos de CWIS, utilizada por server.php
- Carpetas 
    lib : contiene las librerias xmlrpc utilizadas por server.php
    cliente: contiene codigo de un cliente para demostrar el uso de los diferentes métodos del 
    			 web service   
    			 
Descripción del web service (server.php):

CWIS es una aplicación que tiene un tipo de contenido que es el Recurso, este viene con un grupo de 
campos predefinidos,pero puede extenderse ya que la aplicación permite agregar nuevos campos al 
Recurso. El server incluido en el Plugin XmlrpcServer, es genérico y puede utilizarse en cualquier
instancia de CWIS, independientemente de como haya sido personalizado CWIS.

- Métodos del web service:
	- ListAvailableFields : Lista los campos de un recurso que están disponibles en la instancia 
									de CWIS, con sus propiedades.
		- Parámetros: no tiene parámetros 
		- Resultado: (array de arrays) De cada campo se obtiene (FieldId, 
						 FieldName, FieldType, Description. Campos para construir en el cliente 
						 los formularios de busqueda avanzada y edicion de un recurso. Campos Return, Detail,
						 Insert, TextSearch, Filter, Order que definen para que opcion esta habilitado). 
      - En el cliente llamar al método: xmlrpcserver.ListAvailableFields						 
	   - Observaciones: Este debe ser el primer método que se llame en un cliente, pues el resto de los 
	   					  métodos utilizan los FieldId de los campos	para definir que retornar, donde
	   					  buscar, donde insertar.

   - ListResources : Devuelve una Lista de Recursos. En esta se puede configurar el orden y la cantidad
                     de recursos que son los parámetros del método. La información de cada recurso son 
                     los campos con Return=1. 
		- Parámetros:  
		            - order_fields (array): arreglo con el id de los campos por los que se desea ordenar 
		              y ASC o DESC para cada uno.
		            - start (int) : numero inicial del rango de registros a devolver
		            - count (int) : cantidad máxima de registros a devolver
		- Resultado: (array) : arreglo con los índices 
		              total: total de recursos existentes
		              result (array of arrays) : cada elemento contiene la información de un recurso, esta 
		              contiene los campos con Return=1  
		- En el cliente llamar al método: xmlrpcserver.ListResources						 
	   - Observaciones: start y count dan la posibilidad de paginar los resultados, si se desea obtener todos
	                    los recursos de una vez pasar start = 0 y count = 0. En los campos para ordenar debe 
	                    pasar aquellos con Order=1, en caso de pasar otro no se tendra en cuenta, el orden por 
	                    defecto es (1=>'ASC'), es decir, alfabeticamente por Titulo.  
									      
	- GetResourceById : Brinda el detalle de un recurso a partir de su identificador.
		- Parámetros: 
						 - resource_id (int): el código del recurso que se desea obtener
		- Resultado: (array) Arreglo asociativo donde a cada nombre de campo se asocia su valor. Los campos 
		             devueltos son aquellos con Detail=1. 
      - En el cliente llamar al método: xmlrpcserver.GetResourceById						 
	   - Observaciones: Antes de llamar este metodo, para conocer el id del recurso se debe haber llamado 
	                    a algun metodo que lista recursos como ListResources, ListResourcesByVocabElement, 
	                    SimpleSearch,	AdvancedSearch. 

   - ListResourcesByVocabElement: Lista los recursos asociados a un valor de un clasificador (vocabulario) dado. 
                                  Por ejemplo: aquellos cuyo Pais es 'Cuba' 
		- Parámetros:  
                  - cod_vocab (int): codigo del Vocabulario (su FieldId)  
                  - cod_elem (integer): codigo del elemento del Vocabulario (su ElemId)
		            - order_fields (array): arreglo con el id de los campos por los que se desea ordenar 
		              y ASC o DESC para cada uno.
		            - start (int) : numero inicial del rango de registros a devolver
		            - count (int) : cantidad máxima de registros a devolver
		- Resultado: (array) : arreglo con los índices 
		              total: total de recursos existentes asociados al elemento del vocabulario
		              result (array of arrays) : cada elemento contiene la información de un recurso, esta 
		              contiene los campos con Return=1  
		- En el cliente llamar al método: xmlrpcserver.ListResourcesByVocabElement						 
	   - Observaciones: cod_vocab se puede obtener de los métodos ListVocabularies donde viene en el indice FieldId
	                    y de ListVocabularyElements donde viene en el indice VocId, en este mismo método se obtiene 
	                    cod_elem en el indice ElemId. 
	                    start y count dan la posibilidad de paginar los resultados, si se desea obtener todos
	                    los recursos de una vez, pasar start = 0 y count = 0. En los campos para ordenar debe 
	                    pasar aquellos con Order=1, en caso de pasar otro no se tendra en cuenta, el orden por 
	                    defecto es (1=>'ASC'), es decir, alfabeticamente por Titulo.  	 
									      
   - SimpleSearch: Permite la búsqueda por texto en los campos definidos para ello en el plugin (con TextSearch=1).
                   Lista los recursos donde se haya encontrado el texto. 
		- Parámetros:  
                  - text (string): texto a buscar  
		            - order_fields (array): arreglo con el id de los campos por los que se desea ordenar 
		              y ASC o DESC para cada uno.
		            - start (int) : numero inicial del rango de registros a devolver
		            - count (int) : cantidad máxima de registros a devolver
		- Resultado: (array) : arreglo con los índices 
		              total: total de recursos que contienen el texto dado
		              result (array of arrays) : cada elemento contiene la información de un recurso, esta 
		              contiene los campos con Return=1  
		- En el cliente llamar al método: xmlrpcserver.SimpleSearch						 
	   - Observaciones: start y count dan la posibilidad de paginar los resultados, si se desea obtener todos
	                    los recursos de una vez, pasar start = 0 y count = 0. En los campos para ordenar debe 
	                    pasar aquellos con Order=1, en caso de pasar otro no se tendra en cuenta, el orden por 
	                    defecto es (1=>'ASC'), es decir, alfabeticamente por Titulo.  	 
 
   - AdvancedSearch: Permite la búsqueda por texto y cualquier otro criterio a partir de los campos
                     definidos para ello en el plugin (con Filter=1). El resultado de la búsqueda
                     es una Lista de recursos. 
		- Parámetros:  
                  - text (string): texto a buscar  
		            - filter_fields (array): arreglo con el id de los campos por los que se desea 
		              filtrar, el valor y el operador a aplicar. FieldId => (Value,Operator). Estos 
		              campos deben tener Filter=1.  
		            - order_fields (array): arreglo con el id de los campos por los que se desea ordenar 
		              y ASC o DESC para cada uno. FieldId => 'ASC' o 'DESC'
		            - start (int) : numero inicial del rango de registros a devolver
		            - count (int) : cantidad máxima de registros a devolver
		- Resultado: (array) : arreglo con los índices (total, result), donde 
		              total: total de recursos que contienen el texto dado
		              result (array of arrays) : cada elemento contiene la información de un recurso, esta 
		              contiene los campos con Return=1  
		- En el cliente llamar al método: xmlrpcserver.AdvancedSearch						 
	   - Observaciones: filter_fields permite construir la condición para filtrar los recursos. Es 
	                    un arreglo que en el índice tiene los FieldId de los campos por los que se 
	                    filtrar y a cada índice se asocia un arreglo con Valor del campo y operador.
	                    El operador depende del tipo de campo de la sgte forma:
	                      * Valores por defecto segun el tipo:
	                        - Number,Flag,User,Point,ControlledName,Option,Tree: '=' 
	                        - 'LIKE' para el resto
	                      * Valores posibles segun el tipo:
	                        - Number, Flag, User, Point: ('=', '<', '<=', '>', '>=','!=')       
                           - Date, TimeStamp: ('=', '<', '<=', '>', '>=','!=', 'LIKE', 'NOT LIKE')
                           - ControlledName, Option, Tree: solo '=', no hay que pasarlo 
                           - Text, Paragraph, Url: ('LIKE', 'NOT LIKE', '=','!=') 	                         
                       En caso de Date, TimeStamp se puede definir Value como un rango de la
                       siguiente forma ini:end                             
                       En caso del tipo Point (que es un par x,y) :
                         - Value es un par valueX:valueY
                         - Operador es un par operadorX:operadorY
                              
	                    start y count dan la posibilidad de paginar los resultados, si se desea obtener todos
	                    los recursos de una vez, pasar start = 0 y count = 0. En los campos para ordenar debe 
	                    pasar aquellos con Order=1, en caso de pasar otro no se tendra en cuenta, el orden por 
	                    defecto es (1=>'ASC'), es decir, alfabeticamente por Titulo.  	 
 
   - InsertResource: Permite insertar un recurso en CWIS desde un cliente. Los campos a llenar al
                     al insertar un recurso son aquellos que tienen Insert=1. Retorna un numero 
                     si se insertó el recurso o un error en caso contrario.   
		- Parámetros:  
		            - fields_values (array): arreglo con los datos para insertar el recurso, 
		              donde al id del campo se asocia su valor. FieldId => Value. Estos 
		              campos deben tener Insert=1.  
		            - user_id (int): identificador del usuario que va a insertar el recurso
		            - user_passw (string): password encriptado del usuario
		- Resultado: 1: El recurso se insertó con todos los campos enviados
		             2: El recurso se insertó, pero algun valor de vocabularios no pq no existía
		             3: El recurso se insertó, pero de algun vocab se inserto solo un elemento 
		                porque no es multiple.
		             Error: error en el nombre de usuario o contraseña, o
		                    si no hay campos definidos con Insert=1 o 
		                    si hay campos obligatorios vacios o
		                    si el usuario en cwis no tiene permiso para insertar recurso o
		                    si ocurrio error al insertar el recurso
		- En el cliente llamar al método: xmlrpcserver.InsertResource						 
	   - Observaciones: para conocer el user_id y el password encriptado es necesario llamar 
	                    al metodo UserInfo antes de InsertResource. El parámetro user_passw se  
	                    obtiene encriptando la contraseña introducida en el cliente y como semilla
	                    el resultado de UserInfo en el indice 'Salt'    

   - ListVocabularies: Lista los campos que contienen clasificadores (vocabulario), es decir,
                       los campos con tipo ControlledName, Option o Tree. 
		- Parámetros:  
                  - filter (string): valores posibles 'Name': devuelve los ControlledName y Option
                                     'Tree', devuelve los Tree. Por defecto los devuelve todos.     
		- Resultado: (array) : arreglo con los índices 
		              total: total de vocabularios disponibles
		              result (array of arrays) : cada elemento contiene la información del campo,
		              (FieldId, FieldName, FieldType)  
		- En el cliente llamar al método: xmlrpcserver.ListVocabularies						 
	   - Observaciones:  	 
	   
   - ListVocabularyElements: Lista elementos de un clasificador (vocabulario). 
		- Parámetros:  
                  - cod_vocab (int): codigo del Vocabulario (su FieldId devuelto por 
                    ListVocabularies)  
                  - parent (int): solo para campos Tree, para obtener elementos del mismo padre.
                    Si se pasa 0 devuelve todos los elementos, con -1 la rama inicial del árbol.  
		            - start (int) : numero inicial del rango de registros a devolver
		            - count (int) : cantidad máxima de registros a devolver
		- Resultado: (array) : arreglo con los índices 
		              total: total de elementos del vocabulario
		              result (array of arrays) : contiene la información de los elementos de 
		                                        vocabulario (VocId, ElemId, Name), si el vocabulario
		                                        es Tree ademas se devuelven ParentId y ResourceCount
		- En el cliente llamar al método: xmlrpcserver.ListVocabularyElements						 
	   - Observaciones: Llamar antes de invocar a ListResourcesByVocabElement, para conocer 
	                    VocId y ElemId que son parámetros de este último método. 	 

   - UserInfo: Chequea si un usuario existe en la BD por su username y retorna si id, 
               semilla y permisos. 
		- Parámetros:  
                  - username (string): nombre de usuario en CWIS  
		- Resultado: (array) : arreglo con los índices (UserId, Salt, Privilege)
		- En el cliente llamar al método: xmlrpcserver.UserInfo						 
	   - Observaciones: Llamar antes de invocar a InserResource, para conocer 
	                    UserId y construir passw con Salt. 	 
