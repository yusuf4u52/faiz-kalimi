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
                <h2 class="mb-3">User Menu</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (($_GET['action'] ?? null) === 'send') { ?>
                    <div class="alert alert-success" role="alert">Edited list of Menu or roti dated
                        <strong><?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?></strong> is being
                        send successfully.
                    </div>
                <?php } ?>
                <form id="usermenu" class="form-horizontal" method="GET"
                    action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                    <div class="mb-3 row">
                        <label for="menu_date" class="col-4 control-label">Menu Date</label>
                        <div class="col-4">
                            <input type="date" class="form-control"
                                min="<?php echo date('Y-m-d', strtotime('- 1 week')); ?>"
                                name="menu_date"
                                value="<?php echo e($menuDate ?? ''); ?>"
                                required>
                        </div>
                        <div class="col-4 col-md-3">
                            <button class="btn btn-light btn-sm me-2 mb-2" type="submit" name="search">Search</button>
                            <button class="btn btn-light btn-sm mb-2" type="submit" name="email"
                                formaction="/testfmb/users/emailmenu.php">Email</button>
                        </div>
                    </div>
                </form>
                <?php if ($menu_list && $menu_list->num_rows > 0) {
                    $row_menu = $menu_list->fetch_assoc();
                    $menu_item = decode_menu_item($row_menu['menu_item']); ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-hover display">
                            <thead>
                                <tr>
                                    <th>Sabeel No</th>
                                    <th>Tiffin No</th>
                                    <th>Tiffin Size</th>
                                    <th>Transporter</th>
                                    <?php
                                    $sabjiqty = $menu_item['sabji']['qty'] ?? 0;
                                    $tarkariqty = $menu_item['tarkari']['qty'] ?? 0;
                                    $riceqty = $menu_item['rice']['qty'] ?? 0;
                                    if (!empty($menu_item['sabji']['item'])) {
                                        echo '<th>' . e($menu_item['sabji']['item']) . '</th>';
                                    }
                                    if (!empty($menu_item['tarkari']['item'])) {
                                        echo '<th>' . e($menu_item['tarkari']['item']) . '</th>';
                                    }
                                    if (!empty($menu_item['rice']['item'])) {
                                        echo '<th>' . e($menu_item['rice']['item']) . '</th>';
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totaledited = 0;
                                $sabji = 0;
                                $tarkari = 0;
                                $rice = 0;

                                $thali = db_query($link, "SELECT `thali` FROM user_menu WHERE `menu_date` = ?", "s", [$menuDate]);
                                if ($thali->num_rows > 0) {
                                    $totaledited = $thali->num_rows;
                                    $thalino = [];
                                    while ($row_thali = mysqli_fetch_assoc($thali)) {
                                        $thalino[] = $row_thali['thali'];
                                    }

                                    $placeholders = implode(',', array_fill(0, count($thalino), '?'));
                                    $types = str_repeat('s', count($thalino));

                                    // Single JOIN instead of one user_menu query per
                                    // thalilist row (was N+1 — a separate round trip
                                    // for every thali in the list).
                                    $thali = db_query(
                                        $link,
                                        "SELECT t.id, t.Thali, t.tiffinno, t.thalisize, t.Transporter, um.menu_item
                                         FROM thalilist t
                                         INNER JOIN user_menu um ON um.thali = t.id AND um.menu_date = ?
                                         WHERE t.id IN ($placeholders) AND t.`hardstop` != 1 AND t.Active != 0 AND t.thalisize != 'Roti'
                                         ORDER BY t.Transporter",
                                        's' . $types,
                                        array_merge([$menuDate], $thalino)
                                    );

                                    while ($row = mysqli_fetch_assoc($thali)) {
                                        $user_menu_item = decode_menu_item($row['menu_item']); ?>
                                            <tr>
                                                <td><?php echo e($row['Thali']); ?></td>
                                                <td><?php echo e($row['tiffinno']); ?></td>
                                                <td><?php echo e($row['thalisize']); ?></td>
                                                <td><?php echo e($row['Transporter']); ?></td>
                                                <?php if (!empty($user_menu_item['sabji']['item'])) {
                                                    $sabji += (int) $user_menu_item['sabji']['qty'];
                                                    echo '<td>' . (int) $user_menu_item['sabji']['qty'] . '</td>';
                                                }
                                                if (!empty($user_menu_item['tarkari']['item'])) {
                                                    $tarkari += (int) $user_menu_item['tarkari']['qty'];
                                                    echo '<td>' . (int) $user_menu_item['tarkari']['qty'] . '</td>';
                                                }
                                                if (!empty($user_menu_item['rice']['item'])) {
                                                    $rice += (int) $user_menu_item['rice']['qty'];
                                                    echo '<td>' . (int) $user_menu_item['rice']['qty'] . '</td>';
                                                } ?>
                                            </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                    $totalthali = db_query($link, "SELECT count(*) as tcount FROM `thalilist` WHERE `hardstop` != 1 AND Active != 0 AND thalisize != 'Roti'");
                    $result = mysqli_fetch_row($totalthali);
                    $total = (int) $result[0];
                    if ($total > 0 && $totaledited > 0) {
                        echo '<h3 class="mb-3">Total Thali - ' . $total . '</h3>';
                        echo '<h4 class="mb-2">Total Edited Thali - ' . $totaledited . '</h4>';
                        if (!empty($menu_item['sabji']['item']) && $sabjiqty > 0) {
                            echo '<h5 class="mb-1">' . e($menu_item['sabji']['item']) . ' - ' . ($total - ($totaledited - $sabji / $sabjiqty)) . '</h5>';
                        }
                        if (!empty($menu_item['tarkari']['item']) && $tarkariqty > 0) {
                            echo '<h5 class="mb-1">' . e($menu_item['tarkari']['item']) . ' - ' . ($total - ($totaledited - $tarkari / $tarkariqty)) . '</h5>';
                        }
                        if (!empty($menu_item['rice']['item']) && $riceqty > 0) {
                            echo '<h5 class="mb-1">' . e($menu_item['rice']['item']) . ' - ' . ($total - ($totaledited - $rice / $riceqty)) . '</h5>';
                        }
                    }
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
