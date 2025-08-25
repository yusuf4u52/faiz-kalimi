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
                                <h2 class="mb-3">User Feedback</h2>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <form id="userfeedmenu" class="form-horizontal" method="GET"
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
                                            <button class="btn btn-light btn-sm" type="submit" name="search">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (isset($menu_list) && $menu_list->num_rows > 0) {
                                    $row_menu = $menu_list->fetch_assoc();
                                    $menu_item = unserialize($row_menu['menu_item']); ?>
                                    <div class="table-responsive mb-3">
                                        <table id="userfeedmenu" class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="text-align:left">Sabeel No</th>
                                                    <th style="text-align:left">Tiffin No</th>
                                                    <?php if (!empty($menu_item['sabji']['item'])) {
                                                        echo '<th style="text-align:left">' . $menu_item['sabji']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['tarkari']['item'])) {
                                                        echo '<th style="text-align:left">' . $menu_item['tarkari']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['rice']['item'])) {
                                                        echo '<th style="text-align:left">' . $menu_item['rice']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['roti']['item'])) {
                                                        echo '<th style="text-align:left">' . $menu_item['roti']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['extra']['item'])) {
                                                        echo '<th style="text-align:left">' . $menu_item['extra']['item'] . '</th>';
                                                    }
                                                    ?>
                                                    <th style="text-align:left">Feedback</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $thali = mysqli_query($link, "SELECT `thali` FROM user_feedmenu WHERE `menu_date` = '" . $_GET['menu_date'] . "'");
                                                if ($thali->num_rows > 0) {
                                                    $totaledited = $thali->num_rows;
                                                    $thalino = array();
                                                    while ($row_thali = mysqli_fetch_assoc($thali)) {
                                                        $thalino[] = $row_thali['thali'];
                                                    }
                                                    $sabeelno = "'" . implode("', '", $thalino) . "'";
                                                    $thali = mysqli_query($link, "SELECT Thali, tiffinno, thalisize, Transporter from thalilist WHERE Thali IN (" . $sabeelno . ") AND `hardstop` != 1 AND Active != 0 ORDER BY Transporter");
                                                    $sabji = 0; $tarkari = 0; $rice = 0;
                                                    while ($row = mysqli_fetch_assoc($thali)) {
                                                        $user_feedmenu = mysqli_query($link, "SELECT * FROM user_feedmenu WHERE `menu_date` = '" . $_GET['menu_date'] . "' AND `thali` = '" . $row['Thali'] . "' ORDER BY thali");
                                                        if ($user_feedmenu->num_rows > 0) {
                                                            $row_user = $user_feedmenu->fetch_assoc();
                                                            $user_menu_item = unserialize($row_user['menu_feed']); ?>
                                                            <tr>
                                                                <td style="text-align:left"><?php echo $row['Thali']; ?></td>
                                                                <td style="text-align:left"><?php echo $row['tiffinno']; ?></td>
                                                                <?php if (!empty($menu_item['sabji']['item'])) {
                                                                    if(!empty($user_menu_item['sabji']['rating'])) {
                                                                        $sabjirating = $user_menu_item['sabji']['rating'];
                                                                    } else {
                                                                        $sabjirating = 'NA';
                                                                    }
                                                                    echo '<td style="text-align:left">' . $sabjirating . '</td>';
                                                                }
                                                                if (!empty($menu_item['tarkari']['item'])) {
                                                                    if(!empty($user_menu_item['tarkari']['rating'])) {
                                                                        $tarkarirating = $user_menu_item['tarkari']['rating'];
                                                                    } else {
                                                                        $tarkarirating = 'NA';
                                                                    }
                                                                    echo '<td style="text-align:left">' . $tarkarirating . '</td>';
                                                                }
                                                                if (!empty($menu_item['rice']['item'])) {
                                                                    if(!empty($user_menu_item['rice']['rating'])) {
                                                                        $ricerating = $user_menu_item['rice']['rating'];
                                                                    } else {
                                                                        $ricerating = 'NA';
                                                                    }
                                                                    echo '<td style="text-align:left">' . $ricerating . '</td>';
                                                                } 
                                                                if (!empty($menu_item['roti']['item'])) {
                                                                    if(!empty($user_menu_item['roti']['rating'])) {
                                                                        $rotirating = $user_menu_item['roti']['rating'];
                                                                    } else {
                                                                        $rotirating = 'NA';
                                                                    }
                                                                    echo '<td style="text-align:left">' . $rotirating . '</td>';
                                                                }
                                                                if (!empty($menu_item['extra']['item'])) {
                                                                    if(!empty($user_menu_item['extra']['rating'])) {
                                                                        $extrarating = $user_menu_item['extra']['rating'];
                                                                    } else {
                                                                        $extrarating = 'NA';
                                                                    }
                                                                    echo '<td style="text-align:left">' . $extrarating . '</td>';
                                                                } ?>
                                                                <td><?php echo $row_user['feedback']; ?></td>
                                                            </tr>
                                                        <?php }
                                                    }
                                                } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Sabeel No</th>
                                                    <th>Tiffin No</th>
                                                    <?php if (!empty($menu_item['sabji']['item'])) {
                                                        echo '<th>' . $menu_item['sabji']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['tarkari']['item'])) {
                                                        echo '<th>' . $menu_item['tarkari']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['rice']['item'])) {
                                                        echo '<th>' . $menu_item['rice']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['roti']['item'])) {
                                                        echo '<th>' . $menu_item['roti']['item'] . '</th>';
                                                    }
                                                    if (!empty($menu_item['extra']['item'])) {
                                                        echo '<th>' . $menu_item['extra']['item'] . '</th>';
                                                    }
                                                    ?>
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
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
