<?php
include('../users/connection.php');
include('../users/header.php');
date_default_timezone_set('Asia/Kolkata');
$now = new DateTime();
$dayOfWeek = $now->format('w'); // 0 (for Sunday) through 6 (for Saturday)
$time = $now->format('H:i'); // Current time in HH:MM
$isInRange = false;
if ($dayOfWeek == 6 && $time >= '13:00') {
    $isInRange = true;
} elseif ($dayOfWeek == 0 && $time <= '20:00') {
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
<div class="fmb-content mt-4">
	<div class="container">
		<div class="row">
			<div class="col-12 offset-sm-2 col-sm-8 offset-lg-3 col-lg-6">
				<div class="card">
					<div class="card-body">
						<a href="/fmb/index.php"><img class="img-fluid mx-auto d-block my-3" src="../users/assets/img/logo.png" alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" width="253" height="253" /></a>
						<hr>
	  					<h3 class="mb-4 text-center">Feedback</h3>
                        <?php if( !$isInRange ) {
                            echo '<h5>Feedback will be live from <strong class="text-danger">Saturday: 01:00 PM</strong> to <strong class="text-danger">Sunday: 08:00 PM</strong> for this week.</h5>';
                        } else {
                            if (isset($msg)) {
                                echo '<h5 class="text-success mt-5">Thank you <strong>Sabeel No: ' . $_POST['thali'] . '</strong> for your valuable feedback.</h5>';
                                echo '<h6><a href="/fmb/feedback">Click here</a> to submit review or another feedback.</h5>';
                            } else { ?>
                                <form id="searchsabeel" class="form-horizontal my-3" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row">
                                        <label for="sabeel_no" class="col-4 control-label">Sabeel No</label>
                                        <div class="col-5">
                                            <input type="text" class="form-control" name="sabeel_no" id="sabeel_no" value="<?php echo (!empty($_POST['sabeel_no']) ? $_POST['sabeel_no'] : ''); ?>" required>
                                        </div>
                                        <div class="col-3">
                                            <button class="btn btn-light" type="submit">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if( !empty($_POST['sabeel_no']) ) {
                                    $takesFmb = mysqli_query($link, "SELECT * FROM thalilist where `Thali` = '" . $_POST['sabeel_no'] . "' AND `hardstop` != 1") or die(mysqli_error($link)); 
                                    if (isset($takesFmb) && $takesFmb->num_rows > 0) {
                                        $takesFmb = $takesFmb->fetch_assoc(); ?>
                                        <hr>
                                        <form id="feedbackmenu" class="form-horizontal" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                            <input type="hidden" name="action" value="feedback_menu" />
                                            <input type="hidden" name="thali" id="thali" value="<?php echo (!empty($_POST['sabeel_no']) ? $_POST['sabeel_no'] : ''); ?>" />
                                            <?php $result = mysqli_query($link, "SELECT * FROM user_feedmenu WHERE `menu_date` BETWEEN DATE_ADD(CURDATE(), INTERVAL -WEEKDAY(CURDATE()) DAY) AND DATE_ADD(CURDATE(), INTERVAL (5 - WEEKDAY(CURDATE())) DAY) AND `thali` = '" . $_POST['sabeel_no'] . "' order by `menu_date` ASC") or die(mysqli_error($link));
                                            if ($result->num_rows > 0) {
                                                echo '<div class="alert alert-info" role="alert">Salaam <strong class="text-capitalize">'.strtolower($takesFmb['NAME']).'</strong>, you have already submitted your feedback.</div>';
                                            } else {
                                                $result = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` BETWEEN DATE_ADD(CURDATE(), INTERVAL -WEEKDAY(CURDATE()) DAY) AND DATE_ADD(CURDATE(), INTERVAL (5 - WEEKDAY(CURDATE())) DAY) AND `menu_type` = 'thaali' order by `menu_date` ASC") or die(mysqli_error($link));
                                                echo '<div class="alert alert-info" role="alert">Salaam <strong class="text-capitalize">'.strtolower($takesFmb['NAME']).'</strong>, please submit your feedback. Your feedback is valuable to us.</div>';
                                            }
                                            while ($menu = mysqli_fetch_assoc($result)) {
                                                if( !empty($menu['menu_item'])) {
                                                    $menu_item = unserialize($menu['menu_item']);
                                                } elseif( !empty($menu['menu_feed'])) {
                                                    $menu_item = unserialize($menu['menu_feed']);
                                                }
                                                echo '<h5 class="mb-3">'.date('d M Y (l)', strtotime($menu['menu_date'])).'</h5>';
                                                if (!empty($menu_item['sabji']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="sabji" class="col-4 control-label"><?php echo $menu_item['sabji']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][item]" id="sabji" value="<?php echo $menu_item['sabji']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input mt-2 sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" id="sabjirating1" value="1" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == '1') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating1">1</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input mt-2 sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" id="sabjirating2" value="2" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == '2') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating2">2</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input mt-2 sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" id="sabjirating3" value="3" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == '3') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating3">3</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input mt-2 sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" id="sabjirating4" value="4" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == '4') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating4">4</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input mt-2 sabjirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][sabji][rating]" id="sabjirating5" value="5" <?php echo ((!empty($menu_item['sabji']['rating']) && $menu_item['sabji']['rating'] == '5') ? 'checked' : '' ); ?> required>
                                                                <label class="form-check-label" for="sabjirating5">5</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['tarkari']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="tarkari" class="col-4 control-label"><?php echo $menu_item['tarkari']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][item]" id="tarkari" value="<?php echo $menu_item['tarkari']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" id="tarkarirating1" value="1" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == '1') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating1">1</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" id="tarkarirating2" value="2" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == '2') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating2">2</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" id="tarkarirating3" value="3" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == '3') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating3">3</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" id="tarkarirating4" value="4" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == '4') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating4">4</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 tarkarirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][tarkari][rating]" id="tarkarirating5" value="5" <?php echo ((!empty($menu_item['tarkari']['rating']) && $menu_item['tarkari']['rating'] == '5') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="tarkarirating5">5</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['rice']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="rice" class="col-4 control-label"><?php echo $menu_item['rice']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][item]" id="rice" value="<?php echo $menu_item['rice']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" id="ricerating1" value="1" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == '1') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating1">1</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" id="ricerating2" value="2" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == '2') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating2">2</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" id="ricerating3" value="3" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == '3') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating3">3</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" id="ricerating4" value="4" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == '4') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating4">4</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 ricerating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][rice][rating]" id="ricerating5" value="5" <?php echo ((!empty($menu_item['rice']['rating']) && $menu_item['rice']['rating'] == '5') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="ricerating5">5</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['roti']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="roti" class="col-4 control-label"><?php echo $menu_item['roti']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][item]" id="roti" value="<?php echo $menu_item['roti']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" id="rotirating1" value="1" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == '1') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating1">1</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" id="rotirating2" value="2" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == '2') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating2">2</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" id="rotirating3" value="3" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == '3') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating3">3</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" id="rotirating4" value="4" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == '4') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating4">4</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 rotirating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][roti][rating]" id="rotirating5" value="5" <?php echo ((!empty($menu_item['roti']['rating']) && $menu_item['roti']['rating'] == '5') ? 'checked' : '' ); ?> required>
                                                            <label class="form-check-label" for="rotirating5">5</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                                if (!empty($menu_item['extra']['item'])) { ?>
                                                    <div class="mb-3 row">
                                                        <label for="extra" class="col-4 control-label"><?php echo $menu_item['extra']['item']; ?></label>
                                                        <div class="col-8">
                                                            <input type="hidden" class="form-control" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][item]" id="extra" value="<?php echo $menu_item['extra']['item']; ?>">
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" id="extrarating1" value="1" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == '1') ? 'selected' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating1">1</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" id="extrarating2" value="2" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == '2') ? 'selected' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating2">2</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" id="extrarating3" value="3" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == '3') ? 'selected' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating3">3</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" id="extrarating4" value="4" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == '4') ? 'selected' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating4">4</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-2 extrarating" type="radio" name="feedback[<?php echo $menu['menu_date']; ?>][menu_item][extra][rating]" id="extrarating5" value="5" <?php echo ((!empty($menu_item['extra']['rating']) && $menu_item['extra']['rating'] == '5') ? 'selected' : '' ); ?> required>
                                                            <label class="form-check-label" for="extrarating5">5</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <div class="mb-3 row">
                                                    <label for="comment" class="col-4 control-label">Comment</label>
                                                    <div class="col-8">
                                                        <textarea class="form-control" id="comment" name="feedback[<?php echo $menu['menu_date']; ?>][comment]" rows="3"><?php echo (!empty($menu['feedback']) ? $menu['feedback'] : '' ); ?></textarea>
                                                    </div>  
                                                </div>
                                                <hr>
                                            <?php } ?>
                                            <div id="submit" class="mb-3 row">
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