<?php

class Common
{
	public static function writeOutLetter($letter, $length, $newLine = true)
	{
		for ($i = 0; $i < $length; $i++) 
		{
			echo $letter;
		}
		if ($newLine)
		{
			echo "\n\r";
		}
	}
}

?>