<?php
//Copyright (c) 2022 Tamas Hernadi
//Db Repository Base
//Current version: 2.26

namespace Rasher\Data\DataManagement;
use Rasher\Data\Type\{DataType,LogicalOperator,Param,FilterParam,ReferenceDescriptor,ItemAttribute};
use Rasher\Common\{Common};

include_once __DIR__."/data_type_helper.php";
include_once __DIR__."/common_static_helper.php";

//ABSTRACT
trait DbRepositoryBase
{
	protected $tbl = null;
	public $itemAttributes = null; //ItemAttribute object array without values, it is only the data structure

	public function __construct($connectionData, $tbl, $itemAttributes)
	{
		parent::__construct($connectionData);
		$this->tbl = $tbl;
		$this->itemAttributes = $itemAttributes;
	}

	public function getMinField($fieldName)
	{
		$query = "SELECT DISTINCT MIN(".$fieldName.") FROM ". $this->tbl . " WHERE IsDeleted = ?";
		$params[] = new Param("IsDeleted", 0);		
		return $this->executeScalar($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}

	public function getMaxField($fieldName)
	{
		$query = "SELECT DISTINCT MAX(".$fieldName.") FROM ". $this->tbl . " WHERE IsDeleted = ?";
		$params[] = new Param("IsDeleted", 0);
		return $this->executeScalar($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}

	public function getCountField($fieldName)
	{
		$query = "SELECT COUNT(".$fieldName.") FROM ". $this->tbl . " WHERE IsDeleted = ?";
		$params[] = new Param("IsDeleted", 0);		
		return $this->executeScalar($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}
	

	public function getNewItemInstance($itemAttributes = null)
	{
		$returnValue = null;
		if ($itemAttributes === null) 
		{			
			$itemAttributes	= $this->itemAttributes;
		}

		$returnValue = ItemAttribute::getSimpleCopiedItemAttributeArray($itemAttributes);

		foreach($returnValue as $key => $val) 
		{
			if ($val->value === null && $val->defaultValue !== null)
			{
				$val->value = $val->defaultValue;
			}
		}		
		return $returnValue;		
	}

	//$rowArray: array from a mysql query's return output
	public function convertToItemAttributeArrayArray($rowArray, $itemAttributes = null)
	{
		$returnValue = array(); 
		if ($itemAttributes === null) 
		{			
			$itemAttributes	= $this->itemAttributes;
		}

		foreach ($rowArray as $row)
		{
			$itemAttributesCopy = $this->getNewItemInstance($itemAttributes);
			
			foreach($row as $key => $val) 
			{
				$itemAttribute = ItemAttribute::getItemAttribute($itemAttributesCopy, $key);				
				if ($itemAttribute !== null)
				{ 
					if ($itemAttribute->dataType !== DataType::DT_LIST 
					&& $itemAttribute->dataType !== DataType::DT_ITEM)
					{
						$itemAttribute->value = $val;
					}
					else if ($itemAttribute->referenceDescriptor !== null 
					&& $itemAttribute->dataType === DataType::DT_ITEM)
					{
						$itemAttribute->value = $this->getNewItemInstance($itemAttribute->referenceDescriptor->targetItemAttributes);
						if ($itemAttribute->value !== null)
						{
							$itemAttributeId = ItemAttribute::getItemAttribute($itemAttribute->value, $itemAttribute->referenceDescriptor->targetMappingAttributeName);
							$itemAttributeId->value = $val;
						}
					}
				}
			}
			$returnValue[] = $itemAttributesCopy;	
		}
		return $returnValue;
	}
	
	//$itemAttributeArrayArray: (ItemAttribute array) array
	public function convertToRowArray($itemAttributeArrayArray)
	{
		$returnValue = array(); 
		foreach ($itemAttributeArrayArray as $itemAttributeArray)
		{
			$row = array();						
			foreach($itemAttributeArray as $key => $val) 
			{
				if ($val->dataType !== DataType::DT_LIST 
				&& $val->dataType !== DataType::DT_ITEM)
				{
					$row[$val->name] = $val->value;
				}
				else if ($val->referenceDescriptor !== null 
				&& $val->dataType === DataType::DT_ITEM)
				{
					$itemAttributeId = ItemAttribute::getItemAttribute($val->value, $val->referenceDescriptor->targetMappingAttributeName);
					$row[$val->name] = $itemAttributeId->value;
				}
				else if ($val->referenceDescriptor !== null 
				&& $val->dataType === DataType::DT_LIST)
				{
					$row[$val->name] = $this->convertToRowArray($val->value);
				}			
			}
			$returnValue[] = $row;	
		}
		return $returnValue;
	}

	//override it in derived class
	protected function convertParamArrayToDBSpecificParamArray($paramArray, $itemAttributes = null){}

	//$item: ItemAttribute array  
	//for setting saving parameters
	protected function getParamsByItem($item)
	{
		$returnValue = array();
		foreach ($item as $key => $value)
		{
			if ($value->name === "Id" 
			|| $value->dataType === DataType::DT_LIST)
			{
				continue;
			}
					
			if ($value->dataType !== DataType::DT_ITEM)
			{
				$returnValue[] = new Param($value->name, $value->value);
			}
			else //DT_ITEM
			{
				$itemAttributeId = ItemAttribute::getItemAttribute($value->value, "Id");
				$returnValue[] = new Param($value->name, $itemAttributeId->value);
			}
		}	
		return $returnValue;
	}

	//This function can load all items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	public function loadAll()
	{
		$query = "SELECT * FROM " . $this->tbl . " WHERE IsDeleted = ? ORDER BY Id asc";
		$params = array();
		$params[] = new Param("IsDeleted", 0);
		$returnValue = $this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params));
		return $this->convertToItemAttributeArrayArray($returnValue);
	}

	//This function can load all items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	public function loadByFilter($filters, $fields, $orderFields, $orderDirection = null) 
	//$filters: Param object array 
	//$fields = needed fields array  
	//$orderFields: array
	//$orderDirection: asc/desc/null
	{
		$returnValue = array();
		$query = "SELECT ";
		if  ($fields != null && count($fields) > 0)
		{
			foreach ($fields as $field)
				$query .= $field.", ";
			$query = substr($query, 0, strlen($query) - 2);
		}
		else
			$query .="*";

		$query .=" FROM " . $this->tbl;
		if ($filters != null && count($filters) > 0)
		{
			$query .=" WHERE ";
			foreach ($filters as $filter)
			{
				$query .= $filter->name." LIKE ? AND ";
			}
			$query = substr($query, 0, strlen($query) - 4);
		}
		else
			$filters = array();
		if  ($orderFields != null && count($orderFields) > 0)
		{
			$query .= " ORDER BY ";
			foreach ($orderFields as $orderField)
				$query .= $orderField.", ";
			$query = substr($query, 0, strlen($query) - 2);
			
			$orderDirection = strtolower($orderDirection);
			if ($orderDirection != null && ($orderDirection === "asc" || $orderDirection === "desc"))
				$query .=" ".$orderDirection;
			else
				$query .=" asc";
		}
	
		$returnValue = $this->execute($query, $this->convertParamArrayToDBSpecificParamArray($filters));
		return $this->convertToItemAttributeArrayArray($returnValue);
	}

	//This function can load filtered items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	//$filters: Param object array
	public function loadByFilter2($filters)
	{	
		return $this->loadByFilter($filters, array(), array("Id"), "asc");
	}

	//This function can load one item by "Id" with the type of "DT_ITEM" item together and the "DT_LIST" are loaded. 
	public function loadById($id, $tbl = null, $idAttributeName = null,  $itemAttributes = null)
	{
		$returnValue = null;
		if ($tbl === null)
		{
			$tbl = $this->tbl;
		}

		if ($idAttributeName === null)
		{
			$idAttributeName = "Id";
		}

		$query = "SELECT * FROM " . $tbl . " WHERE ".$idAttributeName." = ? AND IsDeleted = ?";
		$params = array();
		$params[] = new Param($idAttributeName, $id);
		$params[] = new Param("IsDeleted", 0);
		$returnValue = $this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params, $itemAttributes));
		$returnValue = $this->convertToItemAttributeArrayArray($returnValue, $itemAttributes);

