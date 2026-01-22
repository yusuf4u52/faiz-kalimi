<?php
/**
 * Initialization script to create seats for all existing seating areas
 * Run this after migration to pre-create all seats with status='available'
 * 
 * Usage: php initialize_seats_for_existing_areas.php
 */

require_once __DIR__ . '/../../_includes.php';

function initialize_all_seating_areas() {
    $hijri_year = get_current_hijri_year();
    
    // Get all seating areas with seat ranges configured
    $query = 'SELECT area_code, seat_start, seat_end FROM kl_shehrullah_seating_areas 
              WHERE hijri_year = ? AND seat_start IS NOT NULL AND seat_end IS NOT NULL 
              AND seat_start > 0 AND seat_end >= seat_start';
    $result = run_statement($query, $hijri_year);
    
    if (!$result->success) {
        echo "Error: Failed to fetch seating areas\n";
        return false;
    }
    
    $areas = $result->data;
    $total_areas = count($areas);
    $total_seats = 0;
    
    echo "Found {$total_areas} seating areas with seat ranges configured\n\n";
    
    foreach ($areas as $area) {
        $area_code = $area->area_code;
        $seat_start = intval($area->seat_start);
        $seat_end = intval($area->seat_end);
        $seat_count = $seat_end - $seat_start + 1;
        
        echo "Syncing seats for area: {$area_code} (seats {$seat_start}-{$seat_end})... ";
        
        $result = sync_seats_for_area($area_code, $hijri_year);
        
        if ($result['success']) {
            echo "OK ({$seat_count} seats)\n";
            $total_seats += $seat_count;
        } else {
            echo "FAILED: {$result['message']}\n";
        }
    }
    
    echo "\n";
    echo "Initialization complete!\n";
    echo "Total areas processed: {$total_areas}\n";
    echo "Total seats created: {$total_seats}\n";
    
    return true;
}

// Run initialization
if (php_sapi_name() === 'cli') {
    initialize_all_seating_areas();
} else {
    echo "This script should be run from command line\n";
}
