<?php
namespace Rasher\Data\UserManagement;
use Rasher\Data\PDO\DataManagement\{DbRepository}; //PDO extension
//use Rasher\Data\MySQLi\DataManagement\{DbRepository}; //MySQLi extension
use Rasher\Data\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../src/db_repository_base_pdo.php"; //PDO extension
//include_once __DIR__."/../src/db_repository_base_mysqli.php"; //MySQLi extension

//------------------------------------
//UserSetting repository implementations
//------------------------------------

class DbUserSettingRepository extends DbRepository
{
	public function __construct($connectionData, $useItemCache = false, $cacheIdProperty = "Id")
	{
		$itemAttributes = array(
		ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
		ItemAttribute::with_Name_Caption_DataType("Name", "Name", DataType::DT_STRING), //req
		ItemAttribute::with_Name_Caption_DataType("DefaultValue", "Default value", DataType::DT_STRING),		
		ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0));		
		parent::__construct($connectionData, "UserSetting", $itemAttributes, $useItemCache, $cacheIdProperty);
	}
}

?>