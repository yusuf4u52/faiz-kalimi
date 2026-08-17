<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');
include('getHijriDate.php');

$today = getTodayDateHijri();
$errormsg = null;
$msg = null;

if ($_POST) {
    $contact = trim((string) ($_POST['contact'] ?? ''));
    $its = trim((string) ($_POST['its'] ?? ''));
    $wingflat = trim((string) ($_POST['wingflat'] ?? ''));
    $society = trim((string) ($_POST['society'] ?? ''));
    $whatsapp = trim((string) ($_POST['whatsapp'] ?? ''));
    $secondEmail = trim((string) ($_POST['second_email'] ?? ''));

    if (!preg_match('/^[0-9]{10}$/', $contact) || !preg_match('/^[0-9]{8}$/', $its)
        || $wingflat === '' || $society === '' || !preg_match('/^[0-9]{10}$/', $whatsapp)) {
        header("Location: update_details.php?status=" . urlencode('Please check the details you entered and try again.'));
        exit;
    }

    $currentDetailsResult = db_query(
        $link,
        "SELECT `wingflat`, `society`, `Transporter` FROM thalilist WHERE `id` = ? LIMIT 1",
        "i",
        [(int) $_SESSION['thaliid']]
    );
    $currentDetails = $currentDetailsResult->fetch_assoc();
    if (!$currentDetails) {
        header("Location: update_details.php?status=" . urlencode('Thali details could not be found.'));
        exit;
    }

    $addressChanged = $wingflat !== trim((string) $currentDetails['wingflat'])
        || $society !== trim((string) $currentDetails['society']);

    db_query(
        $link,
        "UPDATE thalilist SET CONTACT = ?, ITS_No = ?, wingflat = ?, society = ?, WhatsApp = ? WHERE Thali = ?",
        "ssssss",
        [$contact, $its, $wingflat, $society, $whatsapp, $_SESSION['thali']]
    );

    if ($society !== $currentDetails['society']) {
        $checksociety = db_query($link, "SELECT * FROM thalilist WHERE society = ? LIMIT 1", "s", [$society]);
        if ($checksociety->num_rows > 0) {
            $row = $checksociety->fetch_assoc();
            db_query(
                $link,
                "UPDATE thalilist SET Transporter = ?, sector = ?, subsector = NULL, musaid = ?, Full_Address = ? WHERE id = ?",
                "sssss",
                [$row['Transporter'], $row['sector'], $row['musaid'], $row['Full_Address'], $_SESSION['thaliid']]
            );
        }
    }

    if ($addressChanged) {
        $previousTransporter = trim((string) $currentDetails['Transporter']);
        $addressOperation = $society !== $currentDetails['society']
            ? 'Update Address from ' . ($previousTransporter !== '' ? $previousTransporter : 'Unassigned')
            : 'Update Address';

        db_query(
            $link,
            "UPDATE change_table SET processed = 1
             WHERE userid = ? AND (`Operation` = 'Update Address' OR `Operation` LIKE 'Update Address from %') AND processed = 0",
            "s",
            [$_SESSION['thaliid']]
        );
        db_query(
            $link,
            "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, ?, ?)",
            "ssss",
            [$_SESSION['thali'], $_SESSION['thaliid'], $addressOperation, $today]
        );
    }

    if ($secondEmail !== '') {
        if (!preg_match('/^[a-z0-9._%+\-]+@gmail\.com$/i', $secondEmail)) {
            header("Location: update_details.php?status=" . urlencode('Secondary email must be a Gmail address.'));
            exit;
        }

        if ($secondEmail !== ($_SESSION['old_semail'] ?? null)) {
            $checkemail = db_query($link, "SELECT id FROM thalilist WHERE Email_ID = ? OR `SEmail_ID` = ?", "ss", [$secondEmail, $secondEmail]);
            if ($checkemail->num_rows > 0) {
                $errormsg = 'registered';
            } else {
                db_query($link, "UPDATE thalilist SET SEmail_ID = ? WHERE Thali = ?", "ss", [$secondEmail, $_SESSION['thali']]);

                if ($_SESSION['email'] !== $secondEmail) {
                    $first_email = $_SESSION['email'];
                    session_unset();
                    session_destroy();
                    $status = "Great! $secondEmail is registered successfully with us and $first_email is unregistered. Please login again.";
                    header("Location: https://kalimijamaatpoona.org/testfmb/index.php?status=" . urlencode($status));
                    exit;
                }
            }
        }
    }

    if ($errormsg === null) {
        $msg = 'updated';
    }

    unset($_SESSION['old_society'], $_SESSION['active'], $_SESSION['old_semail']);
}

$data = mysqli_fetch_assoc(db_query($link, "SELECT * FROM thalilist WHERE Thali = ?", "s", [$_SESSION['thali']]));

