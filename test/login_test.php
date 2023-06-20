<?php
namespace Rasher\Test;
use Rasher\Data\MySQLi\DataManagement\{ConnectionData};
use Rasher\Data\UserManagement\{DbUserSettingRepository,DbUserRoleRepository,DbUserRepository};
use Rasher\Data\Type\{LogicalOperator,Param,FilterParam,ItemAttribute};
use Rasher\Common\{Common};

include_once __DIR__."/user_data_repository.php";

class LoginTest
{
	public $dbUserRepository = null;
	public $userLogin = null;

    public function __construct($dbUserRepository)
	{
		$this->dbUserRepository = $dbUserRepository;
	}

	public function deleteUserData()
	{
		$this->dbUserRepository->deleteAll_User_UserRolesCollection_UserSettingsCollection();
		$this->dbUserRepository->deleteAll_User_UserRolesCollection();
		$this->dbUserRepository->deleteAll();
	}

	public function deleteUserRelatedBaseData()
	{
		$this->dbUserRepository->dbUserRoleRepository->deleteAll();
		$this->dbUserRepository->dbUserSettingRepository->deleteAll();
	}

	public function createUserRelatedBaseData()
	{
		//UserRole
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Code", "BASE_USER");
		if (!$this->dbUserRepository->dbUserRoleRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserRoleRepository->getNewItemInstance();
			$item["Code"]->value = "BASE_USER";
			$item["Name"]->value = "Base user";
			$this->dbUserRepository->dbUserRoleRepository->save($item);
		}

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Code", "GUEST");
		if (!$this->dbUserRepository->dbUserRoleRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserRoleRepository->getNewItemInstance();
			$item["Code"]->value = "GUEST";
			$item["Name"]->value = "Guest";
			$this->dbUserRepository->dbUserRoleRepository->save($item);
		}

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Code", "ADMIN");
		if (!$this->dbUserRepository->dbUserRoleRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserRoleRepository->getNewItemInstance();
			$item["Code"]->value = "ADMIN";
			$item["Name"]->value = "Admin";
			$this->dbUserRepository->dbUserRoleRepository->save($item);
		}

		//UserSetting
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Name", "ACTIVE");
		if (!$this->dbUserRepository->dbUserSettingRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserSettingRepository->getNewItemInstance();
			$item["Name"]->value = "ACTIVE";
			$item["DefaultValue"]->value = 0;
			$this->dbUserRepository->dbUserSettingRepository->save($item);
		}

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Name", "LOGLEVEL");
		if (!$this->dbUserRepository->dbUserSettingRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserSettingRepository->getNewItemInstance();
			$item["Name"]->value = "LOGLEVEL";
			$item["DefaultValue"]->value = 1;
			$this->dbUserRepository->dbUserSettingRepository->save($item);
		}

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Name", "ACCESS_READ");
		if (!$this->dbUserRepository->dbUserSettingRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserSettingRepository->getNewItemInstance();
			$item["Name"]->value = "ACCESS_READ";
			$item["DefaultValue"]->value = 0;
			$this->dbUserRepository->dbUserSettingRepository->save($item);
		}

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Name", "ACCESS_WRITE");
		if (!$this->dbUserRepository->dbUserSettingRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserSettingRepository->getNewItemInstance();
			$item["Name"]->value = "ACCESS_WRITE";
			$item["DefaultValue"]->value = 0;
			$this->dbUserRepository->dbUserSettingRepository->save($item);
		}

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Name", "ACCESS_DOWNLOAD");
		if (!$this->dbUserRepository->dbUserSettingRepository->checkItemInDB($filters, $item))
		{
			$item = $this->dbUserRepository->dbUserSettingRepository->getNewItemInstance();
			$item["Name"]->value = "ACCESS_DOWNLOAD";
			$item["DefaultValue"]->value = 0;
			$this->dbUserRepository->dbUserSettingRepository->save($item);
		}
	}

    public function login($loginName, $password)
    {
		$returnValue = false;
		$currentDate = date('Y-m-d H:i:s');	

		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("LoginName", $loginName);
		$filters[] = new Param("Password", sha1($password));
		if ($this->dbUserRepository->checkItemInDB($filters, $this->userLogin))
		{
			$this->userLogin = $this->userLogin[0];
			$this->userLogin["IsLogged"]->value = 1;
			$this->userLogin["LastLoginDateTime"]->value = $currentDate;
			$this->dbUserRepository->save($this->userLogin);	
			$returnValue = true;
			echo "Login success!".LINE_SEPARATOR;
		}
		else
		{
			echo "Login failed!".LINE_SEPARATOR;
		}
		return $returnValue;
    }

