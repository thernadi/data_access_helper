<?php
namespace Rasher\Data\UserManagement;
use Rasher\Data\PDO\DataManagement\{DbRepository,DbUserRoleSettingRepository}; //PDO extension
//use Rasher\Data\MySQLi\DataManagement\{DbRepository}; //MySQLi extension
use Rasher\Data\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../src/db_repository_base_pdo.php"; //PDO extension
//include_once __DIR__."/../src/db_repository_base_mysqli.php"; //MySQLi extension

//------------------------------------
//UserRole repository implementations
//------------------------------------

class DbUserRoleRepository extends DbRepository
{
	public $dbUserRoleSettingRepository = null;

	public function __construct($connectionData, $dbUserRoleSettingRepository, $useItemCache = false, $cacheIdProperty = "Id")
	{
		$this->dbUserRoleSettingRepository = $dbUserRoleSettingRepository;

		$itemAttributes = array(
		ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
		ItemAttribute::with_Name_Caption_DataType("Code", "Code", DataType::DT_STRING), //req
		ItemAttribute::with_Name_Caption_DataType("Name", "Name", DataType::DT_STRING), //req
		ItemAttribute::with_Name_Caption_DataType("UserRoleSettingsCollection", "User role settings collection", DataType::DT_LIST),		
		ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0));		
		parent::__construct($connectionData, "UserRole", $itemAttributes, $useItemCache, $cacheIdProperty);

		$ItemAttribute = ItemAttribute::getItemAttribute($this->itemAttributes, "UserRoleSettingsCollection");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("UserRole", "UserRole_UserRoleSettingsCollection", $this->itemAttributes, $this->getUserRoleUserRoleSettingsCollectionItemAttributes(), "Id", "UserRole"));

	}

	public function getUserRoleUserRoleSettingsCollectionItemAttributes()
	{
		$returnValue = array(
		ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
		ItemAttribute::with_Name_Caption_DataType("UserRole", "UserRole", DataType::DT_INT), //req (this attribute's type cannot be DataType::DT_ITEM !)
		ItemAttribute::with_Name_Caption_DataType("UserRoleSetting", "User setting", DataType::DT_ITEM),	
		ItemAttribute::with_Name_Caption_DataType("Value", "Value", DataType::DT_STRING),
		ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0));		

		$ItemAttribute = ItemAttribute::getItemAttribute($returnValue, "UserRoleSetting");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("UserRole_UserRoleSettingsCollection", "UserRoleSetting", $returnValue, $this->dbUserRoleSettingRepository->itemAttributes, "UserRoleSetting", "Id"));

		return $returnValue;
	}

	public function deleteAll_UserRole_UserRoleSettingsCollection()
	{
		$query = "DELETE FROM UserRole_UserRoleSettingsCollection";
		$params = array();
		$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}
}

?>