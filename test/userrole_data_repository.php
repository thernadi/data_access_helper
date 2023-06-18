<?php
namespace Rasher\Data\DataManagement\UserManagement;
use Rasher\Data\DataManagement\{DbRepository};
use Rasher\Data\DataManagement\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../data_access_helper.php";

//------------------------------------
//UserRole repository implementations
//------------------------------------

class DbUserRoleRepository extends DbRepository
{
	public function __construct($connectionData)
	{
		$itemAttributes = array(
		ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
		ItemAttribute::with_Name_Caption_DataType("Code", "Code", DataType::DT_STRING), //req
		ItemAttribute::with_Name_Caption_DataType("Name", "Name", DataType::DT_STRING), //req
		ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0));		
		parent::__construct($connectionData, "UserRole", $itemAttributes);
	}
}

//DbUserRoleRepository single instance
$dbUserRoleRepository = new DbUserRoleRepository($connectionData);

?>