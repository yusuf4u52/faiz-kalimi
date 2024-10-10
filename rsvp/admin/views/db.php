<?php
function content_display()
{
    $query = '';
    $result = null;
    if (is_post()) {
        $query = $_POST['query'];
        $result = execute_query($query, false, false);
    }

    ?>
    <form method="post">
        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="query" class="col-sm-3 col-form-label">Query</label>
                <div class="col-sm-9">
                    <textarea required class="form-control" name="query" id="query"><?= $query ?></textarea>
                </div>
            </div>
            <div class="form-group" style="text-align: right; vertical-align: middle;font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Run</button>
            </div>
        </div>
    </form>
    <?php
    if (isset($result)) {
        $count = $result->count;
        echo "$count rows effected";
        $data = $result->data;

        if (isset($data) && is_array($data)) {
            $header = true;
            echo '<table class="table table-bordered">';
            foreach ($data as $row) {
                $keys = array_keys($row);
                $vals = array_values($row);
                if ($header) {
                    $header = false;
                    echo '<tr><th>' . implode('</th><th>', $keys) . '</th></tr>';
                }
                echo '<tr><td>' . implode('</td><td>', $vals) . '</td></tr>';
            }
            echo '</table>';
        }
    }

}
?>