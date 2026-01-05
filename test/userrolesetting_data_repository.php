<?php
namespace Rasher\Data\UserManagement;
use Rasher\Data\DataManagement\{SimpleTable,HistoricalTable};
use Rasher\Data\PDO\DataManagement\{DbRepository}; //PDO extension
//use Rasher\Data\MySQLi\DataManagement\{DbRepository}; //MySQLi extension
use Rasher\Data\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../src/db_repository_base_pdo.php"; //PDO extension
//include_once __DIR__."/../src/db_repository_base_mysqli.php"; //MySQLi extension

//------------------------------------
//UserRoleSetting repository implementations
//------------------------------------

class DbUserRoleSettingRepository extends DbRepository
{
	use SimpleTable;

	public function __construct($connectionData, $useItemCache = false, $cacheIdProperty = "Id")
	{
		$itemAttributes = $this->getTableBaseItemAttributes(array(
		ItemAttribute::with_Name_Caption_DataType("Name", "Name", DataType::DT_STRING), //req
		ItemAttribute::with_Name_Caption_DataType("DefaultValue", "Default value", DataType::DT_STRING)));		
		parent::__construct($connectionData, "userrolesetting", $itemAttributes, $useItemCache, $cacheIdProperty);
	}
}

?>