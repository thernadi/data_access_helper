<?php
//Copyright (c) 2022 Tamas Hernadi
//Data Access Helper For MySQL Database 
//Dependency: 
//- data_type_helper.php 
//- common_static_helper.php

//Current version: 2.0
//Database table rules: all table contains the fields belows in database.
//Table level existed fields:
//Id (int, required, primary key)
//IsDeleted (datetime, default 0)

//Extends class from DbRepository class for using

include_once __DIR__."/data_type_helper.php";
include_once __DIR__."/common_static_helper.php";

class ConnectionData
{
	public $server = null;
	public $user = null;
	public $psw = null;
	public $db = null;

	public function __construct($server, $user, $psw, $db)
	{
		$this->server = $server;
		$this->user = $user;
		$this->psw = $psw;
		$this->db = $db;
	}
}

//MySQL ConnectionData single instance
//Parameters: serverName, userName, password, databaseName
//Fill out before using
$connectionData = new ConnectionData("serverName", "userName", "password", "databaseName");

class StatementResult
{
	private $bindVarsArray = array();
	private $results = array();

	public function __construct(&$stmt)
	{
		$meta = $stmt->result_metadata();
		while ($columnName = $meta->fetch_field())
		{
			$this->bindVarsArray[] = &$this->results[$columnName->name];
		}
		call_user_func_array(array($stmt, 'bind_result'), $this->bindVarsArray);
		$meta->close();
	}

	public function getArray()
	{
		return $this->results;
	}

	public function get($columnName)
	{
		return $this->results[$columnName];
	}
}

class BindingParam extends Param
{
	public $type = null; //i = integer, s = string, d = double, b = blob, (s = datetime) 
	public function __construct($name, $type, $value)
	{
		parent::__construct($name, $value);
		$this->type = $type;
	}
}

class DataAccessHelper
{
	private $mysqli = null;	
	protected $connectionData = null;	
	public function __construct($connectionData)
	{
		$this->connectionData = $connectionData;
	}

	private function open()
	{
		$this->mysqli = new mysqli($this->connectionData->server, $this->connectionData->user, $this->connectionData->psw, $this->connectionData->db);
		if ($this->mysqli->connect_errno)
		{
			echo "\n\rCannot connect into the database!\n\r" . $this->mysqli->connect_error;
			exit();
		}
	}

	private function close()
	{
		$this->mysqli->close();
	}

	private function init()
	{
		$this->open();
		$this->mysqli->set_charset("utf8");		
	}

	//Conventional query
	public function query($query)
	{
		$this->init();
		$returnValue = array();
		if ($result = $this->mysqli->query($query))
		{
			if ($result->num_rows > 0)
			{
				while ($row = $result->fetch_assoc())
				{
					$returnValue[] = $row;
				}
				$result->close();
			}
		}
		else
			echo "\n\rError in the query!\n\r" . $this->mysqli->error. "\n\rSQL:\n\r" . $query. "\n\r";
		$this->close();
		return $returnValue;
	}
	
    //$params: BindingParam array
	private function getStmtBindingParams($params) 
	{
		$bindingType = "";
		$bindingParams = array();
		foreach ($params as $param)
		{
			$bindingType .= $param->type;
			$bindingParams[] = &$param->value; //!important!
		}
		array_unshift($bindingParams, $bindingType);
		return $bindingParams;
	}

	//prepeared-statement query
	public function execute($query, $params, $isLoadedId = false)
	{
		$this->init();
		$returnValue = array();
		$bindingParams = array();
		$stmt = $this->mysqli->stmt_init();
		$stmt->prepare($query);
		if ($params !== null && count($params) > 0)
		{
			$bindingParams = $this->getStmtBindingParams($params);					
			call_user_func_array(array($stmt,"bind_param"), $bindingParams);
		}
		if ($stmt->execute())
		{
			$stmt->store_result();
			if ($stmt->num_rows > 0)
			{
				$sr = new StatementResult($stmt);
				while($stmt->fetch())
				{
					$row = array();
					foreach ($sr->getArray() as $key => $value)
					{
						$row[$key] = $value;
					}
					$returnValue[] = $row;
				}
			}
		}
		else
		{
			echo "\n\rError in the query!\n\r" . $stmt->error . "\n\rQuery:\n\r" . $query. "\n\rParameters:\n\r".var_dump($bindingParams)."\n\r";
		}
		
		if (in_array("Id", $returnValue) && $isLoadedId)
		{
			$returnValue["Id"] = $this->mysqli->insert_id;
		}
		
		$stmt->close();
		$this->close();
		return $returnValue;
	}

