<?php
include('header.php');
include('navbar.php');
include('helpers.php');
include('getHijriDate.php');

$today = getTodayDateHijri();

/**
 * For a given thali, fetch every upcoming (today or later) menu_list entry
 * along with this thali's personal user_menu override and stop_thali
 * status for that date — 3 queries total, instead of the original design
 * (duplicated in both this file and _thalisearch_single.php) which ran up
 * to 2 extra queries PER menu date, in TWO separate near-identical loops.
 */
function build_admin_menu_entries(mysqli $link, string $thaliId, ?string $thalisize): array
{
    $menuRows = mysqli_fetch_all(
        db_query($link, "SELECT * FROM menu_list WHERE `menu_date` >= ? AND `menu_type` = 'thaali' ORDER BY `menu_date` DESC", "s", [date('Y-m-d')]),
        MYSQLI_ASSOC
    );

    $userMenuByDate = [];
    $userMenuResult = db_query($link, "SELECT * FROM user_menu WHERE `thali` = ?", "s", [$thaliId]);
    while ($row = mysqli_fetch_assoc($userMenuResult)) {
        $userMenuByDate[$row['menu_date']] = $row;
    }

    $stoppedDates = [];
    $stopResult = db_query($link, "SELECT `stop_date` FROM stop_thali WHERE `thali` = ?", "s", [$thaliId]);
    while ($row = mysqli_fetch_assoc($stopResult)) {
        $stoppedDates[$row['stop_date']] = true;
    }

    $entries = [];
    foreach ($menuRows as $menu) {
        $menuId = $menu['id'];
        $menuDate = $menu['menu_date'];
        $roti_qty = null;

        if (isset($userMenuByDate[$menuDate])) {
            $menu_item = decode_menu_item($userMenuByDate[$menuDate]['menu_item']);
            if (!empty($menu_item['roti']['qty'])) {
                $roti_qty = $menu_item['roti']['qty'];
            }
            $target = 'adminusermenu-' . $menuId;
        } else {
            $menu_item = decode_menu_item($menu['menu_item']);
            $sizeKey = match ($thalisize) {
                'Mini' => 'tqty',
                'Small' => 'sqty',
                'Medium' => 'mqty',
                'Large' => 'lqty',
                default => null,
            };
            if ($sizeKey !== null && !empty($menu_item['roti'][$sizeKey])) {
                $roti_qty = $menu_item['roti'][$sizeKey];
            }
            $target = 'adminmenu-' . $menuId;
        }

        $entries[] = [
            'id' => $menuId,
            'date' => $menuDate,
            'menu_item' => $menu_item,
            'roti_qty' => $roti_qty,
            'target' => $target,
            'status' => isset($stoppedDates[$menuDate]) ? 'stop' : 'start',
        ];
    }

    return $entries;
}

/**
 * Grouped contiguous stop_thali date ranges for a thali (e.g. 5 separate
 * daily rows covering Aug 10-14 collapse into one range: Aug 10 - Aug 14).
 * Used by both the "Stop Dates" tab listing and the "delete stop dates"
 * modals below — previously two separate, near-identical CTE queries.
 */
function get_stop_date_ranges(mysqli $link, string $thaliId): array
{
    $result = db_query(
        $link,
        "WITH ranked_dates AS (
            SELECT `id`, `thali`, `stop_date`, ROW_NUMBER() OVER (PARTITION BY `thali` ORDER BY `stop_date`) AS row_num
            FROM `stop_thali` WHERE `thali` = ?
         ),
         grouped_dates AS (
            SELECT `id`, `thali`, `stop_date`, DATE_SUB(`stop_date`, INTERVAL row_num DAY) AS group_key FROM ranked_dates
         )
         SELECT `id`, `thali`, MIN(`stop_date`) AS start_date, MAX(`stop_date`) AS end_date
         FROM grouped_dates GROUP BY `thali`, group_key ORDER BY start_date DESC",
        "s",
        [$thaliId]
    );
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$action = '';
$thali = null;
$cmusaid = $ctransporter = $csize = $eroti = $erice = $cemail = $remail = null;

