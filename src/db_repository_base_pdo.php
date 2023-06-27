<?php
//Copyright (c) 2022 Tamas Hernadi
//Db Repository for access Databases using PDO extension
//Current version: 2.26

//Database table rules: all table contains the fields belows in database.
//Table level existed columns:
//Id (int, required, primary key)
//IsDeleted (boolean, default 0)

namespace Rasher\Data\PDO\DataManagement;
use PDO;
use Rasher\Data\DataManagement\{DbRepositoryBase,BindingParam};
use Rasher\Data\Type\{DataType,ItemAttribute};

include_once __DIR__."/data_access_layer_helper_pdo.php";
include_once __DIR__."/db_repository_base.php";
include_once __DIR__."/data_type_helper.php";
include_once __DIR__."/common_static_helper.php";

abstract class DbRepository extends DataAccessLayerHelper
{
	use DbRepositoryBase;

	protected function convertParamArrayToDBSpecificParamArray($paramArray, $itemAttributes = null)
	{
		if ($itemAttributes === null)
		{
			$itemAttributes = $this->itemAttributes;
		}

		$returnValue = array();
		foreach ($paramArray as $param) 
		{
			$attr = ItemAttribute::getItemAttribute($itemAttributes, $param->name);
			$bindingType = "";		
			switch ($attr->dataType) 
			{
			case DataType::DT_DATETIME:
			case DataType::DT_TIMESTAMP:
			case DataType::DT_FLOAT:
			case DataType::DT_DOUBLE:
				$bindingType = PDO::PARAM_STR;
				break;
			case DataType::DT_ITEM:	
			case DataType::DT_INT:
				$bindingType = PDO::PARAM_INT;
				break;
			case DataType::DT_BOOL:
				$bindingType = PDO::PARAM_BOOL;
				break;
			case DataType::DT_BLOB:
				$bindingType = PDO::PARAM_LOB;
				break;
			default:
				$bindingType = PDO::PARAM_STR;
			}

			$returnValue[] = new BindingParam($param->name, $bindingType, $param->value);
		}
		return $returnValue;
	}
}

?>