$_SESSION['old_society'] = $data['society'];
$_SESSION['old_semail'] = $data['SEmail_ID'];
$_SESSION['active'] = $data['Active'];
?>

<div class="card">
  <div class="card-body">
	<h2 class="mb-3">Update info</h2>
	<h6 class="mb-3">Make sure you fill out all the required fields.</h6>
	<?php if ($errormsg === 'registered') { ?>
	  <div class="alert alert-danger" role="alert">
		<?php echo '<strong>' . e($_POST['second_email'] ?? '') . '</strong> already registered. Try another Gmail ID.'; ?>
	  </div>
	<?php } elseif ($msg === 'updated') { ?>
	  <div class="alert alert-success" role="alert">
		Your details successfully updated.
	  </div>
	<?php } elseif (!empty($_GET['status'])) { ?>
	  <div class="alert alert-danger" role="alert"><?php echo e($_GET['status']); ?></div>
	<?php } ?>
	<form class="form-horizontal" method="post" autocomplete="off">
	  <input type="hidden" name="Thali" value='<?php echo e($_SESSION['thali']); ?>'>
	  <input type="hidden" name="Email_ID" value='<?php echo e($data['Email_ID']); ?>'>
	  <div class="mb-3 row">
		<label for="inputName" class="col-3 control-label">Primary Email</label>
		<div class="col-9">
		  <input type="email" class="form-control" id="inputEmail" placeholder="Email" required='required'
			name="email" value='<?php echo e($data['Email_ID']); ?>' disabled>
		</div>
	  </div>
	  <div class="mb-3 row">
		<label for="inputName" class="col-3 control-label">Secondary Email</label>
		<div class="col-9">
		  <input type="email" class="form-control" id="inputEmail" placeholder="Email" required='required'
			name="second_email" value='<?php echo e($data['SEmail_ID'] ?? ''); ?>' pattern="[a-z0-9._%+\-]+@gmail.com$">
			<p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
		</div>
	  </div>
	  <div class="mb-3 row">
		<label for="inputName" class="col-3 control-label">HOF Name</label>
		<div class="col-9">
		  <input type="text" class="form-control" id="inputName" placeholder="HOF Name" required='required'
			name="name" value='<?php echo e($data['NAME']); ?>'>
		</div>
	  </div>
	  <div class="mb-3 row">
		<label for="inputIts" class="col-3 control-label">HOF ITS</label>
		<div class="col-9">
		  <input type="text" pattern="[0-9]{8}" class="form-control" id="inputIts" placeholder="HOF ITS"
			required='required' name="its" value='<?php echo e($data['ITS_No']); ?>' title="Enter correct ITS ID">
		</div>
	  </div>
	  <div class="mb-3 row">
		<label for="inputContact" class="col-3 control-label">Mobile No.</label>
		<div class="col-9">
		  <input type="text" pattern="[0-9]{10}" class="form-control" id="inputContact" placeholder="Contact"
			required='required' name="contact" value='<?php echo e($data['CONTACT']); ?>' title="Enter 10 digits">
		</div>
	  </div>
	  <div class="mb-3 row">
		<label for="inputwhatsapp" class="col-3 control-label">Whatsapp No.</label>
		<div class="col-9">
		  <input type="text" pattern="[0-9]{10}" class="form-control" id="inputwhatsapp" placeholder="WhatsApp"
			required='required' name="whatsapp" value='<?php echo e($data['WhatsApp']); ?>'>
		</div>
	  </div>
	  <div class="mb-3 row">
		<label class="col-3 control-label">Wing/Flat</label>
		<div class="col-9">
		  <input type="text" class="form-control" placeholder="B1-1002" required='required' name="wingflat"
			value='<?php echo e($data['wingflat']); ?>'>
		</div>
	  </div>
	  <div class="mb-3 row">
		<label for="inputContact" class="col-3 control-label">Society</label>
		<div class="col-9">
		  <select class="form-select" name="society" required='required'>
			<option value=''>Select</option>
			<?php
			$society_list = db_query($link, "SELECT DISTINCT(society) FROM thalilist WHERE society IS NOT NULL ORDER BY society");
			while ($society_option = mysqli_fetch_assoc($society_list)) {
			  ?>
			  <option value='<?php echo e($society_option['society']); ?>' <?php echo ($society_option['society'] == $data['society']) ? "selected" : ""; ?>>
				<?php echo e($society_option['society']); ?>
			  </option>
			  <?php
			}
			?>
		  </select>
		</div>
	  </div>
	  <div class="mb-3 row">
		<div class="col-9 offset-3">
		  <button type="submit" class="btn btn-light" name='submit'>Submit</button>
		</div>
	  </div>
	</form>
  </div>
</div>

<?php include('footer.php'); ?>
