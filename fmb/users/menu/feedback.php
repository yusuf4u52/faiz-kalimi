<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');

$menuDate = $_GET['menu_date'] ?? null;
$isValidDate = $menuDate && DateTime::createFromFormat('Y-m-d', $menuDate);

$menu_list = null;
$menu_item = [];
if ($isValidDate) {
    $menu_list = db_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = ? LIMIT 1", "s", [$menuDate]);
}
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">User Feedback</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <form id="userfeedmenu" class="form-horizontal" method="GET"
                    action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                    <div class="mb-3 row">
                        <label for="menu_date" class="col-4 control-label">Menu Date</label>
                        <div class="col-4">
                            <input type="date" class="form-control"
                                name="menu_date"
                                value="<?php echo e($menuDate ?? ''); ?>"
                                required>
                        </div>
                        <div class="col-4 col-md-3">
                            <button class="btn btn-light btn-sm" type="submit" name="search">Search</button>
                        </div>
                    </div>
                </form>
                <?php if ($menu_list && $menu_list->num_rows > 0) {
                    $row_menu = $menu_list->fetch_assoc();
                    $menu_item = decode_menu_item($row_menu['menu_item']);
                    $courses = ['sabji', 'tarkari', 'rice', 'roti', 'extra'];
                    $ratingCounts = [];
                    $responseCount = 0;

                    foreach ($courses as $course) {
                        if (!empty($menu_item[$course]['item'])) {
                            $ratingCounts[$course] = [];
                        }
                    }

                    $feedbackSummary = db_query(
                        $link,
                        "SELECT uf.menu_feed
                         FROM thalilist t
                         INNER JOIN user_feedmenu uf ON uf.thali = t.id
                         WHERE uf.menu_date = ? AND t.`hardstop` != 1 AND t.Active != 0",
                        's',
                        [$menuDate]
                    );

                    while ($feedbackRow = mysqli_fetch_assoc($feedbackSummary)) {
                        $responseCount++;
                        $userMenuItem = decode_menu_item($feedbackRow['menu_feed']);

                        foreach (array_keys($ratingCounts) as $course) {
                            $rating = trim((string) ($userMenuItem[$course]['rating'] ?? 'Not Taken'));
                            $rating = $rating !== '' ? $rating : 'Not Taken';
                            $ratingCounts[$course][$rating] = ($ratingCounts[$course][$rating] ?? 0) + 1;
                        }
                    }
                    ?>
                    <section class="mb-4" aria-labelledby="feedback-summary-title">
                        <h3 id="feedback-summary-title" class="h4 mb-1">Response summary</h3>
                        <p class="text-muted mb-3"><?php echo $responseCount; ?> response<?php echo $responseCount === 1 ? '' : 's'; ?></p>
                        <div class="row g-3">
                            <?php foreach ($ratingCounts as $course => $counts) {
                                $totalRatings = array_sum($counts);
                                $ratingOrder = ['Excellent', 'Good', 'Ok', 'Not Satisfied', 'Not Taken'];
                                $ratings = array_unique(array_merge($ratingOrder, array_keys($counts))); ?>
                                <div class="col-12 col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h4 class="h6 mb-3"><?php echo e($menu_item[$course]['item']); ?></h4>
                                        <?php foreach ($ratings as $rating) {
                                            $count = $counts[$rating] ?? 0;
                                            if ($count === 0) {
                                                continue;
                                            }
                                            $percentage = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                                            $barClass = match ($rating) {
                                                'Excellent' => 'bg-success',
                                                'Good' => 'bg-primary',
                                                'Ok' => 'bg-info',
                                                'Not Satisfied' => 'bg-danger',
                                                default => 'bg-secondary',
                                            };
                                            ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span><?php echo e($rating); ?></span>
                                                    <span><?php echo $count; ?> (<?php echo round($percentage); ?>%)</span>
                                                </div>
                                                <div class="progress" role="progressbar" aria-label="<?php echo e($rating); ?>" aria-valuenow="<?php echo round($percentage); ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </section>
                    <div class="table-responsive mb-3">
                        <table id="userfeedmenu" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="text-align:left">Sabeel No</th>
                                    <th style="text-align:left">Tiffin No</th>
                                    <?php foreach (['sabji', 'tarkari', 'rice', 'roti', 'extra'] as $course) {
                                        if (!empty($menu_item[$course]['item'])) {
                                            echo '<th style="text-align:left">' . e($menu_item[$course]['item']) . '</th>';
                                        }
                                    } ?>
                                    <th style="text-align:left">Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $thali = db_query($link, "SELECT `thali` FROM user_feedmenu WHERE `menu_date` = ?", "s", [$menuDate]);
                                if ($thali->num_rows > 0) {
                                    $thalino = [];
                                    while ($row_thali = mysqli_fetch_assoc($thali)) {
                                        $thalino[] = $row_thali['thali'];
                                    }

                                    $placeholders = implode(',', array_fill(0, count($thalino), '?'));
                                    $types = str_repeat('s', count($thalino));

                                    // Single JOIN instead of one user_feedmenu query per
                                    // thalilist row (was N+1 — a separate round trip
                                    // for every thali in the list).
                                    $thali = db_query(
                                        $link,
                                        "SELECT t.id, t.Thali, t.tiffinno, t.thalisize, t.Transporter, uf.menu_feed, uf.feedback
                                         FROM thalilist t
                                         INNER JOIN user_feedmenu uf ON uf.thali = t.id AND uf.menu_date = ?
                                         WHERE t.id IN ($placeholders) AND t.`hardstop` != 1 AND t.Active != 0
                                         ORDER BY t.Transporter",
                                        's' . $types,
                                        array_merge([$menuDate], $thalino)
                                    );

                                    while ($row = mysqli_fetch_assoc($thali)) {
                                        $user_menu_item = decode_menu_item($row['menu_feed']); ?>
                                            <tr>
                                                <td style="text-align:left"><?php echo e($row['Thali']); ?></td>
                                                <td style="text-align:left"><?php echo e($row['tiffinno']); ?></td>
                                                <?php foreach (['sabji', 'tarkari', 'rice', 'roti', 'extra'] as $course) {
                                                    if (!empty($menu_item[$course]['item'])) {
                                                        $rating = $user_menu_item[$course]['rating'] ?? 'Not Taken';
                                                        echo '<td style="text-align:left">' . e($rating) . '</td>';
                                                    }
                                                } ?>
                                                <td><?php echo e($row['feedback'] ?? ''); ?></td>
                                            </tr>
                                <?php }
                                } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Sabeel No</th>
                                    <th>Tiffin No</th>
                                    <?php foreach (['sabji', 'tarkari', 'rice', 'roti', 'extra'] as $course) {
                                        if (!empty($menu_item[$course]['item'])) {
                                            echo '<th>' . e($menu_item[$course]['item']) . '</th>';
                                        }
                                    } ?>
                                    <th>Feedback</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