	public function logout()
    {
		if ($this->userLogin !== null)
		{			
			$this->userLogin["IsLogged"]->value = 0;
			$this->dbUserRepository->save($this->userLogin);	
			$this->userLogin = null;
			echo "Logout success!".LINE_SEPARATOR;
		}
	}
	
	public function showUserSomeData()
	{
		if ($this->userLogin !== null)
		{
			//We will load the user's all data
			$user = $this->dbUserRepository->loadById($this->userLogin["Id"]->value); 
			echo LINE_SEPARATOR;
			echo "User:";
			$this->dbUserRepository->writeOutSimpleData($user);

			echo "DefaultUserRole.Name: ";
			$defaultUserRoleAttributeNameAttribute = ItemAttribute::getItemAttribute($user[0], "DefaultUserRole.Name");
			echo $defaultUserRoleAttributeNameAttribute->value;			
			echo LINE_SEPARATOR;
			Common::writeOutLetter("-", 50, LINE_SEPARATOR);

			echo "UserRolesCollection.UserRole.Code:".LINE_SEPARATOR;
			$userRolesCollectionUserRoleAttributeCodeAttribute = ItemAttribute::getItemAttribute($user[0], "UserRolesCollection.UserRole.Code");
			foreach($userRolesCollectionUserRoleAttributeCodeAttribute as $val)
			{
				echo $val->value.LINE_SEPARATOR;
			}
			echo LINE_SEPARATOR;
			Common::writeOutLetter("-", 50, LINE_SEPARATOR);

			echo "UserRolesCollection.UserSettingsCollection.Value".LINE_SEPARATOR;
			$userRolesCollectionUserSettingsCollectionAttributeValueAttribute = ItemAttribute::getItemAttribute($user[0], "UserRolesCollection.UserSettingsCollection.Value");
			foreach($userRolesCollectionUserSettingsCollectionAttributeValueAttribute as $val)
			{
				echo $val->value.LINE_SEPARATOR;
			}

			echo LINE_SEPARATOR;	
			Common::writeOutLetter("-", 50, LINE_SEPARATOR);
			echo "UserRolesCollection:";
			$userRolesCollectionAttribute = $user[0]["UserRolesCollection"];
			$this->dbUserRepository->writeOutSimpleData($userRolesCollectionAttribute->value);
			echo "UserRolesCollection UserRole codes with settings and values:";
			echo LINE_SEPARATOR;
			foreach($userRolesCollectionAttribute->value as $userRoleCollectionItem)
			{
				$userRoleAttributeNameAttribute = ItemAttribute::getItemAttribute($userRoleCollectionItem, "UserRole.Code");	
				echo $userRoleAttributeNameAttribute->value;
				echo LINE_SEPARATOR;
				echo "User setting names:".LINE_SEPARATOR;
				$userSettingAttributeNameAttribute = ItemAttribute::getItemAttribute($userRoleCollectionItem, "UserSettingsCollection.UserSetting.Name");	
				foreach($userSettingAttributeNameAttribute as $value)
				{
					echo " -> ".$value->value;
					echo LINE_SEPARATOR;					
				}

				echo "UserSettings with names and values:".LINE_SEPARATOR;
				$userSettingCollectionAttribute = $userRoleCollectionItem["UserSettingsCollection"];
				foreach($userSettingCollectionAttribute->value as $userSettingCollectionItem)
				{	
					$userSettingCollectionAttributeUserSettingNameAttribute = ItemAttribute::getItemAttribute($userSettingCollectionItem, "UserSetting.Name");
					echo " -> ".$userSettingCollectionAttributeUserSettingNameAttribute->value." : ".$userSettingCollectionItem["Value"]->value;
					echo LINE_SEPARATOR;	
				}
				echo LINE_SEPARATOR;	
			}

			echo LINE_SEPARATOR;	
		}
		else
		{
			echo "User is not logged in!".LINE_SEPARATOR;
		}
	}

	private function getNewUserRoleCollectionItem($userRoleCode, $settingValuesParamArrayArray)
	{
		$userRoleCollectionItem = $this->dbUserRepository->getNewItemInstance($this->dbUserRepository->getUserUserRolesCollectionItemAttributes());
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Code", $userRoleCode);		
		$userRole = $this->dbUserRepository->dbUserRoleRepository->loadByFilter2($filters);
		$userRoleCollectionItem["UserRole"]->value = $userRole[0];

		foreach($settingValuesParamArrayArray as $settingValuesParamArray)
		{
			$settingCode = Param::getParam("Code", $settingValuesParamArray)->value;
			$settingValue = Param::getParam("Value", $settingValuesParamArray)->value;

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Name", $settingCode);		
			$userSetting = $this->dbUserRepository->dbUserSettingRepository->loadByFilter2($filters);
	
			$userRoleCollectionItemUserSettingCollectionItem = $this->dbUserRepository->getNewItemInstance($this->dbUserRepository->getUserUserRolesCollectionUserSettingsCollectionItemAttributes());
			$userRoleCollectionItemUserSettingCollectionItem["UserSetting"]->value = $userSetting[0];
			$userRoleCollectionItemUserSettingCollectionItem["Value"]->value = $settingValue;
			$userRoleCollectionItem["UserSettingsCollection"]->value[] = $userRoleCollectionItemUserSettingCollectionItem;
		}
		return $userRoleCollectionItem;
	}