	//prepared-statement query
	public function executeScalar($query, $params)
	{
		$this->init();
		$bindingParams = array();
		$returnValue = null;
		$stmt = $this->mysqli->stmt_init();
		$stmt->prepare($query);
		if ($params != null && count($params) > 0)
		{
			$bindingParams = $this->getStmtBindingParams($params);
			call_user_func_array(array($stmt,"bind_param"), $bindingParams);
		}

		if ($stmt->execute())
		{
			$stmt->store_result();
			$stmt->bind_result($returnValue);
			$stmt->fetch();
		}
		else
			echo "\n\rError in the query!\n\r" . $stmt->error . "\n\rQuery:\n\r" . $query. "\n\rParameters:\n\r".var_dump($bindingParams)."\n\r";
		$stmt->close();
		$this->close();
		return $returnValue;
	}
	
    //Check wether an array contains a value (for the conventional query result)
	//$name: column name
	//$value: search value
	//$rows: array($row-array)
	public function isValueInRows($name, $value, $rows, &$outputRow) 
	{
		$outputRow = null;
		$returnValue = false;
		foreach ($rows as $row)
		{
			foreach($row as $key => $val) 
			{
				if ($key === $name)
				{
					$returnValue = true;
					$outputRow = $row;
					break;
				}
			}	
		}
		return  $returnValue;
	}
}


class DbRepository extends DataAccessHelper
{
	protected $tbl = null; //string, only for statement queries
	public $itemAttributes = null; //array, only ItemAttribute array without values for statement queries
	public function __construct($connectionData, $tbl, $itemAttributes)
	{
		parent::__construct($connectionData);
		$this->tbl = $tbl;
		$this->itemAttributes = $itemAttributes;
	}

