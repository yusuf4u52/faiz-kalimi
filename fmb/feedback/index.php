<?php
include('../users/connection.php');
include('../users/helpers.php');

date_default_timezone_set('Asia/Kolkata');
$now = new DateTime();
$dayOfWeek = (int) $now->format('w'); // 0 (Sunday) through 6 (Saturday)
$time = $now->format('H:i');

$isInRange = false;
if ($dayOfWeek === 1 && $time >= '13:00') {
    $isInRange = true;
} elseif ($dayOfWeek >= 2 && $dayOfWeek <= 6) {
    $isInRange = true;
} elseif ($dayOfWeek === 0 && $time <= '23:30') {
    $isInRange = true;
}

// Ratings accepted from the radio buttons below. Shared between rendering
// and validation so a tampered/unexpected value posted directly (bypassing
// the "required" attribute) can never reach the database.
$ratingOptions = ['Excellent', 'Good', 'Ok', 'Not Satisfied', 'Not Taken'];
// menu_item sub-keys we render a feedback row for, in display order.
$itemTypes = ['sabji', 'tarkari', 'rice', 'roti', 'extra'];

/**
 * Decode a stored menu_feed value. Older rows were written with serialize(),
 * new rows are written with json_encode() (safer: no risk of PHP object
 * injection if a legacy value is ever tampered with). Reads support both so
 * existing data keeps working.
 */
function feedback_decode(?string $raw): array
{
    if ($raw === null || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    $legacy = @unserialize($raw, ['allowed_classes' => false]);

    return is_array($legacy) ? $legacy : [];
}

/** Monday-to-(today-or-yesterday) window that feedback is currently open for. */
function feedback_window(string $time): array
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $endDate = $time >= '13:00' ? $today : $yesterday;

    return [$weekStart, $endDate];
}

$view = 'closed';
$errors = [];
$hofRow = null;
$menuRows = [];
$thankYouName = '';
$itsNo = trim((string) ($_POST['its_no'] ?? ''));