	public function registerNewUser($loginName, $password)
	{	
		$newUser = null;
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("LoginName", $loginName);		
		if ($this->dbUserRepository->checkItemInDB($filters, $newUser))
		{		
			echo "User is already existed!".LINE_SEPARATOR;
		}
		else
		{
			//Set user's base data
			$passwordsha1 = sha1($password);
			$newUser = $this->dbUserRepository->getNewItemInstance();
			$newUser["LoginName"]->value = $loginName;	
			$newUser["Password"]->value = $passwordsha1;
			
			//Set user's DefaultUserRole
			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Code", "BASE_USER");		
			$userRole = $this->dbUserRepository->dbUserRoleRepository->loadByFilter2($filters);
			$newUser["DefaultUserRole"]->value = $userRole[0];
	
			//Set user's UserRolesCollection

			//Adding Guest UserRole
			$settingValuesParamArrayArray = array();
			$settingValuesParamArrayArray[] = array(new Param("Code", "ACTIVE"), new Param("Value", 1));
			$settingValuesParamArrayArray[] = array(new Param("Code", "LOGLEVEL"), new Param("Value", 10));		
			$newUser["UserRolesCollection"]->value[] = $this->getNewUserRoleCollectionItem("GUEST", $settingValuesParamArrayArray);

			//Adding Base_User UserRole
			$settingValuesParamArrayArray = array();
			$settingValuesParamArrayArray[] = array(new Param("Code", "ACTIVE"), new Param("Value", 1));
			$settingValuesParamArrayArray[] = array(new Param("Code", "LOGLEVEL"), new Param("Value", 3));		
			$newUser["UserRolesCollection"]->value[] = $this->getNewUserRoleCollectionItem("BASE_USER", $settingValuesParamArrayArray);

			$this->dbUserRepository->save($newUser);
			echo "User registered!".LINE_SEPARATOR;
		}
	}

	public function deleteUser($loginName)
	{	
		$currentUser = null;
		$filters = array();
		$filters[] = new Param("LoginName", $loginName);		
		if (!$this->dbUserRepository->checkItemInDB($filters, $currentUser))
		{		
			echo "User is not existed!".LINE_SEPARATOR;
		}
		else
		{
			//We will load the user's all data
			$currentUser = $this->dbUserRepository->loadById($currentUser[0]["Id"]->value); 
			$this->dbUserRepository->delete($currentUser[0]);
			echo "User deleted!".LINE_SEPARATOR;
		}
	}
}

try
{
	//TEST

	//error_reporting(0); //only production environment
	error_reporting(E_ALL); //for detail error reporting

	//MySQLi ConnectionData single instance
	//Fill out before using
	$connectionData = new ConnectionData("serverName", "userName", "password", "databaseName");

	//DbUserRoleRepository single instance
	$dbUserRoleRepository = new DbUserRoleRepository($connectionData);
	//DbUserSettingRepository single instance
	$dbUserSettingRepository = new DbUserSettingRepository($connectionData);
	//DbUserRepository single instance
	$dbUserRepository = new DbUserRepository($connectionData, $dbUserSettingRepository, $dbUserRoleRepository);

	$loginTest = new LoginTest($dbUserRepository);
	//Comment out if you want to modify this class for several times!
	//unset($_SESSION["LoginTest"]);

	if (!isset($_SESSION["LoginTest"]))
	{	
		$_SESSION["LoginTest"] = json_encode($loginTest);
	}
	else
	{
		$loginTest = json_decode($loginTest); 
	}

	$loginTest->deleteUserData(); //User and related data deleting
	$loginTest->deleteUserRelatedBaseData(); //UserRole + UserSetting deleting

	$loginTest->createUserRelatedBaseData(); //UserRole + UserSetting creating
	$loginTest->registerNewUser("user1", "123");
	$loginTest->login("user1", "123");
	$loginTest->showUserSomeData();
	$loginTest->logout();
	$loginTest->deleteUser("user1"); //physically delete
}
catch(\Throwable $e)
{
	echo $e->getMessage();
}
?>