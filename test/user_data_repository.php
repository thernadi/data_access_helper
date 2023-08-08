<?php
namespace Rasher\Data\UserManagement;
use Rasher\Data\PDO\DataManagement\{DbRepository}; //PDO extension
//use Rasher\Data\MySQLi\DataManagement\{DbRepository}; //MySQLi extension
use Rasher\Data\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../src/db_repository_base_pdo.php"; //PDO extension
//include_once __DIR__."/../src/db_repository_base_mysqli.php"; //MySQLi extension
include_once __DIR__."/usersetting_data_repository.php";
include_once __DIR__."/userrole_data_repository.php";
//------------------------------------
//User repository implementations
//------------------------------------

class DbUserRepository extends DbRepository
{
	public $dbUserSettingRepository = null;
	public $dbUserRoleRepository = null;

	public function __construct($connectionData, $dbUserSettingRepository, $dbUserRoleRepository, $useItemCache = false, $cacheIdProperty = "Id")
	{
		$this->dbUserSettingRepository = $dbUserSettingRepository;
		$this->dbUserRoleRepository = $dbUserRoleRepository;

		$itemAttributes = array(
			ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
			ItemAttribute::with_Name_Caption_DataType("LoginName", "Login name", DataType::DT_STRING), //req
			ItemAttribute::with_Name_Caption_DataType("Password", "Password", DataType::DT_STRING), //req
			ItemAttribute::with_Name_Caption_DataType_DataFormat("LastLoginDateTime", "Last login datetime", DataType::DT_DATETIME, "Y-m-d H:i:s"),
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsLogged", "Is logged", DataType::DT_INT, 0),					
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0),		
			ItemAttribute::with_Name_Caption_DataType("DefaultUserRole", "Default user role", DataType::DT_ITEM),
			ItemAttribute::with_Name_Caption_DataType("UserRolesCollection", "User roles collection", DataType::DT_LIST));
		parent::__construct($connectionData, "User", $itemAttributes, $useItemCache, $cacheIdProperty);

		$ItemAttribute = ItemAttribute::getItemAttribute($this->itemAttributes, "UserRolesCollection");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User", "User_UserRolesCollection", $this->itemAttributes, $this->getUserUserRolesCollectionItemAttributes(), "Id", "User"));
	
		$ItemAttribute = ItemAttribute::getItemAttribute($this->itemAttributes, "DefaultUserRole");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User", "UserRole", $this->itemAttributes, $this->dbUserRoleRepository->itemAttributes, "DefaultUserRole", "Id"));
	}

	public function getUserUserRolesCollectionItemAttributes()
	{
		$returnValue = array(
			ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
			ItemAttribute::with_Name_Caption_DataType("User", "User", DataType::DT_INT), //req (this attribute's type cannot be DataType::DT_ITEM !)
			ItemAttribute::with_Name_Caption_DataType("UserRole", "User role", DataType::DT_ITEM), //req									
			ItemAttribute::with_Name_Caption_DataType("UserSettingsCollection", "User settings collection", DataType::DT_LIST),
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0));		
	
		$ItemAttribute = ItemAttribute::getItemAttribute($returnValue, "UserRole");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User_UserRolesCollection", "UserRole", $returnValue, $this->dbUserRoleRepository->itemAttributes, "UserRole", "Id"));

		$ItemAttribute = ItemAttribute::getItemAttribute($returnValue, "UserSettingsCollection");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User_UserRolesCollection", "User_UserRolesCollection_UserSettingsCollection", $returnValue, $this->getUserUserRolesCollectionUserSettingsCollectionItemAttributes(), "Id", "User_UserRolesCollection"));

		return $returnValue;
	}

	public function getUserUserRolesCollectionUserSettingsCollectionItemAttributes()
	{
		$returnValue = array(
			ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
			ItemAttribute::with_Name_Caption_DataType("User_UserRolesCollection", "User_UserRolesCollection", DataType::DT_INT),
			ItemAttribute::with_Name_Caption_DataType("UserSetting", "User setting", DataType::DT_ITEM),	
			ItemAttribute::with_Name_Caption_DataType("Value", "Value", DataType::DT_STRING),
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0));		


		$ItemAttribute = ItemAttribute::getItemAttribute($returnValue, "UserSetting");
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User_UserRolesCollection_UserSettingsCollection", "UserSetting", $returnValue, $this->dbUserSettingRepository->itemAttributes, "UserSetting", "Id"));

		return $returnValue;
	}

	public function deleteAll_User_UserRolesCollection()
	{
		$query = "DELETE FROM User_UserRolesCollection";
		$params = array();
		$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}

	public function deleteAll_User_UserRolesCollection_UserSettingsCollection()
	{
		$query = "DELETE FROM User_UserRolesCollection_UserSettingsCollection";
		$params = array();
		$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}
}

?>