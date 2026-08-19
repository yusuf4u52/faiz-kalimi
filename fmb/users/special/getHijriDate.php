<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use GeniusTS\HijriDate\Date;
use GeniusTS\HijriDate\Hijri;

function getTodayDateHijri(): string
{
    Date::setToStringFormat('Y-m-d');
    return (string) Date::today();
}

function getHijriDate(string $date): string
{
    Date::setToStringFormat('Y-m-d');
    return (string) Hijri::convertToHijri($date);
}
