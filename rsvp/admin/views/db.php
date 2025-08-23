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
        <div class="mb-3 row">
            <label for="query" class="col-3 control-label">Query</label>
            <div class="col-9">
                <textarea required class="form-control" name="query" id="query"><?= $query ?></textarea>
            </div>
        </div>
        <div class="mb-3 row">
            <div class="offset-3 col-9">
                <button type="submit" class="btn btn-light me-2">Run</button>
                <button onclick="history.back()" class="btn btn-light">Back</button>
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
            echo '<div class="table-responsive mt-4">
                <table class="table table-striped display" width="100%">';
                    foreach ($data as $row) {
                        $keys = array_keys($row);
                        $vals = array_values($row);
                        if ($header) {
                            $header = false;
                            echo '<tr><th>' . implode('</th><th>', $keys) . '</th></tr>';
                        }
                        echo '<tr><td>' . implode('</td><td>', $vals) . '</td></tr>';
                    }
                echo '</table>
            </div>';
        }
    }

}
?>