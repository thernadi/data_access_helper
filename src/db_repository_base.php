<?php
//Copyright (c) 2022 Tamas Hernadi
//Db Repository Base
//Current version: 2.35

namespace Rasher\Data\DataManagement;
use Rasher\Data\Type\{DataType,LogicalOperator,Operator,Param,FilterParam,ReferenceDescriptor,ItemAttribute,CachedItem};
use Rasher\Common\{Common};

include_once __DIR__."/data_type_helper.php";
include_once __DIR__."/common_static_helper.php";

//ABSTRACT
trait DbRepositoryBase
{
	public $itemAttributes = null; //ItemAttribute object array without values, it is only the data structure
	protected $tbl = null;
	protected $useItemCache = null;
	protected $cacheIdProperty = null;
	protected  $itemCache = null;

	public $depth = 1; //Override a larger value in derived DBRepository if structure contains more "DT_LIST.DT_LIST" levels.
	//For example #1: "Collection1.Collection2.Item1.Collection3.Item2" -> depth = 3  
	//For example #2: "Collection1.Item1.Collection2.Item2" -> depth = 2  

	public function __construct($connectionData, $tbl, $itemAttributes, $useItemCache = false, $cacheIdProperty = "Id")
	{
		parent::__construct($connectionData);
		$this->tbl = $tbl;
		$this->itemAttributes = $itemAttributes;
		$this->useItemCache = $useItemCache;
		$this->cacheIdProperty = $cacheIdProperty;
		$this->buildCache(true);		
	}

	public function __destruct() 
	{
		if(isset($this->itemCache))
		{
			$_SESSION["DBRepositoryCache_".$this->tbl] = json_encode($this->itemCache);
		}
	}

	//Load from DB or session if exists and needed
	public function buildCache($reloadFromDB = false)
	{
		if ($this->useItemCache)
		{
			$this->itemCache = array();
			if (!$reloadFromDB && isset($_SESSION["DBRepositoryCache_".$this->tbl]))
			{	
				$this->itemCache = json_decode($_SESSION["DBRepositoryCache_".$this->tbl]); 
			}
			else
			{
				foreach ($this->loadAll() as $item) 
				{
					$this->addItemToCache($item);
				}
			}
		}
	}

	//Save to DB
	public function saveCache()
	{
		if(isset($this->itemCache) && count($this->itemCache) > 0)
		{
			foreach ($this->itemCache as $cachedItem) 
			{
				if ($this->hasChanges($cachedItem->item))
				{
					$id = $cachedItem->item["Id"]->value;
					if ($id < 0)
					{
						continue;	
					}
					$this->saveWithTransaction($cachedItem->item);
				}
			}

			$i = -1;
			while ($i < 0)
			{
				if (!array_key_exists($i, $this->itemCache))
				{
					break;
				}
				$this->saveWithTransaction($this->itemCache[$i]->item);
				$this->addItemToCache($this->itemCache[$i]->item);
				unset($this->itemCache[$i]);

				$i--;
			}

		}
	}

	public function addItemToCache($item)
	{
		if(isset($this->itemCache))
		{
			if (isset($item))
			{
				$cachedItem = new CachedItem($item);
				$id = $cachedItem->item[$this->cacheIdProperty]->value;
				if ($this->cacheIdProperty === "Id")
				{
					if ($item["Id"]->value === null)
					{
						$item["Id"]->value = -1;
						if (count($this->itemCache) > 0)
						{
							$itemIds = array();
							foreach ($this->itemCache as $cachedItemWithId) 
							{	
								$itemIds[] = $cachedItemWithId->item["Id"]->value; 
							}
							sort($itemIds);
							if ($itemIds[0] < 0)
							{
								$item["Id"]->value = $itemIds[0] - 1;
							}
						}
						$id = $item["Id"]->value;
					}
				}
			}
			
			if($id < 0)
			{
				$newItemCache = array($id => $cachedItem);
				foreach($this->itemCache as $key => $value)
				{
					$newItemCache[$key] = $value;
				}
				$this->itemCache = $newItemCache;
			}
			else if (!array_key_exists($id, $this->itemCache))
			{
				$this->itemCache[$id] = $cachedItem;
			}
		}
	}

