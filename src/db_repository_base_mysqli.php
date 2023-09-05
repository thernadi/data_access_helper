<?php
//Copyright (c) 2022 Tamas Hernadi
//Db Repository for access MySQL Database using MySQLi extension
//Current version: 2.40

//Database table rules: all table contains the fields belows in database.
//Table level existed columns:
//Id (int, required, primary key)
//IsDeleted (boolean, default 0)

namespace Rasher\Data\MySQLi\DataManagement;
use Rasher\Data\DataManagement\{DbRepositoryBase,BindingParam};
use Rasher\Data\Type\{DataType,ItemAttribute};

include_once __DIR__."/data_access_layer_helper_mysqli.php";
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
					$bindingType = "s";
					break;
				case DataType::DT_FLOAT:
				case DataType::DT_DOUBLE:
					$bindingType = "d";
					break;
				case DataType::DT_ITEM:	
				case DataType::DT_INT:
				case DataType::DT_BOOL:
					$bindingType = "i";
					break;
				default:
					$bindingType = "s";
			}

			$returnValue[] = new BindingParam($param->name, $bindingType, $param->value);
		}
		return $returnValue;
	}

}

?>
