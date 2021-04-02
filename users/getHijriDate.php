<?php
include '../vendor/autoload.php';
use GeniusTS\HijriDate\Date;

function getTodayDateHijri()
{	
	$toStringFormat = 'Y-m-d';
	Date::setToStringFormat($toStringFormat);
	return Date::today();
}

?>