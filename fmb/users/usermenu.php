<?php
include('header.php');
include('navbar.php');

if (isset($_GET['menu_date'])) {
    $menu_list = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $_GET['menu_date'] . "' LIMIT 1");
}
?>

<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h2 class="mb-3">User Menu</h2>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'send') { ?>
                                    <div class="alert alert-success" role="alert">Edited list of Menu or roti dated
                                        <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is being
                                        send successfully.
                                    </div>
                                <?php } ?>
                                <form id="usermenu" class="form-horizontal" method="GET"
                                    action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row">
                                        <label for="menu_date" class="col-4 control-label">Menu Date</label>
                                        <div class="col-4">
                                            <input type="date" class="form-control"
                                                min="<?php echo date('Y-m-d', strtotime('- 1 week')); ?>"
                                                name="menu_date"
                                                value="<?php echo (!empty($_GET['menu_date']) ? $_GET['menu_date'] : ''); ?>"
                                                required>
                                        </div>
                                        <div class="col-4 col-md-3">
                                            <button class="btn btn-light btn-sm me-2 mb-2" type="submit" name="search">Search</button>
                                            <button class="btn btn-light btn-sm mb-2" type="submit" name="email"
                                                formaction="/fmb/users/emailmenu.php">Email</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (isset($menu_list) && $menu_list->num_rows > 0) {
                                    $row_menu = $menu_list->fetch_assoc();
                                    $menu_item = unserialize($row_menu['menu_item']); ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-striped table-hover display">
                                            <thead>
                                                <tr>
                                                    <th>Sabeel No</th>
                                                    <th>Tiffin No</th>
                                                    <th>Tiffin Size</th>
                                                    <th>Transporter</th>
                                                    <?php if (!empty($menu_item['sabji']['item'])) {
                                                        $sabjiqty = $menu_item['sabji']['qty'];
                                                        echo '<th>' . $menu_item['sabji']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['tarkari']['item'])) {
                                                        $tarkariqty = $menu_item['tarkari']['qty'];
                                                        echo '<th>' . $menu_item['tarkari']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['rice']['item'])) {
                                                        $riceqty = $menu_item['rice']['qty'];
                                                        echo '<th>' . $menu_item['rice']['item'] . '</th>';
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $thali = mysqli_query($link, "SELECT `id`, `thali` FROM user_menu WHERE `menu_date` = '" . $_GET['menu_date'] . "'");
                                                if ($thali->num_rows > 0) {
                                                    $totaledited = $thali->num_rows;
                                                    $thalino = array();
                                                    while ($row_thali = mysqli_fetch_assoc($thali)) {
                                                        $thalino[] = $row_thali['thali'];
                                                    }
                                                    $thaliid = "'" . implode("', '", $thalino) . "'";
                                                    $thali = mysqli_query($link, "SELECT id, Thali, tiffinno, thalisize, Transporter from thalilist WHERE id IN (" . $thaliid . ") AND `hardstop` != 1 AND Active != 0 ORDER BY Transporter");
                                                    $sabji = 0; $tarkari = 0; $rice = 0;
                                                    while ($row = mysqli_fetch_assoc($thali)) {
                                                        $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $_GET['menu_date'] . "' AND `thali` = '" . $row['id'] . "' ORDER BY thali");
                                                        if ($user_menu->num_rows > 0) {
                                                            $row_user = $user_menu->fetch_assoc();
                                                            $user_menu_item = unserialize($row_user['menu_item']); ?>
                                                            <tr>
                                                                <td><?php echo $row['Thali']; ?></td>
                                                                <td><?php echo $row['tiffinno']; ?></td>
                                                                <td><?php echo $row['thalisize']; ?></td>
                                                                <td><?php echo $row['Transporter']; ?></td>
                                                                <?php if (!empty($user_menu_item['sabji']['item'])) {
                                                                    $sabji = $sabji + $user_menu_item['sabji']['qty'];
                                                                    echo '<td>' . $user_menu_item['sabji']['qty'] . '</td>';
                                                                }
                                                                if (!empty($user_menu_item['tarkari']['item'])) {
                                                                    $tarkari = $tarkari + $user_menu_item['tarkari']['qty'];
                                                                    echo '<td>' . $user_menu_item['tarkari']['qty'] . '</td>';
                                                                }
                                                                if (!empty($user_menu_item['rice']['item'])) {
                                                                    $rice = $rice + $user_menu_item['rice']['qty'];
                                                                    echo '<td>' . $user_menu_item['rice']['qty'] . '</td>';
                                                                } ?>
                                                            </tr>
                                                        <?php }
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php 
                                    $totalthali = mysqli_query($link, "SELECT count(*) as tcount FROM `thalilist` WHERE Active = 1");
                                    $result = mysqli_fetch_row($totalthali);
                                    $total = $result[0];
                                    if(!empty($total)) {
                                        if(!empty($totaledited)) {
                                            echo '<h3 class="mb-3">Total Thali - '. $total .'</h3>';
                                            echo '<h4 class="mb-2">Total Edited Thali - '. $totaledited .'</h4>';
                                            if (!empty($menu_item['sabji']['item'])) {
                                                echo '<h5 class="mb-1">' . $menu_item['sabji']['item'] .' - '. $total - ($totaledited - $sabji / $sabjiqty ). '</h5>';
                                            }
                                            if (!empty($menu_item['tarkari']['item'])) {
                                                echo '<h5 class="mb-1">' . $menu_item['tarkari']['item'] .' - '. $total - ($totaledited - $tarkari / $tarkariqty). '</h5>';
                                            }
                                            if (!empty($menu_item['rice']['item'])) {
                                                echo '<h5 class="mb-1">' . $menu_item['rice']['item'] .' - '. $total - ($totaledited - $rice / $riceqty). '</h5>';
                                            }
                                        }
                                    }
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
