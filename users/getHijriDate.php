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

function getHijriMonth($date)
{
	$toStringFormat = 'm';
	Date::setToStringFormat($toStringFormat);
	return Hijri::convertToHijri($date);
}
