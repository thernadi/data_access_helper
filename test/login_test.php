<?php
include_once __DIR__."/user_data_repository.php";

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
	
	public function showUserSomeData()
	{
		if ($this->userLogin !== null)
		{
			$userId = ItemAttribute::getItemAttribute($this->userLogin, "Id");
			//We will load the user's all data
			$user = $this->dbUserRepository->loadById($userId->value); 
			echo "\n\r";
			echo "User:";
			$this->dbUserRepository->writeOutSimpleData($user);
			echo "UserDefaultRole->Name: ";
			$defaultUserRoleAttribute = ItemAttribute::getItemAttribute($user[0], "DefaultUserRole");
			$defaultUserRoleAttributeNameAttribute = ItemAttribute::getItemAttribute($defaultUserRoleAttribute->value, "Name");
			echo $defaultUserRoleAttributeNameAttribute->value;
			echo "\n\r";
			Common::writeOutLetter("-", 50);
			echo "User_UserRolesCollection:";
			$userRolesCollectionAttribute = ItemAttribute::getItemAttribute($user[0], "UserRolesCollection");
			$this->dbUserRepository->writeOutSimpleData($userRolesCollectionAttribute->value);
			echo "User_UserRolesCollection UserRole names:";
			echo "\n\r";
			foreach($userRolesCollectionAttribute->value as $userRoleCollectionItem)
			{
				$userRoleAttribute = ItemAttribute::getItemAttribute($userRoleCollectionItem, "UserRole");	
				$userRoleAttributeNameAttribute = ItemAttribute::getItemAttribute($userRoleAttribute->value, "Name");
				echo $userRoleAttributeNameAttribute->value;
				echo "\n\r";			
			}
			echo "\n\r";	
		}
		else
		{
			echo "User is not logged in!\n\r";
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
			//Set user's base data
			$passwordsha1 = sha1($password);
			$newUser = $this->dbUserRepository->getNewItemInstance();
			$itemAttributeLoginName = ItemAttribute::getItemAttribute($newUser, "LoginName");
			$itemAttributeLoginName->value = $loginName;	
			$itemAttributePassword = ItemAttribute::getItemAttribute($newUser, "Password");
			$itemAttributePassword->value = $passwordsha1;	
			
			//Set user's DefaultUserRole
			$filters = array();
			$filters[] = new BindingParam("IsDeleted", "i", 0);
			$filters[] = new BindingParam("Code", "s", "BASE_USER");		
			$userRole = $this->dbUserRepository->dbUserRoleRepository->loadByFilter2($filters);
			$itemAttributeDefaultUserRole = ItemAttribute::getItemAttribute($newUser, "DefaultUserRole");
			$itemAttributeDefaultUserRole->value = $userRole[0];
	

			//Set user's UserRolesCollection
			$itemAttributeUserRoleCollection = ItemAttribute::getItemAttribute($newUser, "UserRolesCollection");
			$userRoleCollectionItem = $this->dbUserRepository->getNewItemInstance($this->dbUserRepository->getUserUserRolesCollectionItemAttributes());

			$filters = array();
			$filters[] = new BindingParam("IsDeleted", "i", 0);
			$filters[] = new BindingParam("Code", "s", "GUEST");		
			$userRole = $this->dbUserRepository->dbUserRoleRepository->loadByFilter2($filters);
			$itemAttributeUserRoleCollectionItemUserRole = ItemAttribute::getItemAttribute($userRoleCollectionItem, "UserRole");
			$itemAttributeUserRoleCollectionItemUserRole->value = $userRole[0];

			$filters = array();
			$filters[] = new BindingParam("IsDeleted", "i", 0);
			$filters[] = new BindingParam("Name", "s", "ACTIVE");		
			$userSetting = $this->dbUserRepository->dbUserSettingRepository->loadByFilter2($filters);

			$itemAttributeUserRoleCollectionItemUserSettingCollection = ItemAttribute::getItemAttribute($userRoleCollectionItem, "UserSettingsCollection");
			$userRoleCollectionItemUserSettingCollectionItem = $this->dbUserRepository->getNewItemInstance($this->dbUserRepository->getUserUserRolesCollectionUserSettingsCollectionItemAttributes());
			$userRoleCollectionItemUserSettingCollectionItemUserSetting = ItemAttribute::getItemAttribute($userRoleCollectionItemUserSettingCollectionItem, "UserSetting");
			$userRoleCollectionItemUserSettingCollectionItemUserSetting->value = $userSetting[0];
			$userRoleCollectionItemUserSettingCollectionItemValue = ItemAttribute::getItemAttribute($userRoleCollectionItemUserSettingCollectionItem, "Value");
			$userRoleCollectionItemUserSettingCollectionItemValue->value = 1; //1 - TRUE, 0 - FALSE
			$itemAttributeUserRoleCollectionItemUserSettingCollection->value[] = $userRoleCollectionItemUserSettingCollectionItem;

			$itemAttributeUserRoleCollection->value[] = $userRoleCollectionItem;


			$this->dbUserRepository->save($newUser);
			echo "User registered!\n\r";
		}
	}

	public function deleteUser($loginName)
	{	
		$currentUser = null;
		$filters = array();
		$filters[] = new BindingParam("LoginName", "s", $loginName);		
		if (!$this->checkUserInDB($filters, $currentUser))
		{		
			echo "User is not existed!\n\r";
		}
		else
		{
			$currentUserItemAttributeId = ItemAttribute::getItemAttribute($currentUser[0], "Id");
			//We will load the user's all data
			$currentUser = $this->dbUserRepository->loadById($currentUserItemAttributeId->value); 
			$this->dbUserRepository->delete($currentUser[0]);
			echo "User deleted!\n\r";
		}
	}

	protected function checkUserInDB($filters, &$userLogin)
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
$loginTest->showUserSomeData();
$loginTest->logout();
$loginTest->deleteUser("user1"); //physically delete
?>