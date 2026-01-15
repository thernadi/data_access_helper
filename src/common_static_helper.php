<?php
namespace Rasher\Common;

class Common
{
	public static function writeOutLetter(string $letter, int $length, ?string $lineSeparator = null)
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