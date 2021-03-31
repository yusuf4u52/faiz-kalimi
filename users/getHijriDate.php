<?php
include '../vendor/autoload.php';
use Cammac\Carbony\Carbony;

function getTodayDateHijri()
{	
	return Carbony::now()->hijriFormat('Y-m-d');
}

?>