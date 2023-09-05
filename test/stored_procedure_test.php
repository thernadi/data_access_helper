<?php
namespace Rasher\Test;
use PDO;
use Rasher\Data\DataManagement\{BindingParam};
use Rasher\Data\PDO\DataManagement\{ConnectionData, DataAccessLayerHelper}; //PDO extension
use Rasher\Common\{Common};

include_once __DIR__."/../src/data_type_helper.php";
include_once __DIR__."/../src/data_access_layer_helper_base.php";
include_once __DIR__."/../src/data_access_layer_helper_pdo.php";

$connectionData = new ConnectionData("sqlsrv:server=(local);Database=test","",""); //PDO MSSQL
$dal = new DataAccessLayerHelper($connectionData);

$query = "exec getUser ?, ?";
$params = array();
$params[] = new BindingParam("Id", PDO::PARAM_INT, null);
$params[] = new BindingParam("LoginName", PDO::PARAM_STR, "user_3");
$result = $dal->execute($query, $params);

var_dump($result);
echo LINE_SEPARATOR;
echo LINE_SEPARATOR;

//simple query
$query = "exec getUser";
$result = $dal->query($query);
var_dump($result);

?>