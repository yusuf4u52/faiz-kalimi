<?php
/**
 * UI Helper Functions - Bootstrap 5 Wrappers
 * Simple functions for consistent UX patterns
 */

// Card with header
function ui_card($title, $subtitle = '', $back_url = '') {
    $back = $back_url ? "<a href=\"$back_url\" class=\"btn btn-sm btn-outline-secondary\">Back</a>" : '';
    $sub = $subtitle ? "<small class=\"text-muted d-block\">$subtitle</small>" : '';
    echo "<div class=\"card mb-3\"><div class=\"card-header\"><div class=\"d-flex justify-content-between align-items-center\"><div><h5 class=\"mb-0\">$title</h5>$sub</div>$back</div></div><div class=\"card-body\">";
}

function ui_card_end() {
    echo "</div></div>";
}

// Table
function ui_table($cols) {
    echo "<div class=\"table-responsive\"><table class=\"table table-sm table-hover align-middle\"><thead class=\"table-light\"><tr>";
    foreach ($cols as $c) echo "<th>$c</th>";
    echo "</tr></thead><tbody>";
}

function ui_tr($cells) {
    echo "<tr>";
    foreach ($cells as $c) echo "<td>$c</td>";
    echo "</tr>";
}

function ui_table_end($empty_msg = '', $count = -1) {
    if ($count === 0 && $empty_msg) {
        echo "<tr><td colspan=\"99\" class=\"text-center text-muted py-3\">$empty_msg</td></tr>";
    }
    echo "</tbody></table></div>";
}

// Search form
function ui_search($name, $placeholder, $value = '', $clear_url = '') {
    $clear = ($value && $clear_url) ? "<a href=\"$clear_url\" class=\"btn btn-outline-secondary\">&times;</a>" : '';
    echo "<form method=\"post\" class=\"flex-fill\" style=\"max-width:400px\"><input type=\"hidden\" name=\"action\" value=\"search\"><div class=\"input-group input-group-sm\"><input type=\"text\" name=\"$name\" class=\"form-control\" placeholder=\"$placeholder\" value=\"" . h($value) . "\"><button class=\"btn btn-outline-secondary\" type=\"submit\">Search</button>$clear</div></form>";
}

// Form elements
function ui_select($name, $opts, $sel = '', $placeholder = '') {
    $html = "<select name=\"$name\" class=\"form-select form-select-sm\">";
    if ($placeholder) $html .= "<option value=\"\">$placeholder</option>";
    foreach ($opts as $v => $l) {
        $s = ($v == $sel) ? ' selected' : '';
        $html .= "<option value=\"" . h($v) . "\"$s>" . h($l) . "</option>";
    }
    return $html . "</select>";
}

function ui_input($name, $val = '', $placeholder = '', $type = 'text', $style = '') {
    $st = $style ? " style=\"$style\"" : '';
    return "<input type=\"$type\" name=\"$name\" class=\"form-control form-control-sm\" value=\"" . h($val) . "\" placeholder=\"$placeholder\"$st>";
}

function ui_btn($text, $type = 'primary') {
    return "<button type=\"submit\" class=\"btn btn-sm btn-$type\">$text</button>";
}

function ui_link($text, $url, $type = 'primary') {
    return "<a href=\"$url\" class=\"btn btn-sm btn-$type\">$text</a>";
}

// Status & badges
function ui_dot($active) {
    return $active ? "<small class=\"text-success\">●</small>" : "<small class=\"text-muted\">○</small>";
}

function ui_badge($text, $type = 'primary') {
    $tc = in_array($type, ['warning', 'light']) ? ' text-dark' : '';
    return "<span class=\"badge bg-$type$tc\">$text</span>";
}

function ui_status($active, $labels = ['Active', 'Inactive']) {
    return ui_dot($active) . ' ' . ($active ? $labels[0] : $labels[1]);
}

// Display helpers
function ui_code($t) { return "<code class=\"small\">" . h($t) . "</code>"; }
function ui_muted($t) { return "<small class=\"text-muted\">" . h($t) . "</small>"; }
function ui_money($n) { return "Rs. " . number_format($n); }
function ui_date($d, $f = 'd/m H:i') { return "<small class=\"text-muted\">" . date($f, strtotime($d)) . "</small>"; }
function ui_ga($g, $a) { return "<small>" . substr($g,0,1) . "/$a</small>"; }
function ui_count($n, $s = 'item') { echo "<div class=\"small text-muted mb-2\">$n " . ($n==1?$s:$s.'s') . "</div>"; }

// Layout
function ui_toolbar() { echo "<div class=\"d-flex gap-2 mb-3 flex-wrap align-items-center\">"; }
function ui_toolbar_end() { echo "</div>"; }
function ui_btngroup($btns) {
    echo "<div class=\"btn-group btn-group-sm\">";
    foreach ($btns as $l => $u) echo "<a href=\"$u\" class=\"btn btn-outline-primary\">$l</a>";
    echo "</div>";
}

function ui_alert($msg, $type = 'warning') {
    echo "<div class=\"alert alert-$type py-2\">$msg</div>";
}

function ui_hr() { echo "<hr class=\"my-3\">"; }

// Escape helper
function h($s) { return htmlspecialchars($s ?? ''); }
