<?php
//Copyright (c) 2022 Tamas Hernadi
//Db Repository Base
//Current version: 2.40

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


			$checkIsNull_IsNotNull = false;
			foreach($filters as $filter)
			{
				$operator = Operator::getOperatorForDB($filter->operator);
				if ($operator !== "IS NULL" &&  $operator !== "IS NOT NULL" )
				{
					$query .= $filter->name." $operator ? AND ";
				}
				else //"IS NULL" OR "IS NOT NULL"
				{
					$query .= $filter->name." $operator AND ";
					$checkIsNull_IsNotNull = true;
				}	
			}

			if ($checkIsNull_IsNotNull)
			{
				for($i = count($filters) - 1; $i >= 0 ; $i--)
				{
					$operator = Operator::getOperatorForDB($filters[$i]->operator);
					if ($operator === "IS NULL" || $operator === "IS NOT NULL")
					{
						unset($filters[$i]);
					}	
				}

				$filters_new = array();
				foreach($filters as $filter)
				{
					$filters_new[] = $filter; 
				}
				$filters = $filters_new;	
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

	public function find(array $items, FilterParam $filterParam, bool $firstMatch = false): array
	{
		$result = [];

		foreach ($items as $item) {
			if ($this->matchItem($item, $filterParam)) {
				$result[] = $item;
				if ($firstMatch) {
					break;
				}
			}
		}

		return $result;
	}

	/* ===================================================== */

	protected function matchItem($item, FilterParam $filterParam): bool
	{
		if (empty($filterParam->paramArray)) {
			return true;
		}

		// OR
		if ($filterParam->logicalOperator === LogicalOperator::LO_OR) {
			foreach ($filterParam->paramArray as $param) {
				if ($this->matchSingleParam($item, $param)) {
					return true;
				}
			}
			return false;
		}

		// AND (DEFAULT)
		return $this->matchGroupedParams($item, $filterParam->paramArray);
	}

	/* ===================================================== */
	/**
	 * AND logika: azonos collection-elemhez kell tartoznia
	 */
	protected function matchGroupedParams($item, array $params): bool
	{
		$groups = [];

		foreach ($params as $param) {
			$root = explode('.', $param->name)[0];
			$groups[$root][] = $param;
		}

		foreach ($groups as $root => $groupParams) {

			if (!isset($item[$root])) {
				return false;
			}

			$attr = $item[$root];

			// DT_LIST → egy elemnek BELÜL kell minden feltételnek teljesülnie
			if ($attr->dataType === DataType::DT_LIST) {

				$matched = false;

				foreach ($attr->value as $listItem) {

					$ok = true;
					foreach ($groupParams as $param) {
						$relativePath = substr($param->name, strlen($root) + 1);
						if (!$this->matchPath($listItem, $relativePath, $param)) {
							$ok = false;
							break;
						}
					}

					if ($ok) {
						$matched = true;
						break;
					}
				}

				if (!$matched) {
					return false;
				}

			} else {
				foreach ($groupParams as $param) {
					if (!$this->matchSingleParam($item, $param)) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/* ===================================================== */
	/**
	 * Rekurzív DT_ITEM / DT_LIST feldolgozás
	 */
	protected function matchPath($item, string $path, Param $param): bool
	{
		if ($path === '') {
			return false;
		}

		$parts = explode('.', $path);
		$current = $item;

		foreach ($parts as $index => $part) {

			if (!isset($current[$part])) {
				return false;
			}

			$attr = $current[$part];

			if ($attr->dataType === DataType::DT_ITEM) {
				$current = $attr->value;
				continue;
			}

			if ($attr->dataType === DataType::DT_LIST) {
				$remaining = implode('.', array_slice($parts, $index + 1));
				foreach ($attr->value as $listItem) {
					if ($this->matchPath($listItem, $remaining, $param)) {
						return true;
					}
				}
				return false;
			}

			// Egyszerű érték
			return $this->compareValues($attr->value, $param->value, $param->operator);
		}

		return false;
	}

	/* ===================================================== */

	protected function matchSingleParam($item, Param $param): bool
	{
		$values = ItemAttribute::getItemAttribute($item, $param->name);

		if (!is_array($values)) {
			$values = [$values];
		}

		foreach ($values as $val) {
			$value = $val->value ?? null;
			if ($this->compareValues($value, $param->value, $param->operator)) {
				return true;
			}
		}

		return false;
	}

	/* ===================================================== */

	protected function compareValues($left, $right, int $operator): bool
	{
		switch ($operator) {

			case Operator::OP_EQUAL:
				return $right === null ? $left === null : $left == $right;

			case Operator::OP_NOT_EQUAL:
				return $right === null ? $left !== null : $left != $right;

			case Operator::OP_IS_NULL:
				return $left === null;

			case Operator::OP_IS_NOT_NULL:
				return $left !== null;

			case Operator::OP_LIKE:
				if ($left === null) return false;
				return fnmatch(str_replace('%', '*', $right), $left);

			case Operator::OP_NOT_LIKE:
				if ($left === null) return true;
				return !fnmatch(str_replace('%', '*', $right), $left);

			case Operator::OP_LESS_THAN:
				return $left !== null && $left < $right;

			case Operator::OP_LESS_THAN_OR_EQUAL:
				return $left !== null && $left <= $right;

			case Operator::OP_GREATER_THAN:
				return $left !== null && $left > $right;

			case Operator::OP_GREATER_THAN_OR_EQUAL:
				return $left !== null && $left >= $right;
		}

		return false;
	}



}

abstract class TableType
{
	const TT_SIMPLE = 1; 
	const TT_HISTORICAL = 2;
}

trait SimpleTable
{
	protected function getTableType()
	{
		return TableType::TT_SIMPLE;
	}

	protected function getTableBaseItemAttributes($itemAttributes)
	{
		$simpleTableItemAttributesBase = array_merge(array(
			ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //req, pk, autoinc
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0)), $itemAttributes);
		return $simpleTableItemAttributesBase;
	}
}

//TODO LATER:
trait HistoricalTable
{
	protected function getTableType()
	{
		return TableType::TT_HISTORICAL;
	}

	protected function getTableBaseItemAttributes($itemAttributes)
	{
		$historicalTableItemAttributesBase = array_merge(array(
			ItemAttribute::with_Name_Caption_DataType("TechnicalId", "Technical Id", DataType::DT_INT), //req, pk, autoinc
			ItemAttribute::with_Name_Caption_DataType("Id", "Id", DataType::DT_INT), //When it is null in first time the autoinc "Technical Id" will be set into "Id" by DB trigger
			ItemAttribute::with_Name_Caption_DataType_DefaultValue("IsDeleted", "Is deleted", DataType::DT_INT, 0),	
			ItemAttribute::with_Name_Caption_DataType_DataFormat("ValidFrom", "Valid from", DataType::DT_DATETIME, "Y-m-d H:i:s"), //req	
			ItemAttribute::with_Name_Caption_DataType_DataFormat("ValidTo", "Valid to", DataType::DT_DATETIME, "Y-m-d H:i:s")), $itemAttributes);	
		return $historicalTableItemAttributesBase;
	}
}
?>
