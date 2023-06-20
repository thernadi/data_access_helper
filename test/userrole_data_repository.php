<?php
namespace Rasher\Data\UserManagement;
use Rasher\Data\MySQLi\DataManagement\{DbRepository};
use Rasher\Data\Type\{DataType,ReferenceDescriptor,ItemAttribute};

include_once __DIR__."/../db_repository_base_mysqli.php";

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

?>