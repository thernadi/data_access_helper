<?php
//Copyright (c) 2022 Tamas Hernadi
//Data Access Layer Helper Base for access Databases
//Current version: 2.35

namespace Rasher\Data\DataManagement;
use Rasher\Data\Type\{Param};

!defined("LINE_SEPARATOR") && define("LINE_SEPARATOR", "\n\r"); //<br/>

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

abstract class DataAccessLayerHelperBase
{

	/**
	* isValueInRows function check wether an array contains a value
	* 
	*
	* @param string $name The column name
	* @param string $value The search value
	* @param array $rows The result data set array (array(array()))
	* @param array $outputRow The found data result array
	*
	* @return boolean @returnValue Return true if found the row data and false if not
	*/
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

?>
