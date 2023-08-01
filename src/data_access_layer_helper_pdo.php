<?php
//Copyright (c) 2022 Tamas Hernadi
//Data Access Layer Helper for access Databases using PDO extension
//Current version: 2.33

namespace Rasher\Data\PDO\DataManagement;
use Rasher\Data\DataManagement\{DataAccessLayerHelperBase,BindingParam};
use Rasher\Data\Type\{Param,ItemAttribute};
use PDO;

include_once __DIR__."/data_type_helper.php";
include_once __DIR__."/data_access_layer_helper_base.php";
include_once __DIR__."/common_static_helper.php";


class ConnectionData
{	
	public $dsn = null;
	public $user = null;
	public $psw = null;
	public $options = null;

	/**
	* ConnectionData constructor
	* 
	*
	*/
	public function __construct($dsn, $user = null, $psw = null, $options = null)
	{
		$this->dsn = $dsn;
		$this->user = $user;
		$this->psw = $psw;
		$this->options = $options;
	}
}

class DataAccessLayerHelper extends DataAccessLayerHelperBase
{
	private $pdo = null;	
	protected $connectionData = null;
	protected $isInTransaction = false;

	/**
	* DataAccessHelper constructor
	* @param ConnectionData $connectionData The PDO connection data
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
		if (!$this->isInTransaction)
		{
			$this->init();
		}
	}

	/**
	* close function which close a database connection
	* 
	*
	*/

	private function close()
	{
		if (!$this->isInTransaction)
		{
			$this->pdo = null;
		}
	}

	/**
	* init function which opens a database connection
	* 
	*
	*/
	private function init()
	{
		$this->pdo = new PDO($this->connectionData->dsn, $this->connectionData->user, $this->connectionData->psw, $this->connectionData->options);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
			$result = $this->pdo->query($this->transformQueryToDBSpecific($query), PDO::FETCH_ASSOC);
			if (str_starts_with(trim(strtoupper($query)), "SELECT"))
			{
				foreach($result as $row)
				{
					$returnValue[] = $row;
				}
			}
			$this->close();
		}
		catch (\Throwable $e)
		{
			echo LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$e->getMessage().LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR;		
			throw new \Exception(LINE_SEPARATOR."DAL Error!");
		}
		return $returnValue;
	}
	
	/**
	* transformQueryToDBSpecific function
	* 
	*
	*/
	public function transformQueryToDBSpecific($query)
	{	
		$returnValue = strtoupper($query);
		$driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driverName === "sqlsrv")//MSSQL SERVER
		{
			$reserved = array("USER" => "[USER]");
			foreach($reserved as $key => $val)
			{
				$returnValue = preg_replace("/\b".$key."\b/", $val, $returnValue);
			}
		}
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
			$stmt = $this->pdo->prepare($this->transformQueryToDBSpecific($query));
			for($i = 1; $i <= count($params); $i++)
			{
				$stmt->bindValue($i, $params[$i-1]->value, $params[$i-1]->type);
			}
			$stmt->execute();		
			if (str_starts_with(trim(strtoupper($query)), "SELECT"))
			{
				foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
				{
					$returnValue[] = $row;
				}
			}
			
			if (count($returnValue) === 0 && $item !== null)
			{
				$attributeItemId = ItemAttribute::getItemAttribute($item, "Id");
				if ($attributeItemId->value === null)
				{
					$attributeItemId->value = $this->pdo->lastInsertId();
				}
			}
			$stmt = null;
			$this->close();
		}
		catch (\Throwable $e)
		{
			echo LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$e->getMessage().LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR."Parameters:".LINE_SEPARATOR.var_dump($params).LINE_SEPARATOR;
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
			$stmt = $this->pdo->prepare($this->transformQueryToDBSpecific($query));
			for($i = 1; $i <= count($params); $i++)
			{
				$stmt->bindValue($i, $params[$i-1]->value, $params[$i-1]->type);
			}
			$stmt->execute();
			$returnValue = $stmt->fetchColumn();			
			$stmt = null;
			$this->close();
		}
		catch (\Throwable $e)
		{
			echo LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$e->getMessage().LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR."Parameters:".LINE_SEPARATOR.var_dump($params).LINE_SEPARATOR;
			throw new \Exception(LINE_SEPARATOR."DAL Error!");
		}
		return $returnValue;
	}

	public function beginTransaction()
	{
		$this->open();
		$this->pdo->beginTransaction();
		$this->isInTransaction = true;
	}

	public function commitTransaction()
	{
		$this->pdo->commit();
		$this->isInTransaction = false;
		$this->close();
	}

	public function rollbackTransaction()
	{
		$this->pdo->rollBack();
		$this->isInTransaction = false;
		$this->close();
	}
	
}

?>
