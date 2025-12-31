<?php
include('../users/connection.php');
include('../users/header.php');
date_default_timezone_set('Asia/Kolkata');
$now = new DateTime();
$dayOfWeek = $now->format('w'); // 0 (for Sunday) through 6 (for Saturday)
$time = $now->format('H:i'); // Current time in HH:MM
$isInRange = false;
if ($dayOfWeek == 1 && $time >= '13:00') {
    $isInRange = true;
} elseif ($dayOfWeek >= 2 && $dayOfWeek <= 6) {
    $isInRange = true;
} elseif ($dayOfWeek == 0 && $time <= '23:30') {
    $isInRange = true;
}
if( $isInRange ) {
    if (isset($_POST['action']) && $_POST['action'] == 'feedback_menu') {
        foreach( $_POST['feedback'] as $date => $value ) {
            $user_feedmenu = mysqli_query($link, "SELECT * FROM user_feedmenu WHERE `menu_date` = '" . $date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
            if ($user_feedmenu->num_rows > 0) {
                $row = $user_feedmenu->fetch_assoc();
                $sql = "UPDATE `user_feedmenu` SET `menu_feed` = '" . serialize($value['menu_item']) . "', `feedback` = '" . $value['comment'] . "' WHERE `id` = '" . $row['id'] . "'";
                $action = 'editfeed';
            } else {
                $sql = "INSERT INTO `user_feedmenu` (`thali`,`menu_date`,`menu_feed`,`feedback`) VALUES ('" . $_POST['thali'] . "', '" . $date . "', '" . serialize($value['menu_item']) . "', '" . $value['comment'] . "')";
                $action = 'addfeed';
            }
            mysqli_query($link, $sql) or die(mysqli_error($link));
        }
        $msg = 'successfeed';
    } 
} else {
    $msg = 'notinrange';
} ?>

<div class="content mt-4">
	<div class="container">
		<div class="row">
			<div class="col-12 offset-sm-1 col-sm-10 offset-lg-2 col-lg-8">
				<div class="card">
					<div class="card-body">
						<a href="/fmb/index.php"><img class="img-fluid mx-auto d-block my-3" src="/fmb/styles/img/logo.avif" alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" width="253" height="253" /></a>
						<hr>
	  					<h2 class="mb-4 text-center">Feedback</h2>
                        <?php if( !$isInRange ) {
                            echo '<h5>Feedback will be live from <strong class="text-danger">Monday: 01:00 PM</strong> to <strong class="text-danger">Sunday: 11:30 PM</strong> for this week.</h5>';
                        } else {
                            if (isset($msg)) {
                                $hofName = mysqli_query($link, "SELECT * FROM thalilist where `Thali` = '" . $_POST['thali'] . "' AND `hardstop` != 1") or die(mysqli_error($link)); 
                                if (isset($hofName) && $hofName->num_rows > 0) {
                                    $hofName = $hofName->fetch_assoc();
                                    echo '<h5 class="text-success mt-5">Thank you <strong class="text-capitalize">'.strtolower($hofName['NAME']).'</strong> for your valuable feedback.</h5>';
                                    echo '<h6><a href="/fmb/feedback">Click here</a> to review your feedback.</h5>';
                                }
                            } else { ?>
                                <form class="form-horizontal my-3" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row">
                                        <label for="its_no" class="col-4 control-label">HOF ITS No</label>
                                        <div class="col-5">
                                            <input type="number" class="form-control" name="its_no" pattern="[0-9]{8}" value="<?php echo (!empty($_POST['its_no']) ? $_POST['its_no'] : ''); ?>" required>
                                            <p class="help-block mb-0 text-danger text-end"><small>Please enter HOF ITS No.</small></p>
                                        </div>
                                        <div class="col-3">
                                            <button class="btn btn-light" type="submit">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if( !empty($_POST['its_no']) ) {
                                    $takesFmb = mysqli_query($link, "SELECT * FROM thalilist where `ITS_No` = '" . $_POST['its_no'] . "' AND `hardstop` != 1") or die(mysqli_error($link)); 
                                    if (isset($takesFmb) && $takesFmb->num_rows > 0) {
                                        $takesFmb = $takesFmb->fetch_assoc(); ?>
                                        <hr>
                                        <form class="form-horizontal" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                            <input type="hidden" name="action" value="feedback_menu" />
                                            <input type="hidden" name="thali" value="<?php echo $takesFmb['Thali']; ?>" />
                                            <?php $today = date('Y-m-d');
											$yesterday = date('Y-m-d', strtotime('-1 day'));
											$weekStart = date('Y-m-d', strtotime('monday this week'));
											$endDate = $yesterday;
											if ($time >= '13:00') {
											    $endDate = $today;
											}
											$result = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` BETWEEN '$weekStart' AND '$endDate' AND `menu_type` = 'thaali' order by `menu_date` ASC") or die(mysqli_error($link));                                
                                            echo '<div class="alert alert-info" role="alert">Salaam <strong class="text-capitalize">'.strtolower($takesFmb['NAME']).'</strong>, your feedback is valuable to us. Please submit or review your feedback.</div>';
                                            while ($menu = mysqli_fetch_assoc($result)) {
                                                $user_feedmenu = mysqli_query($link, "SELECT * FROM user_feedmenu WHERE `menu_date` = '".$menu['menu_date']."'  AND `thali` = '" . $takesFmb['Thali'] . "' order by `menu_date` ASC") or die(mysqli_error($link));
                                                if ($user_feedmenu->num_rows > 0) {
                                                    $rowfeed = $user_feedmenu->fetch_assoc();
                                                    $menu_item = unserialize($rowfeed['menu_feed']);
                                                } elseif( !empty($menu['menu_item'])) {
                                                    $menu_item = unserialize($menu['menu_item']);
                                                }
                                                echo '<h5 class="mb-3">'.date('d M Y (l)', strtotime($menu['menu_date'])).'</h5>';
                                                if (!empty($menu_item['sabji']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="sabji" class="col-4 control-label"><?php echo $menu_item['sabji']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][item]" value="<?php echo $menu_item['sabji']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" value="Excellent" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == 'Excellent') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating1">Excellent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" value="Good" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == 'Good') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating2">Good</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" value="Ok" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == 'Ok') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating3">Ok</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" value="Not Satisfied" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == 'Not Satisfied') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating4">Not Satisfied</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" value="Not Taken" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == 'Not Taken') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating5">Not Taken</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['tarkari']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="tarkari" class="col-4 control-label"><?php echo $menu_item['tarkari']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][item]" value="<?php echo $menu_item['tarkari']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" value="Excellent" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == 'Excellent') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating1">Excellent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" value="Good" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == 'Good') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating2">Good</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" value="Ok" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == 'Ok') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating3">Ok</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" value="Not Satisfied" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == 'Not Satisfied') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating4">Not Satisfied</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" value="Not Taken" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == 'Not Taken') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating5">Not Taken</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['rice']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="rice" class="col-4 control-label"><?php echo $menu_item['rice']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][item]" value="<?php echo $menu_item['rice']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" value="Excellent" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == 'Excellent') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating1">Excellent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" value="Good" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == 'Good') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating2">Good</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" value="Ok" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == 'Ok') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating3">Ok</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" value="Not Satisfied" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == 'Not Satisfied') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating4">Not Satisfied</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" value="Not Taken" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == 'Not Taken') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating5">Not Taken</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['roti']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="roti" class="col-4 control-label"><?php echo $menu_item['roti']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][item]" value="<?php echo $menu_item['roti']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" value="Excellent" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == 'Excellent') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating1">Excellent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" value="Good" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == 'Good') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating2">Good</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" value="Ok" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == 'Ok') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating3">Ok</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" value="Not Satisfied" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == 'Not Satisfied') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating4">Not Satisfied</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" value="Not Taken" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == 'Not Taken') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating5">Not Taken</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['extra']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="extra" class="col-4 control-label"><?php echo $menu_item['extra']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][item]" value="<?php echo $menu_item['extra']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" value="Excellent" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == 'Excellent') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating1">Excellent</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" value="Good" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == 'Good') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating2">Good</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" value="Ok" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == 'Ok') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating3">Ok</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" value="Not Satisfied" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == 'Not Satisfied') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating4">Not Satisfied</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" value="Not Taken" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == 'Not Taken') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating5">Not Taken</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <div class="mb-3 row">
                                                    <label for="comment" class="col-4 control-label">Comment</label>
                                                    <div class="col-8">
                                                        <textarea class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][comment]" rows="3"><?php echo (!empty($menu['feedback']) ? $menu['feedback'] : '' ); ?></textarea>
                                                    </div>  
                                                </div>
                                                <hr>
                                            <?php } ?>
                                            <div class="mb-3 row">
                                                <div class="offset-4 col-8">
                                                    <button type="submit" class="btn btn-light me-2">Submit</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php } else {
                                        echo '<h5 class="text-danger mt-4">You are not eligible to submit feedback because you are not taking barakat of thali from Kalimi Mohallah - Poona.</h5>';
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
<?php include('../users/footer.php'); ?>
