<?php
namespace Rasher\Test;
use Rasher\Data\PDO\DataManagement\{ConnectionData, DataAccessLayerHelper}; //PDO extension
include_once __DIR__."/../src/db_repository_base_pdo.php"; //PDO extension    

	$connectionData = new ConnectionData("mysql:host=localhost;dbname=test", "userName", "password"); // use it with PDO extension (MySQL)
	$dal = new DataAccessLayerHelper($connectionData);
	$dal->query("SELECT * FROM userrolesetting");

?>