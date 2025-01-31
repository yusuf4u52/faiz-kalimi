<?php

function main_menu_items($menus)
{
    foreach ($menus as $label => $href) {
        ?>
        <li class='nav-item  '>
            <a href='<?= $href ?>' class='nav-link'>
                <i class='nav-icon fas fa-user'></i>
                <p>
                    <?= $label ?>
                </p>
            </a>
        </li>
        <?php
    }
}

function set_error_message($message)
{
    setSessionData(ERROR_TRANSITION, $message);
}

function print_table_row($row, $index, $keyArray)
{
    echo '<tr>';
    foreach ($keyArray as $key) {
        if (str_starts_with($key, '__')) {
            echo '<td>';
            echo $key($row, $index);
            echo '</td>';
        } else {
            echo '<td>' . $row->$key . '</td>';
            //echo '<td>' . $row[$key] . '</td>';
        }

    }
    echo '</tr>';
}

function __show_row_sequence($row, $index)
{
    return ((int) $index) + 1;
}

function __show_row_blank($row, $index)
{
    return '';
}

function util_get_checkbox_column($checkbox_name, $checkbox_value, $selected_items = [])
{
    $checked = "checked='checked'";
    if (!in_array($checkbox_value, $selected_items)) {
        $checked = '';
    }

    return "<input type='checkbox' $checked name='$checkbox_name' value='$checkbox_value'>";
}

function util_show_data_table(array $records, array $cols)
{
    $colKeys = [];
    $colLabels = [];
    foreach ($cols as $key => $value) {
        $colLabels[] = $value;
        $colKeys[] = $key;
    }

    ?>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <?= util_table_header_row($colLabels) ?>
            </thead>
            <tbody>
                <?php util_table_data_rows($records, $colKeys) ?>
            </tbody>
        </table>
    </div>
    <?php
}


function util_table_data_rows($records, $colKeys)
{
    array_walk($records, 'print_table_row', $colKeys);
}

function util_table_header_row($labels)
{
    ?>
    <tr>
        <th>
            <?= implode("</th><th>", $labels) ?>
        </th>
    </tr>
    <?php
}

function show_section_content_with_footer($title, $footer_callback, $callback, ...$data)
{
    ?>
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <?= $title ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?= $callback($data) ?>
                </div>
                <div class="card-footer">
                    <?= $footer_callback($data) ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function show_section_content($title, $callback, ...$data)
{
    ?>
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <?= $title ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?= $callback($data) ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

function util_get_dropdown($name, $records, $selected_value = '')
{
    echo "<select name='$name' id='$name' class='form-select'>";
    foreach ($records as $value => $label) {
        $selected = ($value == $selected_value ? 'selected' : '');
        echo "<option value='$value' $selected>$label</option>";
    }
    echo '</select>';
}


function getCountDropdown($from = 0, $to = 10, $value = 0)
{
    $drop_down = '';
    for ($i = $from; $i <= $to; $i++) {
        $selected = '';
        if ($i == $value) {
            $selected = 'selected';
        }
        $drop_down .= "<option value='$i' $selected>$i</option>";
    }

    return $drop_down;
}

function get_tabel_row_for_array(...$data)
{
    return '<tr><td>' . implode('</td><td>', $data) . '</td></tr>';
}
