<?php
include_once __DIR__."/data_access_helper.php";
include_once __DIR__."/usersetting_data_repository.php";
include_once __DIR__."/userrole_data_repository.php";
//------------------------------------
//User repository implementations
//------------------------------------

class DbUserRepository extends DbRepository
{
	protected $dbUserSettingRepository = null;
	protected $dbUserRoleRepository = null;

	public function __construct($connectionData, $dbUserSettingRepository, $dbUserRoleRepository)
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
		parent::__construct($connectionData, "User", $itemAttributes);

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
		$ItemAttribute->setReferenceDescriptor(new ReferenceDescriptor("User_UserRolesCollection", "User_UserRolesCollection_UserSettingsCollection", $returnValue, $this->getUserUserRolesCollectionUserSettingsCollectionItemAttributes(), "Id", "User_UserRolesCollection",));

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
}

//DbUserRepository single instance
$dbUserRepository = new DbUserRepository($connectionData, $dbUserSettingRepository, $dbUserRoleRepository);


?>