	public function getNewItemInstance($itemAttributes = null)
	{
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
			$itemAttributesCopy = ItemAttribute::getSimpleCopiedItemAttributeArray($itemAttributes);
			
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
						$itemAttribute->value = $this->getNewItemInstance($itemAttribute->referenceDescriptor->targetItemAttibutes);
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
			
			$bindingType = "";		
			switch ($value->dataType) 
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
			
			if ($value->dataType !== DataType::DT_ITEM)
			{
				$returnValue[] = new BindingParam($value->name, $bindingType, $value->value);
			}
			else //DT_ITEM
			{
				$itemAttributeId = ItemAttribute::getItemAttribute($value->value, "Id");
				$returnValue[] = new BindingParam($value->name, $bindingType, $itemAttributeId->value);
			}
		}	
		return $returnValue;
	}

	//This function can load all items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	public function loadAll()
	{
		$query = "SELECT * FROM " . $this->tbl . " WHERE IsDeleted = ? ORDER BY Id asc";
		$params = array();
		$params[] = new BindingParam("IsDeleted", "i", 0);
		$returnValue = $this->execute($query, $params);
		return $this->convertToItemAttributeArrayArray($returnValue);
	}

	//This function can load all items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	public function loadByFilter($filters, $fields, $orderFields, $orderDirection = null) 
	//$filters: $BindingParam object array 
	//$fields = neeeded fields array  
	//$orderFields: array
	//$orderDirection: asc/desc/null
	{
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
		$returnValue = $this->execute($query, $filters);
		return $this->convertToItemAttributeArrayArray($returnValue);
	}

	//This function can load filtered items with the type of "DT_ITEM" item together (there it is filled out its "Id" only) but the "DT_LIST" are not loaded. 
	//$filters: $BindingParam object array
	public function loadByFilter2($filters)
	{	
		return $this->loadByFilter($filters, array(), array("Id"), "asc");
	}

	//This function can load one item by "Id" with the type of "DT_ITEM" item together and the "DT_LIST" are loaded. 
	public function loadById($id, $tbl = null, $idAttributeName = null,  $itemAttributes = null)
	{
		if ($tbl === null)
		{
			$tbl = $this->tbl;
		}

		if ($idAttributeName === null)
		{
			$idAttributeName = "Id";
		}

		$returnValue = null;
		$query = "SELECT * FROM " . $tbl . " WHERE ".$idAttributeName." = ? AND IsDeleted = ?";
		$params = array();
		$params[] = new BindingParam($idAttributeName, "i", $id);
		$params[] = new BindingParam("IsDeleted", "i", 0);
		$returnValue = $this->execute($query, $params);
		$returnValue = $this->convertToItemAttributeArrayArray($returnValue, $itemAttributes);

		foreach($returnValue as $outterKey => $outterValue)
		{
			foreach ($outterValue as $key => $value)
			{
				if ($value->referenceDescriptor !== null && $value->dataType == DataType::DT_ITEM)
				{
					$itemAttributeId =  ItemAttribute::getItemAttribute($value->value, $value->referenceDescriptor->targetMappingAttributeName);
					$value->value = $this->loadById($itemAttributeId->value, $value->referenceDescriptor->targetTableName, $value->referenceDescriptor->targetMappingAttributeName, $value->value)[0];
				}
				else if ($value->referenceDescriptor !== null && $value->dataType == DataType::DT_LIST)
				{
					$itemAttributeId = ItemAttribute::getItemAttribute($returnValue[0], $value->referenceDescriptor->sourceMappingAttributeName);
					$value->value = $this->loadById($itemAttributeId->value, $value->referenceDescriptor->targetTableName, $value->referenceDescriptor->targetMappingAttributeName, $value->referenceDescriptor->targetItemAttibutes);
				}
			}
		}

		return $returnValue;
	}

	//TODO: list type and item type ability 
	//$item: ItemAttribute array	
	public function save($item) 
	{
		$params = $this->getParamsByItem($item);		
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
			$params[] = new BindingParam("Id", "i", $itemAttributeId->value);
			$query = "UPDATE ". $this->tbl ." SET ".$q_fnames." WHERE Id = ?";
			$this->execute($query, $params);
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
			$query = "INSERT INTO ". $this->tbl ." (".$q_fnames.") VALUES (".$q_fparams.")";
			$this->execute($query, $params, true);
		}
	}
	
	public function deleteAll()
	{
		$query = "DELETE FROM " . $this->tbl;
		$params = array();
		$this->execute($query, $params);
	}
	

	//TODO: list type and item type ability
	public function deleteById($id)
	{
		$query = "DELETE FROM " . $this->tbl." WHERE Id = ?";
		$params = array();
		$params[] = new BindingParam("Id", "i", $id);
		$this->execute($query, $params);
	}

	public function getMaxField($fieldName)
	{
		$query = "SELECT DISTINCT MAX(".$fieldName.") FROM ". $this->tbl . " WHERE IsDeleted = ?";
	    $params[] = new BindingParam("IsDeleted", "i", 0);
		return $this->executeScalar($query, $params);
	}

	public function getMinField($fieldName)
	{
		$query = "SELECT DISTINCT MIN(".$fieldName.") FROM ". $this->tbl . " WHERE IsDeleted = ?";
	    $params[] = new BindingParam("IsDeleted", "i", 0);		
		return $this->executeScalar($query, $params);
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

	public function writeOutSimpleData($items) 
	{					
		echo "\n\r\n\r";
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
							echo $itemAttribute->name." : ".$itemAttribute->convertedValue($itemAttribute->value)."\n\r";
						}
						else if ($itemAttribute->dataType === DataType::DT_ITEM)
						{
							$itemAttributeId = ItemAttribute::getItemAttribute($itemAttribute->value, "Id");
							echo $itemAttribute->name." : ".$itemAttribute->convertedValue($itemAttributeId->value)."\n\r";
						}
					}
				}
				Common::writeOutLetter("-", 50);
			}
		}	
		echo "\n\r";		
	}

	//$items : attributeItemArray array
	//$filterParam : FilterParam
	//firstMatch : when true then return the first match
	public function find($items, $filterParam, $firstMatch = false) 
	{
		$matchByOneParameter = false;
		if (count($filterParam->paramArray) === 1)
		{
			$matchByOneParameter = true;
		}

		if ($filterParam->logicalOperator === null && count($filterParam->paramArray) > 1)
		{
			$filterParam->logicalOperator = LogicalOperator::LO_AND;
		}

		$returnValue = array();

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
