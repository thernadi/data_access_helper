<?php
include_once __DIR__."/../user_data_repository.php";
include_once __DIR__."/../data_type_helper.php";

class LoginTest
{
	public $dbUserRepository = null;
	public $userLogin = null;

    public function __construct($dbUserRepository)
	{
		$this->dbUserRepository = $dbUserRepository;
	}

    public function login($loginName, $password)
    {
		$returnValue = false;
		$currentDate = date('Y-m-d H:i:s');	

		$filters = array();
		$filters[] = new BindingParam("IsDeleted", "i", 0);
		$filters[] = new BindingParam("LoginName", "s", $loginName);
		$filters[] = new BindingParam("Password", "s", sha1($password));
		if ($this->checkUserInDB($filters, $this->userLogin))
		{
			$this->userLogin = $this->userLogin[0];
			$itemAttributeIsLogged = ItemAttribute::getItemAttribute($this->userLogin, "IsLogged");
			$itemAttributeIsLogged->value = 1;	
			$itemAttributeLastLoginDateTime = ItemAttribute::getItemAttribute($this->userLogin, "LastLoginDateTime");
			$itemAttributeLastLoginDateTime->value = $currentDate;	
			$this->dbUserRepository->save($this->userLogin);	
			$returnValue = true;
			echo "Login success!\n\r";
		}
		else
		{
			echo "Login failed!\n\r";
		}
		return $returnValue;
    }

	public function logout()
    {
		if ($this->userLogin !== null)
		{			
			$itemAttributeIsLogged = ItemAttribute::getItemAttribute($this->userLogin, "IsLogged");
			$itemAttributeIsLogged->value = 0;
			$this->dbUserRepository->save($this->userLogin);	
			$this->userLogin = null;
			echo "Logout success!\n\r";
		}
	}
	
	public function registerNewUser($loginName, $password)
	{	
		$newUser = null;
		$filters = array();
		$filters[] = new BindingParam("IsDeleted", "i", 0);
		$filters[] = new BindingParam("LoginName", "s", $loginName);		
		if ($this->checkUserInDB($filters, $newUser))
		{		
			echo "User is already existed!\n\r";
		}
		else
		{
			$passwordsha1 = sha1($password);
			$newUser = $this->dbUserRepository->getNewItemInstance();
			$itemAttributeLoginName = ItemAttribute::getItemAttribute($newUser, "LoginName");
			$itemAttributeLoginName->value = $loginName;	
			$itemAttributePassword = ItemAttribute::getItemAttribute($newUser, "Password");
			$itemAttributePassword->value = $passwordsha1;	
		
			$this->dbUserRepository->save($newUser);
			echo "User registered!\n\r";
		}
	}

	public function checkUserInDB($filters, &$userLogin)
	{
		$returnValue = false;
		$userLogin = $this->dbUserRepository->loadByFilter2($filters);
		if(isset($userLogin) && count($userLogin) > 0)
		{
			$returnValue = true;
		}
		else
		{
			$userLogin = null;
		}		
		return $returnValue;	
	}
}

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


$loginTest->registerNewUser("user1", "123");
$loginTest->login("user1", "123");

$userId = ItemAttribute::getItemAttribute($loginTest->userLogin, "Id");
$user = $dbUserRepository->loadById($userId->value); //we will load all user's data
echo "\n\r";
echo "User:";
$loginTest->dbUserRepository->writeOutSimpleData($user);
echo "\n\r";
echo "\n\r";
echo "User_UserRolesCollection:";
$userRolesCollectionAttribute = ItemAttribute::getItemAttribute($user[0], "UserRolesCollection");
$loginTest->dbUserRepository->writeOutSimpleData($userRolesCollectionAttribute->value);
echo "\n\r";
echo "UserDefaultRole->Name: ";
$defaultUserRoleAttribute = ItemAttribute::getItemAttribute($user[0], "DefaultUserRole");
$defaultUserRoleAttributeNameAttribute = ItemAttribute::getItemAttribute($defaultUserRoleAttribute->value, "Name");
echo $defaultUserRoleAttributeNameAttribute->value;
echo "\n\r";
echo "\n\r";
$loginTest->logout();
?>