<?php
namespace Rasher\Data\UserManagement;
use Rasher\Data\DataManagement\{SimpleTable,HistoricalTable};
use Rasher\Data\PDO\DataManagement\{DbRepository}; //PDO extension
//use Rasher\Data\MySQLi\DataManagement\{DbRepository}; //MySQLi extension
use Rasher\Data\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../src/db_repository_base_pdo.php"; //PDO extension
//include_once __DIR__."/../src/db_repository_base_mysqli.php"; //MySQLi extension
include_once __DIR__."/userrole_data_repository.php";
//------------------------------------
//User repository implementations
//------------------------------------

class DbUserRepository extends DbRepository
{
	use SimpleTable;

	public $depth = 2;
	public $dbUserRoleRepository = null;

	public function __construct($connectionData, $dbUserRoleRepository, $useItemCache = false, $cacheIdProperty = "Id")
	{
		$this->dbUserRoleRepository = $dbUserRoleRepository;

		$itemAttributes = $this->getTableBaseItemAttributes(array(
			ItemAttribute::with_Name_Caption_DataType("LoginName", "Login name", DataType::DT_STRING), //req
			ItemAttribute::with_Name_Caption_DataType("Password", "Password", DataType::DT_STRING), //req
			ItemAttribute::with_Name_Caption_DataType_DataFormat("LastLoginDateTime", "Last login datetime", DataType::DT_DATETIME, "Y-m-d H:i:s"),
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsLogged", "Is logged", DataType::DT_INT, 0),					
			ItemAttribute::with_Name_Caption_DataType("UserRolesCollection", "User roles collection", DataType::DT_LIST)));
		parent::__construct($connectionData, "User", $itemAttributes, $useItemCache, $cacheIdProperty);

		$ItemAttribute = ItemAttribute::getItemAttribute($this->itemAttributes, "UserRolesCollection");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User", "User_UserRolesCollection", $this->itemAttributes, $this->getUserUserRolesCollectionItemAttributes(), "Id", "User"));
	}

	public function getUserUserRolesCollectionItemAttributes()
	{
		$returnValue = $this->getTableBaseItemAttributes(array(
			ItemAttribute::with_Name_Caption_DataType("User", "User", DataType::DT_INT), //req (this attribute's type cannot be DataType::DT_ITEM !)
			ItemAttribute::with_Name_Caption_DataType("UserRole", "User role", DataType::DT_ITEM))); //req											
	
		$ItemAttribute = ItemAttribute::getItemAttribute($returnValue, "UserRole");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User_UserRolesCollection", "UserRole", $returnValue, $this->dbUserRoleRepository->itemAttributes, "UserRole", "Id"));
		return $returnValue;
	}

	public function deleteAll_User_UserRolesCollection()
	{
		$query = "DELETE FROM User_UserRolesCollection";
		$params = array();
		$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}
}

?>