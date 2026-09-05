<?php
/**
 * Menu-module-specific helpers. Generic helpers (e, db_query,
 * decode_menu_item, encode_menu_item, build_in_clause, ...) now live in
 * the single shared users/helpers.php — this file just adds this
 * module's own dish-type constants/labels on top of it.
 */
require_once __DIR__ . '/../helpers.php';

/** Human-readable label for a dish_type code. */
function dish_type_label(?string $type): string
{
    return match ($type) {
        '1' => 'Sabji Item',
        '2' => 'Tarkari/Dal Item',
        '3' => 'Rice Item',
        '4' => 'Roti/Bread Item',
        '5' => 'Extra Item',
        default => '',
    };
}

/** Valid dish_type codes, used to whitelist input. */
const VALID_DISH_TYPES = ['1', '2', '3', '4', '5'];

/** Valid menu_type values, used to whitelist input. */
const VALID_MENU_TYPES = ['thaali', 'miqaat'];