		foreach($returnValue as $outterKey => $outterValue)
		{
			foreach ($outterValue as $key => $value)
			{
				if ($value->referenceDescriptor !== null && $value->dataType == DataType::DT_ITEM)
				{
					$itemAttributeId = ItemAttribute::getItemAttribute($value->value, $value->referenceDescriptor->targetMappingAttributeName);
					$value->value = $this->loadById($itemAttributeId->value, $value->referenceDescriptor->targetTableName, $value->referenceDescriptor->targetMappingAttributeName, $value->value)[0];
				}
				else if ($value->referenceDescriptor !== null && $value->dataType == DataType::DT_LIST)
				{
					$itemAttributeId = ItemAttribute::getItemAttribute($outterValue, $value->referenceDescriptor->sourceMappingAttributeName);
					$value->value = $this->loadById($itemAttributeId->value, $value->referenceDescriptor->targetTableName, $value->referenceDescriptor->targetMappingAttributeName, $value->referenceDescriptor->targetItemAttributes);
				}
			}
		}
		return $returnValue;
	}

	//$item: ItemAttribute array	
	public function save($item, $tbl = null) 
	{
		$params = $this->getParamsByItem($item);		

		if ($tbl === null)
		{
			$tbl = $this->tbl;
		}

		$itemAttributeId = ItemAttribute::getItemAttribute($item, "Id");
	
		$q_fnames = "";
		$q_fparams = "";
		if ($itemAttributeId->value > 0) //UPDATE
		{
			foreach ($params as $param)
			{
				$q_fnames .= $param->name." = ?, ";
			}
			$q_fnames = substr($q_fnames, 0, strlen($q_fnames) - 2);
			$params[] = new Param("Id", $itemAttributeId->value);
			$query = "UPDATE ". $tbl ." SET ".$q_fnames." WHERE Id = ?";
			$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params, $item));
		}
		else //INSERT
		{
			foreach ($params as $param)
			{
				$q_fnames .= $param->name.", ";
				$q_fparams .="?, ";
			}
			$q_fnames = substr($q_fnames, 0, strlen($q_fnames) - 2);
			$q_fparams = substr($q_fparams, 0, strlen($q_fparams) - 2);
			$query = "INSERT INTO ". $tbl ." (".$q_fnames.") VALUES (".$q_fparams.")";
			$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params, $item), $item);
		}

		foreach($item as $key => $value)
		{
			if ($value->referenceDescriptor !== null && $value->dataType == DataType::DT_LIST)
			{
				foreach($value->value as $collectionItemKey => $collectionItemValue)
				{
					$itemCollectionReferenceAttribute = ItemAttribute::getItemAttribute($collectionItemValue, $value->referenceDescriptor->targetMappingAttributeName);
					$itemCollectionReferenceAttribute->value = $itemAttributeId->value;
					$this->save($collectionItemValue, $value->referenceDescriptor->targetTableName);
				}
			}	
		}	
	}
	
	public function deleteAll()
	{
		$query = "DELETE FROM " . $this->tbl;
		$params = array();
		$this->execute($query, $this->convertParamArrayToDBSpecificParamArray($params));
	}
	
	public function delete($item, $tbl = null, &$queryBuffer = null)
	{
		$first = false;
		if ($tbl === null)
		{
			$tbl = $this->tbl;
			$first = true;
		}

		if ($queryBuffer === null)
		{
			$queryBuffer = array();
		}

		$itemAttributeId = ItemAttribute::getItemAttribute($item, "Id");

		$query = "DELETE FROM " . $tbl." WHERE Id = ?";
		$params = array();
		$params[] = new Param("Id", $itemAttributeId->value);	
		$queryBuffer[] = array("query" => $query, "params" => $params);		

		foreach($item as $key => $value)
		{
			if ($value->referenceDescriptor !== null && $value->dataType == DataType::DT_LIST)
			{
				foreach($value->value as $collectionItemKey => $collectionItemValue)
				{
					$this->delete($collectionItemValue, $value->referenceDescriptor->targetTableName, $queryBuffer);
				}
			}	
		}

		if ($first)
		{
			for($i = count($queryBuffer) - 1; $i >= 0; $i--)
			{
				$this->execute($queryBuffer[$i]["query"], $this->convertParamArrayToDBSpecificParamArray($queryBuffer[$i]["params"], $item));
			}
		}
	}

	//$name: attribute name
	//$value: search value	
	//$items: ItemAttribute array
	public function isValueInItems($name, $value, $items, &$outputItem) 
	{
		$outputItem = null;
		$returnValue = false;
		foreach ($items as $item)
		{			
			$itemAttribute = ItemAttribute::getItemAttribute($item, $name);
			if ($itemAttribute !== null)
			{				
				if ($value === $itemAttribute->value)
				{
					$returnValue = true;
					$outputItem = $item;
					break;
				}
			}
		}
		return $returnValue;
	}

	public function checkItemInDB($filters, &$item)
	{
		$returnValue = false;
		$item = $this->loadByFilter2($filters);
		if(isset($item) && count($item) > 0)
		{
			$returnValue = true;
		}
		else
		{
			$item = null;
		}		
		return $returnValue;	
	}

	public function writeOutSimpleData($items) 
	{				
		echo LINE_SEPARATOR.LINE_SEPARATOR;
		if (isset($items) && count($items) > 0)
		{
			foreach($items as $item)
			{
				foreach($item as $itemAttribute)
				{
					if ($itemAttribute->isVisible)
					{
						if ($itemAttribute->dataType !== DataType::DT_LIST 
							&& $itemAttribute->dataType !== DataType::DT_ITEM)
						{
							echo $itemAttribute->name." : ".$itemAttribute->convertedValue($itemAttribute->value).LINE_SEPARATOR;
						}
						else if ($itemAttribute->dataType === DataType::DT_ITEM)
						{
							$itemAttributeId = ItemAttribute::getItemAttribute($itemAttribute->value, "Id");
							echo $itemAttribute->name." : ".$itemAttribute->convertedValue($itemAttributeId->value).LINE_SEPARATOR;
						}
					}
				}
				Common::writeOutLetter("-", 50, LINE_SEPARATOR);
			}
		}	
		echo LINE_SEPARATOR;	
	}

	//$items : attributeItemArray array
	//$filterParam : FilterParam
	//firstMatch : when true then return the first match
	public function find($items, $filterParam, $firstMatch = false) 
	{
		$returnValue = array();
		$matchByOneParameter = false;
		if (count($filterParam->paramArray) === 1)
		{
			$matchByOneParameter = true;
		}

		if ($filterParam->logicalOperator === null && count($filterParam->paramArray) > 1)
		{
			$filterParam->logicalOperator = LogicalOperator::LO_AND;
		}

		foreach($items as $item)
		{
			$match = 0;
			foreach($filterParam->paramArray as $param)
			{			   
			$itemAttribute = ItemAttribute::getItemAttribute($item, $param->name);
			
			if ($itemAttribute !== null)
			{
				if ($itemAttribute->value === $param->value)
				{
					$match++;				   
				}			   
			}
			
			if ($matchByOneParameter)
			{
				break;
			}			   
			}
			
			if (!$matchByOneParameter)
			{
				if (($filterParam->logicalOperator === LogicalOperator::LO_OR && $match > 0) || $filterParam->logicalOperator === LogicalOperator::LO_AND && $match === count($filterParam->paramArray))
				{
					if (!$firstMatch)
					{
						$returnValue[] = $item;			
					}	
					else
					{
						$returnValue = $item;
						break;				
					}					
				}
			}
			else 
			{
				if ($match > 0)
				{
					if (!$firstMatch)
					{
						$returnValue[] = $item;			
					}	
					else
					{
						$returnValue = $item;
						break;				
					}		
				}
			}
		}	
		return $returnValue;
	}

}

?>