if (!empty($_POST)) {
    if (isset($_POST['comment'])) {
        $clean_comment = htmlentities(strip_tags((string) $_POST['comment']), ENT_QUOTES, 'UTF-8');
        db_query(
            $link,
            "INSERT INTO `comments` (`author_id`, `user_id`, `comment`) VALUES (?, ?, ?)",
            "sis",
            [$_SESSION['thaliid'], (int) ($_POST['user_id'] ?? 0), $clean_comment]
        );
        $action = 'comment';
    }

    if (($_POST['action'] ?? null) === 'change_musaid' && isset($_POST['musaid'])) {
        $clean_musaid = htmlentities(strip_tags((string) $_POST['musaid']), ENT_QUOTES, 'UTF-8');
        db_query($link, "UPDATE `thalilist` SET `musaid` = ? WHERE id = ?", "si", [$clean_musaid, (int) $_POST['id']]);
        $action = 'cmusaid';
        $cmusaid = $clean_musaid;
        $thali = $_POST['thali'];
    }

    if (($_POST['action'] ?? null) === 'change_transporter' && isset($_POST['transporter'])) {
        $clean_transporter = htmlentities(strip_tags((string) $_POST['transporter']), ENT_QUOTES, 'UTF-8');
        db_query($link, "UPDATE `thalilist` SET `Transporter` = ? WHERE id = ?", "si", [$clean_transporter, (int) $_POST['id']]);
        db_query(
            $link,
            "UPDATE change_table SET processed = 1 WHERE userid = ? AND (`Operation` = 'Update Transporter' OR `Operation` = 'Update Address' OR `Operation` LIKE 'Update Address from %' OR `Operation` = 'Change Size' OR `Operation` LIKE 'Change Size from %') AND processed = 0",
            "i",
            [(int) $_POST['id']]
        );
        db_query(
            $link,
            "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Update Transporter', ?)",
            "sis",
            [$_POST['thali'], (int) $_POST['id'], $today]
        );
        $action = 'ctransporter';
        $ctransporter = $clean_transporter;
        $thali = $_POST['thali'];
    }

    if (($_POST['action'] ?? null) === 'extra_roti' && isset($_POST['extraRoti'])) {
        db_query($link, "UPDATE thalilist SET extraRoti = ? WHERE id = ?", "ii", [(int) $_POST['extraRoti'], (int) $_POST['id']]);
        $action = 'eroti';
        $eroti = (int) $_POST['extraRoti'];
        $thali = $_POST['thali'];
    }

    if (($_POST['action'] ?? null) === 'less_rice' && isset($_POST['lessRice'])) {
        db_query($link, "UPDATE thalilist SET lessRice = ? WHERE id = ?", "ii", [(int) $_POST['lessRice'], (int) $_POST['id']]);
        $action = 'erice';
        $erice = (int) $_POST['lessRice'];
        $thali = $_POST['thali'];
    }

    if (($_POST['action'] ?? null) === 'change_email' && isset($_POST['Email_ID'])) {
        $newEmail = trim((string) $_POST['Email_ID']);
        $checkemail = db_query($link, "SELECT id FROM thalilist WHERE Email_ID = ? OR `SEmail_ID` = ?", "ss", [$newEmail, $newEmail]);
        if ($checkemail->num_rows > 0) {
            $action = 'remail';
            $remail = $newEmail;
        } else {
            db_query($link, "UPDATE thalilist SET Email_ID = ? WHERE id = ?", "si", [$newEmail, (int) $_POST['id']]);
            $action = 'cemail';
            $cemail = $newEmail;
            $thali = $_POST['thali'];
        }
    }

    if (($_POST['action'] ?? null) === 'change_size' && isset($_POST['thalisize'])) {
        $currentSizeResult = db_query($link, "SELECT `thalisize` FROM thalilist WHERE `id` = ? LIMIT 1", "i", [(int) $_POST['id']]);
        $currentSize = $currentSizeResult->fetch_assoc();
        $sizeOperation = 'Change Size from ' . ($currentSize['thalisize'] ?? 'Unassigned');

        db_query(
            $link,
            "UPDATE change_table SET processed = 1 WHERE userid = ? AND (`Operation` = 'Change Size' OR `Operation` LIKE 'Change Size from %') AND processed = 0",
            "i",
            [(int) $_POST['id']]
        );
        db_query($link, "UPDATE thalilist SET thalisize = ? WHERE id = ?", "si", [$_POST['thalisize'], (int) $_POST['id']]);
        db_query(
            $link,
            "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`, `processed`) VALUES (?, ?, ?, ?, 0)",
            "siss",
            [$_POST['thali'], (int) $_POST['id'], $sizeOperation, $today]
        );
        $action = 'csize';
        $csize = $_POST['thalisize'];
        $thali = $_POST['thali'];
    }

    unset($_POST);
}