if (!$isInRange) {
    $view = 'closed';
} elseif (isset($_POST['action']) && $_POST['action'] === 'feedback_menu') {
    // --- Submitting feedback for a previously-searched HOF ---
    $thali = trim((string) ($_POST['thali'] ?? ''));
    $feedback = is_array($_POST['feedback'] ?? null) ? $_POST['feedback'] : [];

    if ($thali === '' || !ctype_digit($thali)) {
        $errors[] = 'Invalid submission. Please search for your ITS No again.';
    } else {
        // Re-check eligibility server-side rather than trusting the hidden
        // field — it only tells us which thali the form was built for, not
        // that the submitter is still allowed to give feedback for it.
        $thaliCheck = db_query($link, "SELECT `NAME` FROM `thalilist` WHERE `Thali` = ? AND `hardstop` != 1", "s", [$thali]);
        if ($thaliCheck->num_rows === 0) {
            $errors[] = 'You are not eligible to submit feedback because you are not taking barakat of thali from Kalimi Mohallah - Poona.';
        } else {
            $thaliRow = $thaliCheck->fetch_assoc();
            $thankYouName = $thaliRow['NAME'];
        }
    }

    if (empty($errors)) {
        [$weekStart, $endDate] = feedback_window($time);

        // Only dates genuinely open this week may be written — a posted
        // date outside that window (or not a real menu date) is ignored.
        $menuResult = db_query(
            $link,
            "SELECT `menu_date` FROM `menu_list` WHERE `menu_date` BETWEEN ? AND ? AND `menu_type` = 'thaali' ORDER BY `menu_date` ASC",
            "ss",
            [$weekStart, $endDate]
        );
        $validDates = [];
        while ($row = mysqli_fetch_assoc($menuResult)) {
            $validDates[$row['menu_date']] = true;
        }

        try {
            foreach ($feedback as $date => $value) {
                $date = (string) $date;
                if (!isset($validDates[$date]) || !is_array($value)) {
                    continue;
                }

                $menuItem = is_array($value['menu_item'] ?? null) ? $value['menu_item'] : [];
                $cleanItem = [];
                foreach ($itemTypes as $type) {
                    if (empty($menuItem[$type]['item'])) {
                        continue;
                    }
                    $rating = (string) ($menuItem[$type]['rating'] ?? '');
                    if (!in_array($rating, $ratingOptions, true)) {
                        continue; // ignore a tampered/invalid rating value
                    }
                    $cleanItem[$type] = [
                        'item' => (string) $menuItem[$type]['item'],
                        'rating' => $rating,
                    ];
                }
                $comment = trim((string) ($value['comment'] ?? ''));
                $encoded = json_encode($cleanItem);

                $existing = db_query(
                    $link,
                    "SELECT id FROM `user_feedmenu` WHERE `menu_date` = ? AND `thali` = ?",
                    "ss",
                    [$date, $thali]
                );

                if ($existing->num_rows > 0) {
                    $existingRow = $existing->fetch_assoc();
                    db_query(
                        $link,
                        "UPDATE `user_feedmenu` SET `menu_feed` = ?, `feedback` = ? WHERE `id` = ?",
                        "ssi",
                        [$encoded, $comment, $existingRow['id']]
                    );
                } else {
                    db_query(
                        $link,
                        "INSERT INTO `user_feedmenu` (`thali`, `menu_date`, `menu_feed`, `feedback`) VALUES (?, ?, ?, ?)",
                        "ssss",
                        [$thali, $date, $encoded, $comment]
                    );
                }
            }
            $view = 'thanks';
        } catch (RuntimeException $e) {
            error_log('[feedback/index.php] ' . $e->getMessage());
            $errors[] = 'Sorry, something went wrong while saving your feedback. Please try again in a moment or contact the Jamaat office.';
        }
    }
} elseif ($itsNo !== '') {
    // --- Step 1: looking up the HOF by ITS No ---
    if (!preg_match('/^[0-9]{8}$/', $itsNo)) {
        $errors[] = 'Please enter a valid 8 digit HOF ITS No.';
    } else {
        $hofResult = db_query($link, "SELECT * FROM `thalilist` WHERE `ITS_No` = ? AND `hardstop` != 1", "s", [$itsNo]);
        if ($hofResult->num_rows > 0) {
            $hofRow = $hofResult->fetch_assoc();
            $view = 'results';
        } else {
            $view = 'ineligible';
        }
    }
} else {
    $view = 'search';
}

if ($view === 'results' && $hofRow !== null) {
    [$weekStart, $endDate] = feedback_window($time);

    $menuResult = db_query(
        $link,
        "SELECT * FROM `menu_list` WHERE `menu_date` BETWEEN ? AND ? AND `menu_type` = 'thaali' ORDER BY `menu_date` ASC",
        "ss",
        [$weekStart, $endDate]
    );
    while ($menu = mysqli_fetch_assoc($menuResult)) {
        $feedResult = db_query(
            $link,
            "SELECT * FROM `user_feedmenu` WHERE `menu_date` = ? AND `thali` = ?",
            "ss",
            [$menu['menu_date'], $hofRow['Thali']]
        );
        if ($feedResult->num_rows > 0) {
            $feedRow = $feedResult->fetch_assoc();
            $menu['menu_item'] = feedback_decode($feedRow['menu_feed']);
            $menu['feedback'] = (string) $feedRow['feedback'];
        } elseif (!empty($menu['menu_item'])) {
            $menu['menu_item'] = feedback_decode($menu['menu_item']);
            $menu['feedback'] = '';
        } else {
            $menu['menu_item'] = [];
            $menu['feedback'] = '';
        }
        $menuRows[] = $menu;
    }
}

include('../users/header.php'); ?>

