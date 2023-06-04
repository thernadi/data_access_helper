<?php
//Copyright (c) 2022 Tamas Hernadi

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
	
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
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
	public $targetItemAttibutes = null;	
	public $sourceMappingAttributeName = null;
	public $targetMappingAttributeName = null;

	public function __construct($sourceTableName, $targetTableName, $sourceItemAttributes, $targetItemAttibutes, $sourceMappingAttributeName, $targetMappingAttributeName) 
	{	
		$this->sourceTableName = $sourceTableName;
		$this->targetTableName = $targetTableName;						
		$this->sourceItemAttributes = $sourceItemAttributes;
		$this->targetItemAttibutes = $targetItemAttibutes;			
		$this->sourceMappingAttributeName = $sourceMappingAttributeName;	
		$this->targetMappingAttributeName = $targetMappingAttributeName;
	}
}

class ItemAttribute
{
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
				$returnValue[] = $item;
			}
		}
			
		return $returnValue;		
	}
		
	//$itemAttributes: ItemAttribute array
	public static function getItemAttribute($itemAttributes, $attributeName) 
	{
		$returnValue = null;	
		
		foreach($itemAttributes as $key => $val) 
		{
			if ($val->name === $attributeName)
			{
				$returnValue = $val;
				break;
			}			
		}	
		return $returnValue;		
	}
	
	public function convertedValue($value)
	{
		$returnValue = $value;
		if($this->dataFormat !== null)
		{
			switch ($this->dataType)
			{
				case DataType::DT_DATETIME:
					$date = strtotime($value);
					$returnValue = date($this->dataFormat, $date);
				break;
				case DataType::DT_TIMESTAMP:
					$date = date_create();
					date_timestamp_set($date, $value);
					$returnValue = date_format($date, $this->dataFormat);
				break;
				case DataType::DT_DATETIME_ORIGINAL: //Cannot use in mysql db data
					$date = strtotime($value);
					$returnValue = $date;
				break;
				case DataType::DT_TIMESTAMP_ORIGINAL: //Cannot use in mysql db data
					$date = date_create();
					date_timestamp_set($date, $value);
					$returnValue = $date;
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