$current_year = mysqli_fetch_assoc(db_query($link, "SELECT value FROM settings WHERE `key` = 'current_year'"));
$musaid_list = mysqli_fetch_all(db_query($link, "SELECT username, email FROM users"), MYSQLI_ASSOC);

$values = null;
$result = null;
$max_days = null;
$thalilist_tablename = 'thalilist';
$receipts_tablename = 'receipts';
$adminMenuEntries = [];
$stopDateRanges = [];

if (isset($_GET['year'])) {
    // CRITICAL FIX: $_GET['year'] used to be concatenated directly into a
    // table name — "thalilist_" . $_GET['year'] — with zero validation.
    // Table/column names can't be bound as query parameters in a prepared
    // statement, so the only safe fix is to whitelist-validate the value
    // BEFORE it ever touches SQL: a 4-digit Hijri year matching the
    // <select> options below (1438-1450), nothing else. Anything that
    // doesn't match is rejected outright rather than "sanitized" — this
    // is an identifier context, where escaping isn't a reliable defense.
    $requestedYear = (string) $_GET['year'];
    if (!preg_match('/^1[3-5][0-9]{2}$/', $requestedYear)) {
        http_response_code(400);
        die('Invalid year.');
    }

    if ((string) $current_year['value'] === $requestedYear) {
        $thalilist_tablename = 'thalilist';
        $receipts_tablename = 'receipts';
    } else {
        $thalilist_tablename = 'thalilist_' . $requestedYear;
        $receipts_tablename = 'receipts_' . $requestedYear;
    }

    $baseQuery = "SELECT id, Thali, tiffinno, NAME, CONTACT, WhatsApp, Active, Transporter, thalisize, wingflat, sabeelType, sector, society, extraRoti, lessRice, previous_hub, yearly_hub, Zabihat, ITS_No, Email_ID, SEmail_ID, thalicount, Thali_start_date, Thali_stop_date, Full_Address, musaid, Paid, hardstop, (Previous_Due + yearly_hub - Paid) AS Total_Pending FROM thalilist";

    if (!empty($_GET['thalino'])) {
        $result = db_query($link, "$baseQuery WHERE Thali LIKE ?", "s", [$_GET['thalino']]);
    } elseif (!empty($_GET['tiffinno'])) {
        $result = db_query($link, "$baseQuery WHERE tiffinno LIKE ?", "s", [$_GET['tiffinno']]);
    } elseif (!empty($_GET['general'])) {
        $like = '%' . $_GET['general'] . '%';
        $result = db_query(
            $link,
            "$baseQuery WHERE Email_ID LIKE ? OR NAME LIKE ? OR CONTACT LIKE ? OR ITS_No LIKE ?",
            "ssss",
            [$like, $like, $like, $like]
        );
    } else {
        $result = db_query($link, $baseQuery);
    }

    // Table name interpolation here is unavoidable (table names can't be
    // bound as query parameters), but $thalilist_tablename can now only
    // ever be 'thalilist' or 'thalilist_<validated 4-digit year>'.
    $max_days = mysqli_fetch_row(mysqli_query($link, "SELECT MAX(thalicount) as max FROM `$thalilist_tablename`"));

    if ($result->num_rows === 1) {
        $values = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        $adminMenuEntries = build_admin_menu_entries($link, (string) $values['id'], $values['thalisize']);
        $stopDateRanges = get_stop_date_ranges($link, (string) $values['id']);
    }
}
?>

