<?php
//Copyright (c) 2022 Tamas Hernadi
//Data Type Helper
//Current version: 2.35

namespace Rasher\Data\Type;

abstract class DataType
{
	const DT_DATETIME = 1; //DEFAULT STRING CONVERSION WITH FORMAT
	const DT_TIMESTAMP = 2;//DEFAULT STRING CONVERSION WITH FORMAT
	const DT_DATETIME_ORIGINAL = 3; //DATETIME TYPE ORIGINAL FOR DATETIME CALCULATION (NOT REPRESENTATION)
	const DT_TIMESTAMP_ORIGINAL = 4; //DATETIME TYPE ORIGINAL FOR DATETIME CALCULATION (NOT REPRESENTATION)
	const DT_STRING = 5;
	const DT_FLOAT = 6;
	const DT_DOUBLE = 7;	
	const DT_INT = 8;
	const DT_BOOL = 9;
	const DT_LIST = 10;
	const DT_ITEM = 11;
	const DT_BLOB = 12;
}

class Operator
{
	const OP_EQUAL = 1;
	const OP_NOT_EQUAL = 2;	
	const OP_LESS_THAN = 3;
	const OP_LESS_THAN_OR_EQUAL = 4;
	const OP_GREATER_THAN = 5;
	const OP_GREATER_THAN_OR_EQUAL = 6;
	const OP_LIKE = 7; //the joker character is %
	const OP_NOT_LIKE = 8; //the joker character is %
	const OP_IS_NULL = 9; //DB specific only

	public static function getOperatorForDB($operator)
	{
		$returnValue = null;
		switch ($operator) 
		{
			case Operator::OP_EQUAL:
				$returnValue = "=";
				break;
			case Operator::OP_NOT_EQUAL:
				$returnValue = "<>";
				break;				
			case Operator::OP_LESS_THAN:
				$returnValue = "<";
				break;					
			case Operator::OP_LESS_THAN_OR_EQUAL:
				$returnValue = "<=";
				break;					
			case Operator::OP_GREATER_THAN:
				$returnValue = ">";
				break;					
			case Operator::OP_GREATER_THAN_OR_EQUAL:
				$returnValue = ">=";
				break;				
			case Operator::OP_LIKE:
				$returnValue = "LIKE";
				break;		
			case Operator::OP_NOT_LIKE:
				$returnValue = "NOT LIKE";
				break;						
			case Operator::OP_IS_NULL:
				$returnValue = "IS NULL";
				break;						
			default:
				$returnValue = "=";					
		}
		return $returnValue;
	}
}


abstract class LogicalOperator
{
	const LO_OR = 1;
	const LO_AND = 2;
}

//Key-Value parameter class
class Param
{
	public $name = null;
	public $value = null;
	public $operator = null;
	
	public function __construct($name, $value, $operator = Operator::OP_EQUAL)
	{
		$this->name = $name;
		$this->value = $value;
		$this->operator = $operator;
	}
	
	public static function getParam($name, $paramArray)
	{
		$returnValue = null;
		foreach($paramArray as $param)
		{
			if ($param->name === $name)
			{
				$returnValue = $param;
				break;
			}		
		}		
		return $returnValue;
	}
}

//$logicalOperator : LogicalOperator
//$paramArray: Param array
class FilterParam 
{
	public $paramArray = null;
	public $logicalOperator = null;	

	public function __construct($paramArray, $logicalOperator = null)
	{
		$this->paramArray = $paramArray;
		$this->logicalOperator = $logicalOperator;
	}
}

class ReferenceDescriptor
{
	public $sourceTableName = null;
	public $targetTableName = null;	
	public $sourceItemAttributes = null;
	public $targetItemAttributes = null;	
	public $sourceMappingAttributeName = null;
	public $targetMappingAttributeName = null;

	public function __construct($sourceTableName, $targetTableName, $sourceItemAttributes, $targetItemAttributes, $sourceMappingAttributeName, $targetMappingAttributeName) 
	{	
		$this->sourceTableName = $sourceTableName;
		$this->targetTableName = $targetTableName;						
		$this->sourceItemAttributes = $sourceItemAttributes;
		$this->targetItemAttributes = $targetItemAttributes;			
		$this->sourceMappingAttributeName = $sourceMappingAttributeName;	
		$this->targetMappingAttributeName = $targetMappingAttributeName;
	}
}

class CachedItem
{
	public $item = null;
	public $isFullyLoaded = false;
	public function __construct($item)
	{
		$this->item = $item;
	}
}

class ItemAttribute
{
	public $originalValue = null;
	public $name = null;
	public $caption = null;
	public $dataType = null;
	public $dataFormat = null;
	public $required = null;
	public $readonly = null;
	public $referenceDescriptor = null;
	public $value = null;
	public $orderByIndex = null;
	public $isVisible = null;
	public $defaultValue = null;
	public $defaultCaption = null;
	
	public function __construct($name, 
	$caption, 
	$dataType,
	$dataFormat = null,	
	$required = false,
	$readonly = false,
	$isVisible = true, 
	$defaultValue = null, 
	$defaultCaption = null)
	{
		$this->name = $name;
		$this->caption = $caption;
		$this->dataType = $dataType;		
		$this->dataFormat = $dataFormat;
		$this->required = $required;
		$this->readonly = $readonly;
		$this->isVisible = $isVisible;
		$this->defaultValue = $defaultValue;
		$this->defaultCaption = $defaultCaption;
	}

	//constructor
	public static function with_Name_DataType($name, $dataType)
	{
		$returnValue = new self($name, $name, $dataType);
		return $returnValue;
	}

