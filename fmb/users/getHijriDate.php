<?php
include '../vendor/autoload.php';

use GeniusTS\HijriDate\Date;
use GeniusTS\HijriDate\Hijri;

function getTodayDateHijri()
{
	$toStringFormat = 'Y-m-d';
	Date::setToStringFormat($toStringFormat);
	return Date::today();
}

function getHijriDate($date)
{
	$toStringFormat = 'Y-m-d';
	Date::setToStringFormat($toStringFormat);
	return Hijri::convertToHijri($date);
}

function getHijriFullDate($date)
{
	$toStringFormat = 'd M Y';
	Date::setToStringFormat($toStringFormat);
	return Hijri::convertToHijri($date);
}
