<?php

if (!is_user_a(SUPER_ADMIN)) {
    do_redirect_with_message('/home', 'Access denied. SUPER_ADMIN role required.');
}

do_for_post('_handle_post');

function _handle_post()
{
    $action = $_POST['action'] ?? '';
    
    if ($action === 'search') {
        $search = $_POST['search'] ?? '';
        if (!empty($search)) {
            setAppData('search_term', $search);
        }
    } else if ($action === 'pre_allocate') {
        $its_id = $_POST['its_id'] ?? '';
        $hof_id = $_POST['hof_id'] ?? '';
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? null;
        
        if (empty($its_id) || empty($area_code)) {
            setSessionData(TRANSIT_DATA, 'Invalid data provided.');
            return;
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $allocated_by = $userData->itsid ?? '';
        
        $result = admin_pre_allocate_seat($its_id, $hof_id, $area_code, $seat_number, $allocated_by);
        
        if ($result['success']) {
            setSessionData(TRANSIT_DATA, 'Seat pre-allocated successfully!');
        } else {
            $error = $result['error'] ?? 'UNKNOWN';
            if ($error === 'GENDER_MISMATCH') {
                $member_gender = $result['member_gender'] ?? '';
                $area_gender = $result['area_gender'] ?? '';
                setSessionData(TRANSIT_DATA, "Gender mismatch: Cannot allocate {$member_gender} member to {$area_gender} area.");
            } else if ($error === 'SEAT_TAKEN') {
                setSessionData(TRANSIT_DATA, 'Failed to pre-allocate seat. Seat is already taken by someone else.');
            } else if ($error === 'INVALID_AREA') {
                setSessionData(TRANSIT_DATA, 'Invalid area selected.');
            } else {
                setSessionData(TRANSIT_DATA, 'Failed to pre-allocate seat. Please try again.');
            }
        }
    } else if ($action === 'delete_allocation') {
        $its_id = $_POST['its_id'] ?? '';
        
        if (empty($its_id)) {
            setSessionData(TRANSIT_DATA, 'Invalid ITS ID provided.');
            return;
        }
        
        $success = delete_seat_allocation($its_id);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat allocation deleted successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to delete seat allocation.');
        }
    } else if ($action === 'toggle_selection') {
        $open = $_POST['open'] ?? 'N';
        $success = toggle_seat_selection($open === 'Y');
        if (!$success) {
            setSessionData(TRANSIT_DATA, 'Failed to update seat selection status.');
        }
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    $search_term = getAppData('search_term') ?? '';
    $is_selection_open = is_seat_selection_open();
    
    // Get all allocations
    $allocations = get_all_seat_allocations();
    
    // Filter by search if provided
    if (!empty($search_term)) {
        $allocations = array_filter($allocations, function($a) use ($search_term) {
            return stripos($a->hof_id, $search_term) !== false || 
                   stripos($a->full_name, $search_term) !== false ||
                   stripos($a->its_id, $search_term) !== false;
        });
    }
    
    // Build area options for select
    $area_opts = [];
    foreach ($areas as $a) $area_opts[$a->area_code] = $a->area_name;
    
    // Toggle button HTML
    $toggle_btn = '<form method="post" class="mb-0"><input type="hidden" name="action" value="toggle_selection"><input type="hidden" name="open" value="' . ($is_selection_open ? 'N' : 'Y') . '"><button type="submit" class="btn btn-sm ' . ($is_selection_open ? 'btn-success' : 'btn-outline-secondary') . '">' . ($is_selection_open ? '● Open' : '○ Closed') . '</button></form>';
    
    ui_card("Seat Management - {$hijri_year}H", '', '', ['Toggle' => $toggle_btn]);
    
    // Toolbar
    ui_toolbar();
    ui_search('search', 'Search ITS or Name...', $search_term, $search_term ? "$url/seat-management" : '');
    ui_btngroup(['Pre Allocate Seat' => "$url/seat-pre-allocate", 'Manage Grid' => "$url/seating-areas", 'Payment Exceptions' => "$url/seat-exceptions"]);
    ui_toolbar_end();
    
    // Table
    ui_count(count($allocations), 'allocation');
    ui_table(['ITS', 'Member', 'G/Age', 'Area', 'Seat', 'By', 'Date', '']);
    
    if (empty($allocations)) {
        ui_table_end('No allocations found', 0);
    } else {
        foreach ($allocations as $alloc) {
            $seat = $alloc->seat_number ? "<strong>{$alloc->seat_number}</strong>" : ui_muted('—');
            
            // Show admin name if allocated by admin, otherwise 'Self'
            if ($alloc->allocated_by) {
                $by = $alloc->allocated_by_name ?: $alloc->allocated_by;
            } else {
                $by = 'Self';
            }
            
            // Delete button
            $delete_btn = '<form method="post" style="display:inline" onsubmit="return confirm(\'Delete allocation for ' . h($alloc->full_name) . '?\')"><input type="hidden" name="action" value="delete_allocation"><input type="hidden" name="its_id" value="' . h($alloc->its_id) . '"><button type="submit" class="btn btn-sm btn-link text-danger p-0">Delete</button></form>';
            
            ui_tr([
                ui_code($alloc->its_id),
                h($alloc->full_name),
                ui_ga($alloc->gender, $alloc->age),
                ui_muted($alloc->area_name),
                $seat,
                ui_muted($by),
                ui_date($alloc->allocated_at),
                $delete_btn
            ]);
        }
        ui_table_end();
    }
    
    ui_card_end();
}
