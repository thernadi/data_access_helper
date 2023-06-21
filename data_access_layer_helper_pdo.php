<?php
//Copyright (c) 2022 Tamas Hernadi
//Data Access Layer Helper for access Databases using PDO extension
//Current version: 2.26

namespace Rasher\Data\PDO\DataManagement;
use Rasher\Data\DataManagement\{DataAccessLayerHelperBase};
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

class BindingParam extends Param
{
	public $type = null;
	
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
	private $pdo = null;	
	protected $connectionData = null;	

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
		$this->init();
	}

	/**
	* close function which close a database connection
	* 
	*
	*/

	private function close()
	{
		$this->pdo = null;
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
			$this->init();
			$result = $this->pdo->query($query, PDO::FETCH_ASSOC);
			foreach($result as $row)
			{
				$returnValue[] = $row;
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
			$stmt = $this->pdo->prepare($query);
			for($i = 1; $i <= count($params); $i++)
			{
				$stmt->bindValue($i, $params[$i-1]->value, $params[$i-1]->type);
			}
			$stmt->execute();
			$returnValue = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
			echo LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$e->getMessage().LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR;
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
			$stmt = $this->pdo->prepare($query);
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
			echo LINE_SEPARATOR."Error in the query!".LINE_SEPARATOR.$e->getMessage().LINE_SEPARATOR."Query:".LINE_SEPARATOR.$query.LINE_SEPARATOR;
			throw new \Exception(LINE_SEPARATOR."DAL error!");
		}
		return $returnValue;
	}
	
}

?>
