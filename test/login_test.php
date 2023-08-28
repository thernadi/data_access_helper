<?php
namespace Rasher\Test;
use Rasher\Data\PDO\DataManagement\{ConnectionData}; //PDO extension
//use Rasher\Data\MySQLi\DataManagement\{ConnectionData}; //MySQLi extension
use Rasher\Data\UserManagement\{DbUserRoleSettingRepository,DbUserRoleRepository,DbUserRepository};
use Rasher\Data\Type\{LogicalOperator,Param,FilterParam,Operator,ItemAttribute};
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
		try
		{	
			$this->dbUserRepository->dbUserRoleRepository->beginTransaction();
			$this->dbUserRepository->beginTransaction();

			$this->dbUserRepository->dbUserRoleRepository->deleteAll_UserRole_UserRoleSettingsCollection();
			$this->dbUserRepository->deleteAll_User_UserRolesCollection();
			$this->dbUserRepository->deleteAll();
			
			$this->dbUserRepository->dbUserRoleRepository->commitTransaction();
			$this->dbUserRepository->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->dbUserRepository->dbUserRoleRepository->rollbackTransaction();
			$this->dbUserRepository->rollbackTransaction();
			throw $e;
		}
	}

	public function deleteUserRelatedBaseData()
	{
		try
		{	
			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->beginTransaction();
			$this->dbUserRepository->dbUserRoleRepository->beginTransaction();

			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->deleteAll();
			$this->dbUserRepository->dbUserRoleRepository->deleteAll();

			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->commitTransaction();
			$this->dbUserRepository->dbUserRoleRepository->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->rollbackTransaction();
			$this->dbUserRepository->dbUserRoleRepository->rollbackTransaction();
			throw $e;
		}
	}

	public function createUserRelatedBaseData()
	{	
		try
		{	
			//UserRoleSetting
			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->beginTransaction();

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Name", "ACTIVE");
			if (!$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->checkItemInDB($filters, $item))
			{
				$item = $this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->getNewItemInstance();
				$item["Name"]->value = "ACTIVE";
				$item["DefaultValue"]->value = 0;
				$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->save($item);
			}

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Name", "LOGLEVEL");
			if (!$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->checkItemInDB($filters, $item))
			{
				$item = $this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->getNewItemInstance();
				$item["Name"]->value = "LOGLEVEL";
				$item["DefaultValue"]->value = 1;
				$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->save($item);
			}

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Name", "ACCESS_READ");
			if (!$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->checkItemInDB($filters, $item))
			{
				$item = $this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->getNewItemInstance();
				$item["Name"]->value = "ACCESS_READ";
				$item["DefaultValue"]->value = 0;
				$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->save($item);
			}

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Name", "ACCESS_WRITE");
			if (!$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->checkItemInDB($filters, $item))
			{
				$item = $this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->getNewItemInstance();
				$item["Name"]->value = "ACCESS_WRITE";
				$item["DefaultValue"]->value = 0;
				$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->save($item);
			}

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Name", "ACCESS_DOWNLOAD");
			if (!$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->checkItemInDB($filters, $item))
			{
				$item = $this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->getNewItemInstance();
				$item["Name"]->value = "ACCESS_DOWNLOAD";
				$item["DefaultValue"]->value = 0;
				$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->save($item);
			}

			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->rollbackTransaction();
			throw $e;
		}

		try
		{		
			//UserRole
			$this->dbUserRepository->dbUserRoleRepository->beginTransaction();

			$filters = array();
			$filters[] = new Param("IsDeleted", 0);
			$filters[] = new Param("Code", "BASE_USER");
			if (!$this->dbUserRepository->dbUserRoleRepository->checkItemInDB($filters, $item))
			{
				$item = $this->dbUserRepository->dbUserRoleRepository->getNewItemInstance();
				$item["Code"]->value = "BASE_USER";
				$item["Name"]->value = "Base user";
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACTIVE", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("LOGLEVEL", "3");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_READ", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_WRITE", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_DOWNLOAD", "1");
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
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACTIVE", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("LOGLEVEL", "10");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_READ", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_WRITE", "0");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_DOWNLOAD", "1");
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
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACTIVE", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("LOGLEVEL", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_READ", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_WRITE", "1");
				$item["UserRoleSettingsCollection"]->value[] = $this->getNewUserRoleSettingCollectionItem("ACCESS_DOWNLOAD", "1");
				$this->dbUserRepository->dbUserRoleRepository->save($item);
			}

			$this->dbUserRepository->dbUserRoleRepository->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->dbUserRepository->dbUserRoleRepository->rollbackTransaction();
			throw $e;
		}
	}

	private function getNewUserRoleSettingCollectionItem($userRoleSettingName, $value)
	{
		$userRoleSettingCollectionItem = $this->dbUserRepository->dbUserRoleRepository->getNewItemInstance($this->dbUserRepository->dbUserRoleRepository->getUserRoleUserRoleSettingsCollectionItemAttributes());
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Name", $userRoleSettingName);		
		$userRoleSetting = $this->dbUserRepository->dbUserRoleRepository->dbUserRoleSettingRepository->loadByFilter2($filters);
		$userRoleSettingCollectionItem["UserRoleSetting"]->value = $userRoleSetting[0];
		$userRoleSettingCollectionItem["Value"]->value = $value;	
		return $userRoleSettingCollectionItem;
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
			$this->dbUserRepository->saveWithTransaction($this->userLogin);	
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
			$this->dbUserRepository->saveWithTransaction($this->userLogin);	
			$this->userLogin = null;
			echo "Logout success!".LINE_SEPARATOR;
		}
	}
	
	public function showUserSomeData()
	{
		if ($this->userLogin !== null)
		{
			//We will load the user's all data
			$user = $this->dbUserRepository->loadByIdWithTransaction($this->userLogin["Id"]->value); 
			echo LINE_SEPARATOR;
			echo "User:";
			$this->dbUserRepository->writeOutSimpleData($user);

			echo "UserRolesCollection.UserRole.Code:".LINE_SEPARATOR;
			$attribute = ItemAttribute::getItemAttribute($user[0], "UserRolesCollection.UserRole.Code");
			foreach($attribute as $val)
			{
				echo $val->value.LINE_SEPARATOR;
			}
			echo LINE_SEPARATOR;
			Common::writeOutLetter("-", 50, LINE_SEPARATOR);

			echo "UserRolesCollection.UserRole.UserRoleSettingsCollection.UserRoleSetting.Value".LINE_SEPARATOR;
			$attribute = ItemAttribute::getItemAttribute($user[0], "UserRolesCollection.UserRole.UserRoleSettingsCollection.Value");
			foreach($attribute as $val)
			{
				echo $val->value.LINE_SEPARATOR;
			}

			echo LINE_SEPARATOR;	
			Common::writeOutLetter("-", 50, LINE_SEPARATOR);
			echo "UserRolesCollection:";
			$this->dbUserRepository->writeOutSimpleData($user[0]["UserRolesCollection"]->value);
			echo "UserRolesCollection UserRole codes with settings and values:";
			echo LINE_SEPARATOR;
			
			foreach($user[0]["UserRolesCollection"]->value as $userRoleCollectionItem)
			{
				echo LINE_SEPARATOR;	
				echo $userRoleCollectionItem["UserRole"]->value["Code"]->value.":";
				echo LINE_SEPARATOR;
				foreach($userRoleCollectionItem["UserRole"]->value["UserRoleSettingsCollection"]->value as $userRoleUserRoleSettingCollectionItem)
				{	
					echo $userRoleUserRoleSettingCollectionItem["UserRoleSetting"]->value["Name"]->value." -> ".$userRoleUserRoleSettingCollectionItem["Value"]->value;
					echo LINE_SEPARATOR;
				}
			}
			echo LINE_SEPARATOR;	
		}
		else
		{
			echo "User is not logged in!".LINE_SEPARATOR;
		}
	}

	public function getNewUserRoleCollectionItem($userRoleCode)
	{
		$userRoleCollectionItem = $this->dbUserRepository->getNewItemInstance($this->dbUserRepository->getUserUserRolesCollectionItemAttributes());
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("Code", $userRoleCode);		
		$userRole = $this->dbUserRepository->dbUserRoleRepository->loadByFilter2($filters);
		$userRoleCollectionItem["UserRole"]->value = $userRole[0];
		return $userRoleCollectionItem;
	}

	public function registerNewUser($loginName, $password, $userRoleCollectionItemArray)
	{	
		$newUser = null;
		$filters = array();
		$filters[] = new Param("IsDeleted", 0);
		$filters[] = new Param("LoginName", $loginName);		
		if ($this->dbUserRepository->checkItemInDB($filters, $newUser))
		{		
			echo "$loginName user is already existed!".LINE_SEPARATOR;
		}
		else
		{
			//Set user's base data
			$passwordsha1 = sha1($password);
			$newUser = $this->dbUserRepository->getNewItemInstance();
			$newUser["LoginName"]->value = $loginName;	
			$newUser["Password"]->value = $passwordsha1;
		
			//Set user's UserRolesCollection
			$newUser["UserRolesCollection"]->value = $userRoleCollectionItemArray;

			$this->dbUserRepository->saveWithTransaction($newUser);
			echo "$loginName user registered!".LINE_SEPARATOR;
		}
	}

	public function deleteUser($loginName)
	{	
		$currentUser = null;
		$filters = array();
		$filters[] = new Param("LoginName", $loginName);		
		if (!$this->dbUserRepository->checkItemInDB($filters, $currentUser))
		{		
			echo "$loginName user is not existed!".LINE_SEPARATOR;
		}
		else
		{
			//We will load the user's all data
			$currentUser = $this->dbUserRepository->loadByIdWithTransaction($currentUser[0]["Id"]->value); 
			$this->dbUserRepository->deleteWithTransaction($currentUser[0]);
			echo "$loginName user deleted!".LINE_SEPARATOR;
		}
	}
}