	//constructor
	public static function with_Name_DataType_DataFormat($name, $dataType, $dataFormat)
	{
		$returnValue = new self($name, $name, $dataType);
		$returnValue->dataFormat = $dataFormat;
		return $returnValue;
	}
	
	//constructor
	public static function with_Name_Caption_DataType($name, $caption, $dataType)
	{
		$returnValue = new self($name, $caption, $dataType);
		return $returnValue;
	}
	
	//constructor
	public static function with_Name_Caption_DataType_DefaultValue($name, $caption, $dataType, $defaultValue)
	{
		$returnValue = new self($name, $caption, $dataType);
		$returnValue->defaultValue = $defaultValue;	
		return $returnValue;
	}
		

	//constructor
	public static function with_Name_Caption_DataType_DataFormat($name, $caption, $dataType, $dataFormat)
	{
		$returnValue = new self($name, $caption, $dataType);
		$returnValue->dataFormat = $dataFormat;	
		return $returnValue;
	}

	public function setReferenceDescriptor($referenceDescriptor)
	{	
		$this->referenceDescriptor = $referenceDescriptor;	
		if ($referenceDescriptor !== null && $this->dataType === DataType::DT_LIST) 
		{
			$this->value = array();
		}
	}


	//$array: ItemAttribute array
	public static function getSimpleCopiedItemAttributeArray($array)
	{
		$returnValue = array();
		foreach($array as $val) 
		{
			if(is_array($val))
			{
				$returnValue[] = ItemAttribute::getSimpleCopiedItemAttributeArray($val);	
			}
			else if(is_object($val))
			{	
				$item = new ItemAttribute($val->name, $val->caption, $val->dataType, $val->dataFormat, $val->required, $val->readonly, $val->isVisible, $val->defaultValue, $val->defaultCaption);				
				$item->value = $val->value;
				$item->orderByIndex = $val->orderByIndex;

				if ($val->dataType === DataType::DT_LIST || $val->dataType === DataType::DT_ITEM)
				{
					$item->setReferenceDescriptor($val->referenceDescriptor);
				}
				$returnValue[$item->name] = $item;
			}
		}
		return $returnValue;		
	}
		
	//$itemAttributes: ItemAttribute array
	public static function getItemAttribute($itemAttributes, $attributeName) 
	{	
		$returnValue = null;			
		if(str_contains($attributeName, "."))
		{
			$attributeNameExploded = explode(".", $attributeName);				
			$itemAttributes = ItemAttribute::getItemAttribute($itemAttributes, $attributeNameExploded[0]);

			$attributeName = "";
			$first = true;
			foreach($attributeNameExploded as $val)
			{
				if ($first)
				{
					$first = false;
					continue;
				}

				$attributeName .= $val.".";
			}
			$attributeName = substr($attributeName, 0, strlen($attributeName) - 1);

			$itemAttributes = $itemAttributes->value;
			if (count($itemAttributes) > 0 
			&& is_array($itemAttributes[array_keys($itemAttributes)[0]]))
			{	
				$returnValue = array();
				foreach($itemAttributes as $val)
				{	
					$returnValue[] = ItemAttribute::getItemAttribute($val, $attributeName);
				}
			}
			else
			{
				$returnValue = ItemAttribute::getItemAttribute($itemAttributes, $attributeName);
			}
		}
		else
		{
			if (count($itemAttributes) > 0)
			{
				if (is_object($itemAttributes[array_keys($itemAttributes)[0]]))
				{
					foreach($itemAttributes as $key => $val) 
					{		
						if($val->name === $attributeName)
						{
							$returnValue = $val;
							break;
						}			
					}
				}
				else if (is_array($itemAttributes))
				{
					$returnValue = array();
					foreach($itemAttributes as $val)
					{	
						$returnValue[] = ItemAttribute::getItemAttribute($val, $attributeName);
					}
				}
			}
		}
		if ($returnValue !== null)
		{		
			$returnValue = ItemAttribute::eliminateOutterArray($returnValue);
		}
		return $returnValue;		
	}

	private static function eliminateOutterArray($param)
	{
		$returnValue = $param;
		if (!is_object($param) 
		&& count($param) > 0 
		&& is_array($param[0]))
		{
			$returnValue = array();
			foreach($param as $val)
			{
				$returnValue = array_merge($returnValue, ItemAttribute::eliminateOutterArray($val));
			}
		}
		return $returnValue;
	}

	public function convertToBaseType($value)
	{
		$returnValue = $value;
		if ($returnValue !== null)
		{
			$dateFormat = $this->dataFormat;
			if($dateFormat === null)
			{
				$dateFormat = "Y-m-d H:i:s";
			}

			switch ($this->dataType)
			{
				case DataType::DT_DATETIME_ORIGINAL: //Not DB
					$date = strtotime($value);
					$returnValue = $date;
					break;
				case DataType::DT_TIMESTAMP_ORIGINAL: //Not DB
					$date = date_create();
					date_timestamp_set($date, $value);
					$returnValue = $date;
					break;		
				case DataType::DT_DATETIME:
					$date = strtotime($value);
					$returnValue = date($dateFormat, $date);
					break;
				case DataType::DT_TIMESTAMP:
					$date = date_create();
					date_timestamp_set($date, $value);
					$returnValue = date_format($date, $dateFormat);
					break;								
				case DataType::DT_FLOAT:
					$returnValue = (float)$value;
					break;
				case DataType::DT_DOUBLE:
					$returnValue = (double)$value;
					break;
				case DataType::DT_INT:
					$returnValue = (int)$value;
					break;		
				case DataType::DT_BOOL:
					$returnValue = (bool)$value;
					break;				
				default:
					$returnValue = $value;
			}
		}
		return $returnValue;
	}
	
}
?>
