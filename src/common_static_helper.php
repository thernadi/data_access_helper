<?php
namespace Rasher\Common;

class Common
{
	public static function writeOutLetter($letter, $length, $lineSeparator = null)
	{
		for ($i = 0; $i < $length; $i++) 
		{
			echo $letter;
		}
		if ($lineSeparator !== null)
		{
			echo $lineSeparator;
		}
	}
}

?>