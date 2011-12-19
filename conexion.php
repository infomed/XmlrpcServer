<?php
require_once("../../config.php");

global $conexion;
$conexion = @mysql_connect($SPT_DBHost, $SPT_DBUserName, $SPT_DBPassword);
@mysql_select_db($SPT_DBName, $conexion) or die ("Error: Imposible Conectar");
?>
