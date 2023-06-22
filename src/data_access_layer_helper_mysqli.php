<?php
//Copyright (c) 2022 Tamas Hernadi
//Data Access Layer Helper for access MySQL Database using MySQLi extension
//Current version: 2.26

namespace Rasher\Data\MySQLi\DataManagement;
use Rasher\Data\DataManagement\{DataAccessLayerHelperBase};
use Rasher\Data\Type\{Param,ItemAttribute};
use Mysqli;

include_once __DIR__."/data_type_helper.php";
include_once __DIR__."/data_access_layer_helper_base.php";
include_once __DIR__."/common_static_helper.php";

class ConnectionData
{	
	public $server = null;
	public $user = null;
	public $psw = null;
	public $db = null;

	/**
	* ConnectionData constructor
	* 
	*
	*/
	public function __construct($server, $user, $psw, $db)
	{
		$this->server = $server;
		$this->user = $user;
		$this->psw = $psw;
		$this->db = $db;
	}
}

class StatementResult
{
	private $bindVarsArray = array();
	private $results = array();

	/**
	* StatementResult constructor
	* 
	*
	*/
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
	
	/**
	* BindingParam constructor
	* 
	*
	*/
	public function __construct($name, $type, $value)
	{
		parent::__construct($name, $value);
		$this->type = $type;
	}
}

class DataAccessLayerHelper extends DataAccessLayerHelperBase
{
	private $mysqli = null;	
	protected $connectionData = null;	

	/**
	* DataAccessHelper constructor
	* @param ConnectionData $connectionData The MySQLi connection data
	*
	*/
	public function __construct($connectionData)
	{
		$this->connectionData = $connectionData;
	}

	/**
	* open function which opens and inits a database connection
	* 
	*
	*/
	private function open()
	{
		$this->init();
	}

	/**
	* close function which close a database connection
	* 
	*
	*/
	private function close()
	{
		$this->mysqli->close();
	}

	/**
	* init function which opens a database connection with setting charset
	* 
	*
	*/
	private function init()
	{
		$this->mysqli = new mysqli($this->connectionData->server, $this->connectionData->user, $this->connectionData->psw, $this->connectionData->db);
		if ($this->mysqli->connect_errno)
		{
			throw new \Exception(LINE_SEPARATOR."Cannot connect into the database!".LINE_SEPARATOR.$this->mysqli->connect_error);
		}
		$this->mysqli->set_charset("utf8");
		mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);		
	}

	/**
	* query function
	* 
	*
	* @param string $query The sql query
	*
	* @return array @returnValue Return the result data set
	*/
	public function query($query)
	{
		$returnValue = array();
		try
		{
			$this->open();
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
			{
				throw new \Exception(LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$this->mysqli->error.LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR);
			}
			$this->close();
		}
		catch (\Throwable $e)
		{
			echo $e->getMessage();
			throw new \Exception(LINE_SEPARATOR."DAL Error!");
		}
		return $returnValue;
	}
	
	/**
	* getStmtBindingParams function
	* 
	*
	* @param BindingParam $params BindingParam object array
	*
	* @return array $returnValue The first array item is the binding type string for all parameter the others are the parameter values.
	* It is the input parameter to the mysqli bind_param function
	*/
	private function getStmtBindingParams($params) 
	{
		$bindingType = "";
		$returnValue = array();
		foreach ($params as $param)
		{
			$bindingType .= $param->type;
			$returnValue[] = &$param->value; //!important!
		}
		array_unshift($returnValue, $bindingType);
		return $returnValue;
	}

	/**
	* execute function
	* 
	*
	* @param string $query The prepared-statement sql query
	* @param array $params BindingParam object array
	* @param array $item ItemAttribute object array which is reserved and using by the extended repository class
	*
	* @return array @returnValue Return the result data set
	*/
	public function execute($query, $params, &$item = null)
	{
		$returnValue = array();
		try
		{
			$this->open();
			$bindingParams = array();
			$stmt = $this->mysqli->stmt_init();
			$stmt->prepare($query);
			if ($params !== null && count($params) > 0)
			{
				$bindingParams = $this->getStmtBindingParams($params);					
				call_user_func_array(array($stmt, "bind_param"), $bindingParams);
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
				throw new \Exception(LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$stmt->error.LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR."Parameters:".LINE_SEPARATOR.var_dump($bindingParams).LINE_SEPARATOR);
			}
			
			if (count($returnValue) === 0 && $item !== null)
			{
				$attributeItemId = ItemAttribute::getItemAttribute($item, "Id");
				if ($attributeItemId->value === null)
				{
					$attributeItemId->value = $this->mysqli->insert_id;
				}
			}
			$stmt->close();
			$this->close();
		}
		catch (\Throwable $e)
		{
			echo $e->getMessage();
			throw new \Exception(LINE_SEPARATOR."DAL Error!");
		}
		return $returnValue;
	}

	/**
	* executeScalar function
	* 
	*
	* @param string $query The prepared-statement sql query
	* @param array $params BindingParam object array
	*
	* @return mixed @returnValue Return the scalar result 
	*/
	public function executeScalar($query, $params)
	{
		$returnValue = null;
		try
		{
			$this->open();
			$bindingParams = array();
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
			{
				throw new \Exception(LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR. $stmt->error.LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR."Parameters:".LINE_SEPARATOR.var_dump($bindingParams).LINE_SEPARATOR);
			}
			$stmt->close();
			$this->close();
		}
		catch (\Throwable $e)
		{
			echo $e->getMessage();
			throw new \Exception(LINE_SEPARATOR."DAL Error!");
		}
		return $returnValue;
	}
}

?>