	public function getAllItemsFromCache($withFullyLoad = false)
	{
		$returnValue = array();
		foreach($this->itemCache as $cachedItem)
		{
			if (!$withFullyLoad)
			{
				$returnValue[] = $cachedItem->item;
			}
			else
			{
				$returnValue[] = $this->getItemFromCache($cachedItem->item[$this->cacheIdProperty]->value);
			}
		}
		return $returnValue;
	}

	public function getItemFromCache($id)
	{
		$returnValue = null;
		if(isset($this->itemCache) && count($this->itemCache) > 0)
		{
			if(array_key_exists($id, $this->itemCache))
			{
				if (!$this->itemCache[$id]->isFullyLoaded 
				&& $this->itemCache[$id]->item["Id"]->value > 0)
				{
					$returnValue = $this->loadByIdWithTransaction($this->itemCache[$id]->item["Id"]->value)[0];
					if(isset($returnValue) && count($returnValue) > 0)
					{		
						$this->itemCache[$id]->isFullyLoaded = true;		
					}
				}
				else
				{
					$returnValue = $this->itemCache[$id]->item;
				}
			}
			else
			{
				if ($this->cacheIdProperty === "Id")
				{
					$returnValue = $this->loadByIdWithTransaction($id)[0];
					if(isset($returnValue) && count($returnValue) > 0)
					{		
						$this->itemCache[$id]->isFullyLoaded = true;		
					}
				}
				else
				{
					$filters = array();
					$filters[] = new Param($this->cacheIdProperty, $id);		
					$item = $this->loadByFilter2($filters);
					if(isset($item) && count($item) > 0)
					{
						$hasComplexAttribute = false;
						foreach($this->itemAttributes as $itemAttribute)
						{
							if ($itemAttribute->dataType === DataType::DT_ITEM || $itemAttribute->dataType === DataType::DT_LIST)
							{
								$hasComplexAttribute = true;
								break;
							}
						}

						if ($hasComplexAttribute)
						{
							$returnValue = $this->loadByIdWithTransaction($item[0]["Id"]->value)[0];
							if(isset($returnValue) && count($returnValue) > 0)
							{		
								$this->itemCache[$id]->isFullyLoaded = true;		
							}						
						}
						else
						{
							$this->itemCache[$id]->isFullyLoaded = true;		
						}
					}
				}		
			}	
			
			if(isset($returnValue) && count($returnValue) > 0)
			{		
				$this->itemCache[$id]->item = $returnValue;	
			}

		}
		return $returnValue;
	}

