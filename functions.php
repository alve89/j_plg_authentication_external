<?php
	function generateRandomString($length = 32)
	{
    	$characters = '0123456789'
					 .'abcdefghijklmnopqrstuvwxyz'
					 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
					 //.',.-;:_!?%()=@&$§[]{}';
		$randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, strlen($characters) - 1)];
	    }
	    return $randomString;
	}
?>