<div class="card">
  <div class="card-body">
    <h2 class="mb-3">Thali Search</h2>
    <?php if ($action === 'cmusaid') { ?>
      <div class="alert alert-success" role="alert">Musaid change to <strong><?php echo e($cmusaid); ?></strong> for sabeel no <strong><?php echo e($thali); ?></strong>.
      </div>
    <?php } if ($action === 'ctransporter') { ?>
      <div class="alert alert-success" role="alert">Transporter change to <strong><?php echo e($ctransporter); ?> for sabeel no <?php echo e($thali); ?></strong>.
      </div>
    <?php }
    if ($action === 'comment') { ?>
      <div class="alert alert-success" role="alert">A new comment is added for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>.
      </div>
    <?php }
    if ($action === 'csize') { ?>
      <div class="alert alert-success" role="alert">Thali size is changes to
        <strong><?php echo e($csize); ?></strong> for sabeel no
        <strong><?php echo e($thali); ?></strong>.
      </div>
    <?php }
    if ($action === 'eroti') { ?>
      <div class="alert alert-success" role="alert">Extra Roti
        <strong><?php echo e((string) $eroti); ?></strong> is added for sabeel no
        <strong><?php echo e($thali); ?></strong>.
      </div>
    <?php }
    if ($action === 'erice') { ?>
      <div class="alert alert-success" role="alert">Less Rice
        <strong><?php echo e((string) $erice); ?></strong> is added for sabeel no
        <strong><?php echo e($thali); ?></strong>.
      </div>
    <?php }
    if ($action === 'cemail') { ?>
      <div class="alert alert-success" role="alert">Email Id change to
        <strong><?php echo e($cemail); ?></strong> for sabeel no
        <strong><?php echo e($thali); ?></strong>.
      </div>
    <?php }
    if ($action === 'remail') { ?>
      <div class="alert alert-success" role="alert">Email Id
        <strong><?php echo e($remail); ?></strong> is already registered in our system.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'srange') { ?>
      <div class="alert alert-success" role="alert">Thali from <strong>
          <?php echo e(date('d M Y', strtotime($_GET['sdate'] ?? ''))); ?>
        </strong> to <strong>
          <?php echo e(date('d M Y', strtotime($_GET['edate'] ?? ''))); ?></strong> for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong> is stopped successfully.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'start') { ?>
      <div class="alert alert-success" role="alert">Stop thali dates from <strong>
          <?php echo e(date('d M Y', strtotime($_GET['sdate'] ?? ''))); ?>
        </strong> to <strong>
          <?php echo e(date('d M Y', strtotime($_GET['edate'] ?? ''))); ?>
        </strong> for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong> is deleted successfully.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'srsvp') { ?>
      <div class="alert alert-warning" role="alert">RSVP ended to stop thali of <strong>
          <?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?> for sabeel no
          <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>
        </strong>.</div>
    <?php }
    if (($_GET['action'] ?? null) === 'spermanant') { ?>
      <div class="alert alert-danger" role="alert">Thali is stopped permanently for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'edit') { ?>
      <div class="alert alert-success" role="alert">Thali of
        <strong><?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?></strong> is edited successfully for sabeel
        no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'sedit') { ?>
      <div class="alert alert-success" role="alert">Thali of <strong>
          <?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?>
        </strong> is started & edited successfully for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'nochange') { ?>
      <div class="alert alert-warning" role="alert">No change found for Thali of
        <strong><?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?></strong> for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'snochange') { ?>
      <div class="alert alert-success" role="alert">Thali of <strong>
          <?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?>
        </strong> is started successfully for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'astop') { ?>
      <div class="alert alert-warning" role="alert">Thali of <strong>
          <?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?>
        </strong> is already stopped for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'stop') { ?>
      <div class="alert alert-success" role="alert">Thali of <strong>
          <?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?>
        </strong> is stopped successfully for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong>.
      </div>
    <?php }
    if (($_GET['action'] ?? null) === 'rsvp') { ?>
      <div class="alert alert-danger" role="alert">You can't edit the thali now because RSVP time for editing
        Thali
        of
        <strong>
          <?php echo e(date('d M Y', strtotime($_GET['date'] ?? ''))); ?></strong> for sabeel no
        <strong><?php echo e($_GET['thalino'] ?? ''); ?></strong> is finished.
      </div>
    <?php } ?>
    <form class="form-horizontal" autocomplete="off">
      <div class="mb-3 row">
        <label for="inputThalino" class="col-3 control-label">Sabeel No</label>
        <div class="col-9">
          <input type="text" class="form-control" id="inputThalino" placeholder="Sabeel No" name="thalino">
        </div>
      </div>
      <div class="mb-3 row">
        <label for="inputThalino" class="col-3 control-label">Tiffin No</label>
        <div class="col-9">
          <input type="text" class="form-control" id="inputTiffinno" placeholder="Tiffin No" name="tiffinno">
        </div>
      </div>
      <div class="mb-3 row">
        <label for="inputGeneral" class="col-3 control-label">Other</label>
        <div class="col-9">
          <input type="text" class="form-control" id="inputGeneral" placeholder="Contact/ ITS no / Email / Name"
            name="general">
        </div>
      </div>
      <div class="mb-3 row">
        <label for="year" class="col-3 control-label">Year</label>
        <div class="col-9">
          <select class="form-select" id="year" name="year">
            <?php for ($i = 1438; $i <= 1450; $i++) { ?>
              <option value="<?php echo $i; ?>" <?php echo ($current_year['value'] == $i) ? 'selected' : ''; ?>>
                <?php echo $i; ?>
              </option>
            <?php } ?>
          </select>
        </div>
      </div>
      <div class="mb-3 row">
        <div class="col-9 offset-3">
          <button type="submit" class="btn btn-light">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (isset($_GET['year'])): ?>
  <div class="card mt-4">
    <div class="card-body">
      <h2 class="mb-3">Thali Info</h2>
      <?php
      if ($result->num_rows > 1):
        include('_thalisearch_multiple.php');
      elseif ($result->num_rows === 1):
        include('_thalisearch_single.php');
      else:
        echo "No records found";
      endif;
      ?>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($values)): ?>
  <div class="modal fade" id="changeMusaid">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="change_musaid" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>">
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Change Musaid</h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <select name="musaid" required='required' class="form-select">
              <option value=''>Select</option>
              <?php foreach ($musaid_list as $musaid) { ?>
                <option value='<?php echo e($musaid['email']); ?>'><?php echo e($musaid['username']); ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="changeTransporter">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="change_transporter" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>">
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Change Transporter</h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <select name="transporter" required='required' class="form-select">
              <option value=''>Select</option>
              <option value='Balasinor'>Balasinor</option>
              <option value='Imran'>Imran</option>
              <option value='Mauwala'>Mauwala</option>
              <option value='Molana'>Molana</option>
              <option value='Murtaza'>Murtaza</option>
              <option value='Murtaza (Cloud9)'>Murtaza (Cloud9)</option>
              <option value='Shahid'>Shahid</option>
              <option value='SUHB'>SUHB</option>
              <option value='Zainuddin'>Zainuddin</option>
              <option value='Zuhair'>Zuhair</option>
              <option value='Pick Up'>Pick Up</option>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="changeThalisize">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="change_size" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>">
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Change Thali Size</h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <select name="thalisize" required='required' class="form-select">
              <option value=''>Select</option>
              <option value='Mini'>Mini</option>
              <option value='Small'>Small</option>
              <option value='Medium'>Medium</option>
              <option value='Large'>Large</option>
              <option value='Friday'>Friday</option>
              <option value='Roti'>Roti</option>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="extraRoti">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="extra_roti" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>">
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Extra Roti</h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <input type="number" class="form-control" name="extraRoti" placeholder="Extra Roti Quantity" value="<?php echo e((string) $values['extraRoti']); ?>" min="<?php echo (($values['thalisize'] === 'Medium' || $values['thalisize'] === 'Large') ? '-2' : '-1'); ?>" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="lessRice">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="less_rice" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>">
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Less Rice</h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <input type="number" class="form-control" name="lessRice" placeholder="Less Rice Quantity" value="<?php echo e((string) $values['lessRice']); ?>" min="0" max="2" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="changeEmail">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="change_email" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>">
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Change Email Address</h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <input type="email" class="form-control" id="Email_ID" placeholder="Email" name="Email_ID" value='<?php echo e($values['Email_ID']); ?>' pattern="[a-z0-9._%+\-]+@gmail.com$" required>
            <p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="stop_thali">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="admin_stop" class="form-horizontal" method="POST" action="stopthali.php" autocomplete="off">
          <input type="hidden" name="action" value="admin_stop_thali" />
          <input type="hidden" name="thali" value="<?php echo e((string) $values['id']); ?>" />
          <input type="hidden" name="thalino" value="<?php echo e($_GET['thalino'] ?? ''); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($_GET['tiffinno'] ?? ''); ?>" />
          <input type="hidden" name="general" value="<?php echo e($_GET['general'] ?? ''); ?>" />
          <input type="hidden" name="year" value="<?php echo e($_GET['year'] ?? ''); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Stop Thali of sabeel no <?php echo e($values['Thali']); ?></h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div class="input-group input-daterange mb-3">
              <input type="text" class="form-control" name="start_date" id="start_date" placeholder="Start Date">
              <span class="input-group-text"><i class="bi bi-calendar"></i></span>
              <input type="text" class="form-control" name="end_date" id="end_date" placeholder="End Date">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="stop_permanent">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="stop_permanent" class="form-horizontal" method="POST" action="stop_permanant.php" autocomplete="off">
          <input type="hidden" name="action" value="stop_permanant" />
          <input type="hidden" name="id" value="<?php echo e((string) $values['id']); ?>" />
          <input type="hidden" name="thali" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="thalino" value="<?php echo e($values['Thali']); ?>" />
          <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
          <input type="hidden" name="general" value="<?php echo e($_GET['general'] ?? ''); ?>" />
          <input type="hidden" name="year" value="<?php echo e($_GET['year'] ?? ''); ?>" />
          <div class="modal-header">
            <h4 class="modal-title">Stop Permanent Thali of sabeel no <?php echo e($values['Thali']); ?></h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <p> Are you sure you want to permanently stop thali of sabeel no
              <strong><?php echo e($values['Thali']); ?></strong>?
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
            <button type="submit" class="btn btn-light">Yes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php foreach ($adminMenuEntries as $entry) {
    $menu_item = $entry['menu_item'];
    $status = $entry['status']; ?>
    <div class="modal fade" id="<?php echo e($entry['target']); ?>">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="changemenu-<?php echo (int) $entry['id']; ?>" class="form-horizontal" method="post" action="changemenu.php"
            autocomplete="off">
            <input type="hidden" name="action" value="admin_change_menu" />
            <input type="hidden" name="menu_id" value="<?php echo (int) $entry['id']; ?>" />
            <input type="hidden" name="thali" value="<?php echo e((string) $values['id']); ?>" />
            <input type="hidden" name="thalino" value="<?php echo e($_GET['thalino'] ?? ''); ?>" />
            <input type="hidden" name="tiffinno" value="<?php echo e($_GET['tiffinno'] ?? ''); ?>" />
            <input type="hidden" name="general" value="<?php echo e($_GET['general'] ?? ''); ?>" />
            <input type="hidden" name="year" value="<?php echo e($_GET['year'] ?? ''); ?>" />
            <div class="modal-header">
              <h4 class="modal-title">Edit Menu of
                <?php echo e(date('D d M y', strtotime($entry['date']))); ?> for Thaali no
                <?php echo e($values['Thali']); ?>
              </h4>
              <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                  class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
              <div id="status" class="mb-3 row">
                <label for="status" class="col-6 control-label">Thali Status</label>
                <div class="col-6">
                  <div class="form-check form-switch d-flex align-items-center">
                    <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" <?php echo ($status === 'start' ? 'checked' : ''); ?>>
                    <label id="status" class="form-check-label ms-1" for="status" <?php echo ($status === 'start' ? 'style=color:#198754;' : 'style=color:#dc3545;'); ?>><?php echo ($status === 'start' ? 'Start' : 'Stop'); ?></label>
                  </div>
                </div>
              </div>
              <div id="thali" class="<?php echo ($status === 'stop' ? 'd-none' : ''); ?>">
                <?php if (!empty($menu_item['sabji']['item'])) { ?>
                  <div class="mb-3 row row">
                    <label for="sabji" class="col-6 control-label"><?php echo e($menu_item['sabji']['item']); ?></label>
                    <div class="col-6">
                      <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji"
                        value="<?php echo e($menu_item['sabji']['item'] ?? ''); ?>">
                      <div class="input-group">
                        <button class="btn btn-light btn-minus" type="button">-</button>
                        <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty"
                          value="<?php echo e((string) ($menu_item['sabji']['qty'] ?? '1')); ?>"
                          min="0" readonly>
                        <button class="btn btn-light btn-plus" type="button">+</button>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                <?php if (!empty($menu_item['tarkari']['item'])) { ?>
                  <div class="mb-3 row row">
                    <label for="tarkari" class="col-6 control-label"><?php echo e($menu_item['tarkari']['item']); ?></label>
                    <div class="col-6">
                      <input type="hidden" class="form-control" name="menu_item[tarkari][item]" id="tarkari"
                        value="<?php echo e($menu_item['tarkari']['item'] ?? ''); ?>">
                      <div class="input-group">
                        <button class="btn btn-light btn-minus" type="button">-</button>
                        <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty"
                          value="<?php echo e((string) ($menu_item['tarkari']['qty'] ?? '1')); ?>"
                          min="0" readonly>
                        <button class="btn btn-light btn-plus" type="button">+</button>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                <?php if (!empty($menu_item['rice']['item'])) { ?>
                  <div class="mb-3 row row">
                    <label for="rice" class="col-6 control-label"><?php echo e($menu_item['rice']['item']); ?></label>
                    <div class="col-6">
                      <input type="hidden" class="form-control" name="menu_item[rice][item]" id="rice"
                        value="<?php echo e($menu_item['rice']['item'] ?? ''); ?>">
                      <div class="input-group">
                        <button class="btn btn-light btn-minus" type="button">-</button>
                        <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty"
                          value="<?php echo e((string) ($menu_item['rice']['qty'] ?? '1')); ?>" min="0"
                          readonly>
                        <button class="btn btn-light btn-plus" type="button">+</button>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                <?php if (!empty($menu_item['roti']['item'])) { ?>
                  <div class="mb-3 row row">
                    <label for="roti" class="col-6 control-label"><?php echo e($menu_item['roti']['item']); ?></label>
                    <div class="col-6">
                      <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti"
                        value="<?php echo e($menu_item['roti']['item'] ?? ''); ?>">
                      <input type="number" class="form-control" name="menu_item[roti][qty]" id="rotiqty"
                        value="<?php echo e((string) ($entry['roti_qty'] ?? '1')); ?>" min="0" readonly>
                    </div>
                  </div>
                <?php } ?>
                <?php if (!empty($menu_item['extra']['item'])) { ?>
                  <div class="mb-3 row row">
                    <label for="roti" class="col-6 control-label"><?php echo e($menu_item['extra']['item']); ?></label>
                    <div class="col-6">
                      <input type="hidden" class="form-control" name="menu_item[extra][item]" id="extra"
                        value="<?php echo e($menu_item['extra']['item'] ?? ''); ?>">
                      <input type="number" class="form-control" name="menu_item[extra][qty]" id="extraqty"
                        value="<?php echo e((string) ($menu_item['extra']['qty'] ?? '1')); ?>" min="0"
                        readonly>
                    </div>
                  </div>
                <?php } ?>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-light">Save changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php } ?>

  <?php foreach ($stopDateRanges as $start_values) { ?>
    <div class="modal fade" id="startthali-<?php echo (int) $start_values['id']; ?>">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="startthali-<?php echo (int) $start_values['id']; ?>" class="form-horizontal" method="post" action="stopthali.php"
            autocomplete="off">
            <input type="hidden" name="action" value="admin_start_thali" />
            <input type="hidden" name="thali" value="<?php echo e((string) $values['id']); ?>" />
            <input type="hidden" name="thalino" value="<?php echo e($values['Thali']); ?>" />
            <input type="hidden" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" />
            <input type="hidden" name="general" value="<?php echo e($_GET['general'] ?? ''); ?>" />
            <input type="hidden" name="year" value="<?php echo e($_GET['year'] ?? ''); ?>" />
            <?php if (date('Y-m-d') >= $start_values['start_date']) { ?>
              <input type="hidden" name="start_date" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" />
            <?php } else { ?>
              <input type="hidden" name="start_date" value="<?php echo e($start_values['start_date']); ?>" />
            <?php } ?>
            <input type="hidden" name="end_date" value="<?php echo e($start_values['end_date']); ?>" />
            <div class="modal-header">
              <h4 class="modal-title">Delete Stop Dates</h4>
              <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                  class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
              <?php if (date('Y-m-d') >= $start_values['start_date']) { ?>
                <p>Are you sure you want to delete the stop dates from <strong><?php echo e(date('d M Y', strtotime('+1 day'))); ?></strong> to <strong><?php echo e(date('d M Y', strtotime($start_values['end_date']))); ?></strong>. The thali will be start from tomorrow <strong><?php echo e(date('d M Y', strtotime('+1 day'))); ?></strong>.</p>
              <?php } else { ?>
                <p>Are you sure you want to delete the stop dates from <strong><?php echo e(date('d M Y', strtotime($start_values['start_date']))); ?></strong> to <strong><?php echo e(date('d M Y', strtotime($start_values['end_date']))); ?></strong>.</p>
              <?php } ?>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-light">Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php } ?>
<?php endif; ?>

<?php include('footer.php'); ?>