	public function hasChanges($item)
	{
		$returnValue = false;

		if ($item["Id"]->value === null || $item["Id"]->value < 0)
		{
			$returnValue = true;
		}
		else 
		{
			foreach($item as $key => $value) 
			{
				if ($value->dataType !== DataType::DT_LIST)
				{
					if ($value->value !== $value->originalValue)
					{
						$returnValue = true;
						break;
					}
				} 
				else if ($value->referenceDescriptor !== null) //DT_LIST
				{
					foreach($value->value as $collectionItemKey => $collectionItemValue)
					{
						if ($this->hasChanges($collectionItemValue))
						{
							$returnValue = true;
							break;
						}
					}
				}	
			}
		}
		return $returnValue;
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
				$val->value = $val->convertToBaseType($val->defaultValue);
			}
		}		
		return $returnValue;		
	}

	//$rowArray: array from a query's return output
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
						$itemAttribute->value = $itemAttribute->convertToBaseType($val);

						if ($itemAttribute->originalValue === null)
						{
							$itemAttribute->originalValue = $itemAttribute->value;
						}

					}
					else if ($itemAttribute->referenceDescriptor !== null 
					&& $itemAttribute->dataType === DataType::DT_ITEM)
					{
						$itemAttribute->value = $this->getNewItemInstance($itemAttribute->referenceDescriptor->targetItemAttributes);
						if ($itemAttribute->value !== null)
						{
							$itemAttributeId = ItemAttribute::getItemAttribute($itemAttribute->value, $itemAttribute->referenceDescriptor->targetMappingAttributeName);
							$itemAttributeId->value = $itemAttributeId->convertToBaseType($val);

							if ($itemAttributeId->originalValue === null)
							{
								$itemAttributeId->originalValue = $itemAttributeId->value;
							}
						}

						if ($itemAttribute->originalValue === null)
						{
							$itemAttribute->originalValue = $itemAttribute->value;
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
		if  ($fields !== null && count($fields) > 0)
		{
			foreach ($fields as $field)
				$query .= $field.", ";
			$query = substr($query, 0, strlen($query) - 2);
		}
		else
			$query .="*";

		$query .=" FROM " . $this->tbl;
		if ($filters !== null && count($filters) > 0)
		{
			$query .=" WHERE ";
			foreach ($filters as $filter)
			{
			 	$operator = Operator::getOperatorForDB($filter->operator);
				if ($operator !== "IS NULL")
				{
					$query .= $filter->name." $operator ? AND ";
				}
				else
				{
					$query .= $filter->name." $operator AND ";
				}	
			}
			$query = substr($query, 0, strlen($query) - 4);
		}
		else
			$filters = array();
		if  ($orderFields !== null && count($orderFields) > 0)
		{
			$query .= " ORDER BY ";
			foreach ($orderFields as $orderField)
				$query .= $orderField.", ";
			$query = substr($query, 0, strlen($query) - 2);
			
			$orderDirection = strtoupper($orderDirection);
			if ($orderDirection !== null && ($orderDirection === "ASC" || $orderDirection === "DESC"))
				$query .=" ".$orderDirection;
			else
				$query .=" ASC";
		}
	
		$returnValue = $this->execute($query, $this->convertParamArrayToDBSpecificParamArray($filters));
		return $this->convertToItemAttributeArrayArray($returnValue);
	}

	//This function can load filtered items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	//$filters: Param object array
	public function loadByFilter2($filters)
	{	
		return $this->loadByFilter($filters, array(), array("Id"), "ASC");
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
		if ($this->hasChanges($item))
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

	public function saveWithTransaction($item) 
	{
		try
		{
			$this->beginTransaction();
			$this->save($item);
			$this->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->rollbackTransaction();
			throw $e;
		}
	}

	public function loadByIdWithTransaction($id)
	{
		$returnValue = null;
		try
		{
			$this->beginTransaction();
			$returnValue = $this->loadById($id);
			$this->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->rollbackTransaction();
			throw $e;
		}		
		return $returnValue;
	}

	public function deleteWithTransaction($item)
	{
		try
		{
			$this->beginTransaction();
			$this->delete($item);
			$this->commitTransaction();
		}
		catch (\Throwable $e)
		{
			$this->rollbackTransaction();
			throw $e;
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
							echo $itemAttribute->name." : ".$itemAttribute->value.LINE_SEPARATOR;
						}
						else if ($itemAttribute->dataType === DataType::DT_ITEM)
						{
							$itemAttributeId = ItemAttribute::getItemAttribute($itemAttribute->value, "Id");
							echo $itemAttribute->name." : ".$itemAttributeId->value.LINE_SEPARATOR;
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

		$itemsForCheck = ItemAttribute::getSimpleCopiedItemAttributeArray($items);
		for($i = 0; $i < count($itemsForCheck); $i++)
		{
			$this->setParent($itemsForCheck[$i]);
		}

		for($i = 0; $i < count($items); $i++)
		{					
			$match = 0;
			foreach($filterParam->paramArray as $param)
			{		
				$itemAttributes = ItemAttribute::getItemAttribute($itemsForCheck[$i], $param->name);

				if (!is_array($itemAttributes))
				{
					$itemAttributes = array($itemAttributes);
				}

				$submatch = 0;   
				foreach($itemAttributes as $itemAttribute)
				{	
					if ($this->compareValues($itemAttribute->value, $param->value, $param->operator))										
					{
						$submatch++;		
						$this->applyFiltersForListAttribute($itemAttribute, $itemAttribute);		
					}
				}										
				
				if ($submatch > 0)
				{
					$match++;
				}

				if ($matchByOneParameter)
				{
					break;
				}
				
			}

			
			if (!$matchByOneParameter)
			{
				if (($filterParam->logicalOperator === LogicalOperator::LO_OR && $match > 0) 
				|| ($filterParam->logicalOperator === LogicalOperator::LO_AND && $match === count($filterParam->paramArray)))
				{
					$returnValue[] = $items[$i];				
					if ($firstMatch)
					{
						break;
					}
				}
			}
			else 
			{
				if ($match > 0)
				{
					$returnValue[] = $items[$i];		
					if ($firstMatch)
					{
						break;
					}	
				}
			}				
		}	
		return $returnValue;
	}

	private function compareValues($value1, $value2, $operator)
	{				
		$pattern = str_replace('%', '.*', preg_quote($value2));
		$returnValue = ($operator === Operator::OP_EQUAL && $value1 === $value2)
			|| ($operator === Operator::OP_NOT_EQUAL && $value1 !== $value2)
			|| ($operator === Operator::OP_LESS_THAN && $value1 !== null && $value1 < $value2)
			|| ($operator === Operator::OP_LESS_THAN_OR_EQUAL && $value1 !== null && $value1  <= $value2)
			|| ($operator === Operator::OP_GREATER_THAN && $value1 !== null && $value1 > $value2)
			|| ($operator === Operator::OP_GREATER_THAN_OR_EQUAL && $value1 !== null && $value1 >= $value2)
			|| ($operator === Operator::OP_LIKE && preg_match("/^$pattern$/", $value1))
			|| ($operator === Operator::OP_NOT_LIKE && !preg_match("/^$pattern$/", $value1));
	
			return $returnValue;
	}

	private function setParent($item, $parent = null)
	{
		if (is_array($item) 
		&& count($item) > 0)	
		{
			for ($i = 0; $i < count($item); $i++)
			{
				$item[array_keys($item)[$i]]->parent = $parent;

				if ($item[array_keys($item)[$i]]->dataType === DataType::DT_LIST)
				{
					for($j = 0; $j < count($item[array_keys($item)[$i]]->value); $j++)
					{
						$this->setParent($item[array_keys($item)[$i]]->value[$j], $item[array_keys($item)[$i]]);				
					}
				}
				else if ($item[array_keys($item)[$i]]->dataType === DataType::DT_ITEM)
				{
					$this->setParent($item[array_keys($item)[$i]]->value, $item[array_keys($item)[$i]]);
				}
			}
		}
	}

	//We need the the most nearest DT_LIST attribute by the set depth.
	private function applyFiltersForListAttribute($attribute, $childAttribute, &$depth = 0) 
	{		
		$returnValue = true; 
		if ($attribute !== null && $attribute->dataType === DataType::DT_LIST)
		{
			for($i = count($attribute->value) - 1; $i >= 0; $i--)
			{
				if ($childAttribute->dataType === DataType::DT_ITEM)
				{
					if ($attribute->value[$i][$childAttribute->name]->value["Id"]->value !== $childAttribute->value["Id"]->value)
					{
						unset($attribute->value[$i]);
					}
				}
				else
				{
					if ($attribute->value[$i][$childAttribute->name]->value !== $childAttribute->value)
					{
						unset($attribute->value[$i]);
					}
				}
			}
			
			$reIndexedAttributeArray = array();
			foreach($attribute->value as $value)
			{
				$reIndexedAttributeArray[] = $value; 
			}
			$attribute->value = $reIndexedAttributeArray;	
			$depth++;
		}
		else if ($attribute === null) 
		{
			return false;
		}

		while($returnValue && $attribute !== null && $depth !== $this->depth)
		{		
			$returnValue = $this->applyFiltersForListAttribute($attribute->parent, $attribute, $depth);			
		}
		
	}

}

?>