try
{
	//TEST

	error_reporting(0); //only production environment
	//error_reporting(E_ALL); //for detail error reporting

	//Fill connectionData out before using
	//$connectionData = new ConnectionData("localhost", "userName", "password", "test"); // use it with MySQLi extension
	//$connectionData = new ConnectionData("mysql:host=localhost;dbname=test", "userName", "password"); // use it with PDO extension (MySQL)
	$connectionData = new ConnectionData("sqlsrv:server=(local);Database=test","",""); //PDO MSSQL

	//DbUserRoleSettingRepository single instance
	$dbUserRoleSettingRepository = new DbUserRoleSettingRepository($connectionData, true, "Name"); //Caching by Name

	//DbUserRoleRepository single instance
	$dbUserRoleRepository = new DbUserRoleRepository($connectionData,$dbUserRoleSettingRepository, true, "Code"); //Caching by Code
	
	//DbUserRepository single instance
	$dbUserRepository = new DbUserRepository($connectionData, $dbUserRoleRepository, true);

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
	$loginTest->deleteUserRelatedBaseData(); //UserRole + UserRoleSetting deleting

	$loginTest->createUserRelatedBaseData(); //UserRole + UserRoleSetting creating
	
	//Build cache
	echo "Using cached items:".LINE_SEPARATOR;
	$dbUserRoleRepository->buildCache(true);
	$dbUserRoleSettingRepository->buildCache(true);

	$userRoleItem = $dbUserRoleRepository->getItemFromCache("GUEST");
	echo LINE_SEPARATOR;
	echo $userRoleItem["Name"]->value.LINE_SEPARATOR;
	$userRoleSettingItem = $dbUserRoleSettingRepository->getItemFromCache("ACTIVE");
	echo $userRoleSettingItem["Name"]->value.LINE_SEPARATOR;

	//Add new userRoleSetting items to itemCache
	$userRoleSettingItem = $dbUserRoleSettingRepository->getNewItemInstance();
	$userRoleSettingItem["Name"]->value = "TEST1";
	$userRoleSettingItem["DefaultValue"]->value = "TEST1";
	$dbUserRoleSettingRepository->addItemToCache($userRoleSettingItem);
	$userRoleSettingItem = $dbUserRoleSettingRepository->getItemFromCache("TEST1");
	echo $userRoleSettingItem["Name"]->value."(Id: ".$userRoleSettingItem["Id"]->value.")".LINE_SEPARATOR;

	$userRoleSettingItem = $dbUserRoleSettingRepository->getNewItemInstance();
	$userRoleSettingItem["Name"]->value = "TEST2";
	$userRoleSettingItem["DefaultValue"]->value = "TEST2";
	$dbUserRoleSettingRepository->addItemToCache($userRoleSettingItem);
	$userRoleSettingItem = $dbUserRoleSettingRepository->getItemFromCache("TEST2");
	echo $userRoleSettingItem["Name"]->value."(Id: ".$userRoleSettingItem["Id"]->value.")".LINE_SEPARATOR;
	
	//Save new userRoleSetting items to DB
	$dbUserRoleSettingRepository->saveCache();
	
	$userRoleSettingItem = $dbUserRoleSettingRepository->getItemFromCache("TEST1");
	echo $userRoleSettingItem["Name"]->value."(Id: ".$userRoleSettingItem["Id"]->value.")".LINE_SEPARATOR;
	$userRoleSettingItem = $dbUserRoleSettingRepository->getItemFromCache("TEST2");
	echo $userRoleSettingItem["Name"]->value."(Id: ".$userRoleSettingItem["Id"]->value.")".LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #1
	echo "Finding item test #1";
	echo LINE_SEPARATOR;
	$param = array();
	$param[] = new Param("Name","Gues%", Operator::OP_LIKE);
	$param[] = new Param("Code","AA", Operator::OP_NOT_LIKE);
	$filterParam = new FilterParam($param, LogicalOperator::LO_AND);
	$foundItems = $dbUserRoleRepository->find($dbUserRoleRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);
	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #2
	echo "Finding item test #2";
	echo LINE_SEPARATOR;
	$param = array();
	$param[] = new Param("Name","Gues%", Operator::OP_LIKE);
	$param[] = new Param("Code","%ASE%", Operator::OP_LIKE);
	$filterParam = new FilterParam($param, LogicalOperator::LO_OR);
	$foundItems = $dbUserRoleRepository->find($dbUserRoleRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);
	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;


	$userRoleCollectionItemArray = array(
	$loginTest->getNewUserRoleCollectionItem("BASE_USER"),
	$loginTest->getNewUserRoleCollectionItem("GUEST"));
	$loginTest->registerNewUser("user_1", "123", $userRoleCollectionItemArray);
	$loginTest->login("user_1", "123");
	$loginTest->showUserSomeData();
	$loginTest->logout();

	$userRoleCollectionItemArray = array(
	$loginTest->getNewUserRoleCollectionItem("BASE_USER"));
	$loginTest->registerNewUser("user_2", "123", $userRoleCollectionItemArray);
	$userRoleCollectionItemArray = array(
	$loginTest->getNewUserRoleCollectionItem("ADMIN"));
	$loginTest->registerNewUser("admin", "admin", $userRoleCollectionItemArray);


	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	$dbUserRepository->buildCache(true);
	//Find test #3
	echo "Finding item test #3";
	echo LINE_SEPARATOR;
	$param = array();
	$param[] = new Param("LastLoginDateTime", date('Y-m-d H:i:s', strtotime("2024-08-08")), Operator::OP_LESS_THAN);
	$filterParam = new FilterParam($param);
	$foundItems = $dbUserRepository->find($dbUserRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);

	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #4
	echo "Finding item test #4";
	echo LINE_SEPARATOR;
	$param = array();
	$param[] = new Param("LastLoginDateTime", date('Y-m-d H:i:s', strtotime("2023-08-08")), Operator::OP_GREATER_THAN_OR_EQUAL);
	$param[] = new Param("LastLoginDateTime", null, Operator::OP_EQUAL);	
	$filterParam = new FilterParam($param, LogicalOperator::LO_OR);
	$foundItems = $dbUserRepository->find($dbUserRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);
	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #5: any user who has "BASE_USER" UserRole and has "LOGLEVEL" = 3 setting //2 user items
	echo "Finding item test #5";
	echo LINE_SEPARATOR;
	$param = array();

	$param[] = new Param("UserRolesCollection.UserRole.Code","BASE_USER", Operator::OP_EQUAL);		
	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.UserRoleSetting.Name", "LOGLEVEL", Operator::OP_EQUAL);	
	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.Value", "3", Operator::OP_EQUAL);			

	$filterParam = new FilterParam($param, LogicalOperator::LO_AND);
	$foundItems = $dbUserRepository->find($dbUserRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);

	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #6: any user who has "BASE_USER" UserRole and has "LOGLEVEL" = 10 setting //0 user items
	//DbUserRepository.depth = 2, if you set it to 0 you cannot get the correct result
	echo "Finding item test #6";
	echo LINE_SEPARATOR;
	$param = array();

	$param[] = new Param("UserRolesCollection.UserRole.Code", "BASE_USER", Operator::OP_EQUAL);		
	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.Value", "10", Operator::OP_EQUAL);		
	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.UserRoleSetting.Name", "LOGLEVEL", Operator::OP_EQUAL);		

	$filterParam = new FilterParam($param, LogicalOperator::LO_AND);
	$foundItems = $dbUserRepository->find($dbUserRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);

	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #7:
	echo "Finding item test #7";
	echo LINE_SEPARATOR;
	$param = array();

	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.Value", "10", Operator::OP_EQUAL);	
	$param[] = new Param("UserRolesCollection.UserRole.Code", "GUEST", Operator::OP_EQUAL);			
	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.UserRoleSetting.Name", "LOGLEVEL1", Operator::OP_EQUAL); //no LOGLEVEL1		

	$filterParam = new FilterParam($param, LogicalOperator::LO_AND);
	$foundItems = $dbUserRepository->find($dbUserRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);

	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//Find test #8:
	echo "Finding item test #8";
	echo LINE_SEPARATOR;
	$param = array();

	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.Value", "10", Operator::OP_EQUAL);	
	$param[] = new Param("UserRolesCollection.UserRole.Code", "GUEST", Operator::OP_EQUAL);			
	$param[] = new Param("UserRolesCollection.UserRole.UserRoleSettingsCollection.UserRoleSetting.Name", "LOGLEVEL1", Operator::OP_EQUAL); //no LOGLEVEL1	

	$filterParam = new FilterParam($param, LogicalOperator::LO_OR);
	$foundItems = $dbUserRepository->find($dbUserRepository->getAllItemsFromCache(true), $filterParam, false); 
	echo "count: ".count($foundItems);

	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;

	//IT IS NOT GOOD YET - NEED FIX
	/*
	echo "DB - IS NULL Test";
	echo LINE_SEPARATOR;
	$filters = array();
	$filters[] = new Param("LastLoginDateTime", null, Operator::OP_IS_NULL);
	$users = $dbUserRepository->LoadByFilter2($filters);
	echo "count: ".count($users);

	echo LINE_SEPARATOR;
	echo LINE_SEPARATOR;
	*/

	$loginTest->deleteUser("user_1"); //physically delete
	$loginTest->deleteUser("user_2"); //physically delete	
}
catch(\Throwable $e)
{
	echo $e->getMessage();
}
?>