<div class="content mt-4">
    <div class="container">
        <div class="row">
            <div class="col-12 offset-sm-1 col-sm-10 offset-lg-2 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <a href="/fmb/index.php"><img class="img-fluid mx-auto d-block my-3" src="/fmb/assets/img/logo.avif" alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" width="253" height="253" /></a>
                        <hr>
                        <h2 class="mb-4 text-center">Feedback</h2>

                        <?php if (!empty($errors)) { ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo e(implode(' ', $errors)); ?>
                            </div>
                        <?php }

                        if ($view === 'closed') {
                            echo '<h5>Feedback will be live from <strong class="text-danger">Monday: 01:00 PM</strong> to <strong class="text-danger">Sunday: 11:30 PM</strong> for this week.</h5>';
                        } elseif ($view === 'thanks') { ?>
                            <h5 class="text-success mt-5">Thank you <strong class="text-capitalize"><?php echo e(strtolower($thankYouName)); ?></strong> for your valuable feedback.</h5>
                            <h6><a href="/fmb/feedback">Click here</a> to review your feedback.</h6>
                        <?php } elseif ($view === 'ineligible') { ?>
                            <h5 class="text-danger mt-4">You are not eligible to submit feedback because you are not taking barakat of thali from Kalimi Mohallah - Poona.</h5>
                        <?php }

                        if ($view === 'search' || $view === 'results' || $view === 'ineligible') { ?>
                            <form class="form-horizontal my-3" method="post" autocomplete="off">
                                <div class="mb-3 row">
                                    <label for="its_no" class="col-4 control-label">HOF ITS No</label>
                                    <div class="col-5">
                                        <input type="text" inputmode="numeric" class="form-control" id="its_no" name="its_no" pattern="[0-9]{8}" maxlength="8" value="<?php echo e($itsNo); ?>" required>
                                        <p class="help-block mb-0 text-danger text-end"><small>Please enter HOF ITS No.</small></p>
                                    </div>
                                    <div class="col-3">
                                        <button class="btn btn-light" type="submit">Search</button>
                                    </div>
                                </div>
                            </form>
                        <?php }

                        if ($view === 'results') { ?>
                            <hr>
                            <form class="form-horizontal" method="post" autocomplete="off">
                                <input type="hidden" name="action" value="feedback_menu" />
                                <input type="hidden" name="thali" value="<?php echo e((string) $hofRow['Thali']); ?>" />

                                <div class="alert alert-info" role="alert">Salaam <strong class="text-capitalize"><?php echo e(strtolower($hofRow['NAME'])); ?></strong>, your feedback is valuable to us. Please submit or review your feedback.</div>

                                <?php foreach ($menuRows as $menu) {
                                    $menuDate = (string) $menu['menu_date'];
                                    $menuItem = $menu['menu_item']; ?>
                                    <h5 class="mb-3"><?php echo e(date('d M Y (l)', strtotime($menuDate))); ?></h5>

                                    <?php foreach ($itemTypes as $type) {
                                        if (empty($menuItem[$type]['item'])) {
                                            continue;
                                        }
                                        $itemName = (string) $menuItem[$type]['item'];
                                        $currentRating = (string) ($menuItem[$type]['rating'] ?? '');
                                        $fieldBase = 'feedback[' . $menuDate . '][menu_item][' . $type . ']'; ?>
                                        <div class="mb-3 row">
                                            <label class="col-4 control-label"><?php echo e($itemName); ?></label>
                                            <div class="col-8">
                                                <input type="hidden" name="<?php echo e($fieldBase); ?>[item]" value="<?php echo e($itemName); ?>">
                                                <?php foreach ($ratingOptions as $i => $option) {
                                                    $optionId = $type . '_' . $menuDate . '_' . $i; ?>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input <?php echo e($type); ?>rating" type="radio" id="<?php echo e($optionId); ?>" name="<?php echo e($fieldBase); ?>[rating]" value="<?php echo e($option); ?>" <?php echo ($currentRating === $option) ? 'checked' : ''; ?> required>
                                                        <label class="form-check-label" for="<?php echo e($optionId); ?>"><?php echo e($option); ?></label>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <div class="mb-3 row">
                                        <label for="comment_<?php echo e($menuDate); ?>" class="col-4 control-label">Comment</label>
                                        <div class="col-8">
                                            <textarea class="form-control" id="comment_<?php echo e($menuDate); ?>" name="feedback[<?php echo e($menuDate); ?>][comment]" rows="3"><?php echo e((string) $menu['feedback']); ?></textarea>
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
                        <?php } ?>
                    </div>
                </div>

                <?php include('../users/footer.php'); ?>