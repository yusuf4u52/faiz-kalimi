<?php
include('../users/connection.php');
include('../users/helpers.php');
require_once '../users/_sendMail.php';

$msg = null;
$formValues = ['its' => '', 'firstname' => '', 'fathername' => '', 'lastname' => '', 'gender' => '', 'mobile' => '', 'whatsapp' => '', 'email' => '', 'wingflat' => '', 'society' => ''];

function formatRegistrationNamePart(string $name): string
{
    $words = preg_split('/\\s+/', trim($name));

    return implode(' ', array_map(static function (string $word): string {
        return ucfirst(strtolower($word));
    }, $words ?: []));
}

if (isset($_POST['submit'])) {
    $formValues['its'] = trim((string) ($_POST['its'] ?? ''));
    $formValues['firstname'] = trim((string) ($_POST['firstname'] ?? ''));
    $formValues['fathername'] = trim((string) ($_POST['fathername'] ?? ''));
    $formValues['lastname'] = trim((string) ($_POST['lastname'] ?? ''));
    $formValues['gender'] = trim((string) ($_POST['gender'] ?? ''));
    $formValues['mobile'] = trim((string) ($_POST['mobile'] ?? ''));
    $formValues['whatsapp'] = trim((string) ($_POST['whatsapp'] ?? ''));
    $formValues['email'] = trim((string) ($_POST['email'] ?? ''));
    $formValues['wingflat'] = trim((string) ($_POST['wingflat'] ?? ''));
    $formValues['society'] = trim((string) ($_POST['society'] ?? ''));

    // Server-side validation matching the form's client-side pattern=""
    // attributes — those are trivially bypassed by posting directly, so
    // this is the validation that actually matters. Also guards against
    // storing unvalidated names that later get echoed (mostly-escaped
    // elsewhere in the app) or dropped unescaped into the welcome email
    // body below.
    $namePattern = '/^[A-Za-z ]+$/';
    $errors = [];

    if (!preg_match('/^[0-9]{8}$/', $formValues['its'])) {
        $errors[] = 'ITS number must be 8 digits.';
    }
    if (!preg_match($namePattern, $formValues['firstname'])) {
        $errors[] = 'First name must contain letters only.';
    }
    if (!preg_match($namePattern, $formValues['fathername'])) {
        $errors[] = "Father's/Husband's name must contain letters only.";
    }
    if (!preg_match($namePattern, $formValues['lastname'])) {
        $errors[] = 'Last name must contain letters only.';
    }
    if (!in_array($formValues['gender'], ['male', 'female'], true)) {
        $errors[] = 'Please select gender.';
    }
    if (!preg_match('/^[0-9]{10}$/', $formValues['mobile'])) {
        $errors[] = 'Mobile number must be 10 digits.';
    }
    if ($formValues['whatsapp'] !== '' && !preg_match('/^[0-9]{10}$/', $formValues['whatsapp'])) {
        $errors[] = 'WhatsApp number must be 10 digits.';
    }
    if (!is_valid_gmail_address($formValues['email'])) {
        $errors[] = 'Email must be a valid Gmail address.';
    }
    if ($formValues['wingflat'] === '') {
        $errors[] = 'Flat No/House No is required.';
    }
    if ($formValues['society'] === '') {
        $errors[] = 'Society/House Name is required.';
    }
    if ($formValues['society'] === 'Other' && trim((string) ($_POST['society_name'] ?? '')) === '') {
        $errors[] = 'Please enter your society/house name.';
    }

    if (!empty($errors)) {
        $msg = 'Please fix the following and resubmit: ' . implode(' ', $errors);
    } else {
        $its = $formValues['its'];
        $email = $formValues['email'];

        $thalilist_result = db_query(
            $link,
            "SELECT id FROM `thalilist` WHERE `ITS_No` = ? OR `Email_ID` = ? OR `SEmail_ID` = ?",
            "sss",
            [$its, $email, $email]
        );

        if ($thalilist_result->num_rows > 0) {
            $msg = "Your data already exist in the system and so you can login directly.";
        } else {
            if ($formValues['society'] === 'Other') {
                $society = trim((string) $_POST['society_name']);
                $full_address = trim((string) ($_POST['society_address'] ?? ''));
                $sector = '';
                $transporter = '';
                $musaid = '';
            } else {
                $society = $formValues['society'];
                $full_address = '';
                $sector = '';
                $transporter = '';
                $musaid = '';

                $thalidataresult = db_query(
                    $link,
                    "SELECT Full_Address, musaid, sector, Transporter FROM `thalilist` WHERE society = ? LIMIT 1",
                    "s",
                    [$society]
                );
                if ($thalidataresult->num_rows > 0) {
                    $thalidatarow = $thalidataresult->fetch_assoc();
                    $sector = (string) $thalidatarow['sector'];
                    $transporter = (string) $thalidatarow['Transporter'];
                    $musaid = (string) $thalidatarow['musaid'];
                    $full_address = (string) $thalidatarow['Full_Address'];
                }
            }

            $firstName = formatRegistrationNamePart($formValues['firstname']);
            $fatherName = formatRegistrationNamePart($formValues['fathername']);
            $lastName = formatRegistrationNamePart($formValues['lastname']);
            $firstNameSuffix = $formValues['gender'] === 'female' ? 'Bai' : 'Bhai';
            $name = $firstName . ' ' . $firstNameSuffix . ' ' . $fatherName . ' Bhai ' . $lastName;

            try {
                db_query(
                    $link,
                    "INSERT INTO `thalilist` (`ITS_No`, `NAME`, `CONTACT`, `WhatsApp`, `Email_ID`, `wingflat`, `society`, `sector`, `musaid`, `Transporter`, `Full_Address`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    "sssssssssss",
                    [$its, $name, $formValues['mobile'], $formValues['whatsapp'], $email, $formValues['wingflat'], $society, $sector, $musaid, $transporter, $full_address]
                );

                // Never surface raw SQL or mysqli_error() to the visitor —
                // this page has no login requirement at all, so a DB error
                // here was previously leaking query structure and driver
                // error details to anyone on the public internet.
                $msgvar = "Salaam " . e($firstName) . ' ' . strtolower($firstNameSuffix) . ",<br><br>New Registration form for Faiz ul Mawaid il Burhaniyah thali has been successfully submitted.<br>
                <b>Please contact Kalimi Mohalla Jamaat Office to start your thali.</b>";
                sendEmail([$email], 'New Registration Successful, Visit Faiz to start the thali', $msgvar, null, null, true);

                $msg = "Your registration has been successfully submitted. Please contact Kalimi Mohalla Jamaat Office to start your thali.";
            } catch (RuntimeException $e) {
                error_log('[registration/index.php] ' . $e->getMessage());
                $msg = "Sorry, something went wrong while saving your registration. Please try again in a moment or contact the Jamaat office.";
            }
        }
    }
}

include('../users/header.php'); ?>

<main id="main-content" class="content mt-4" tabindex="-1">
	<div class="container">
		<div class="row">
			<div class="col-12 offset-sm-1 col-sm-10 offset-lg-2 col-lg-8">
				<div class="card">
					<div class="card-body">
						<a href="/testfmb/index.php"><img class="img-fluid mx-auto d-block my-3" src="/testfmb/assets/img/logo.avif" alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" width="253" height="253" /></a>
						<hr>
						<?php if ($msg !== null) { ?>
							<div class="alert alert-info fade show" role="alert">
								<?php echo e($msg); ?>
							</div>
						<?php } ?>
						<h2 class="mb-4 text-center">Thaali Registration</h2>
						<form class="form-horizontal" method="post" autocomplete="off">
							<div class="mb-3 row">
								<label for="its" class="col-3 control-label">HOF ITS No</label>
								<div class="col-9">
									<input type="text" inputmode="numeric" class="form-control" id="its" name="its" pattern="[0-9]{8}" maxlength="8" value="<?php echo e($formValues['its']); ?>" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="firstname" class="col-3 control-label">First Name</label>
								<div class="col-9">
									<input type="text" class="form-control" id="firstname" name="firstname" pattern="[A-Za-z ]+" value="<?php echo e($formValues['firstname']); ?>" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="fathername" class="col-3 control-label">Father's/Husband's Name</label>
								<div class="col-9">
									<input type="text" class="form-control" id="fathername" name="fathername" pattern="[A-Za-z ]+" value="<?php echo e($formValues['fathername']); ?>" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="lastname" class="col-3 control-label">Last Name</label>
								<div class="col-9">
									<input type="text" class="form-control" id="lastname" name="lastname" pattern="[A-Za-z ]+" value="<?php echo e($formValues['lastname']); ?>" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="gender" class="col-3 control-label">Gender</label>
								<div class="col-9">
									<select class="form-select" id="gender" name="gender" required>
										<option value="">Select Gender</option>
										<option value="male" <?php echo ($formValues['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
										<option value="female" <?php echo ($formValues['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
									</select>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="mobile" class="col-3 control-label">Mobile Number</label>
								<div class="col-9">
									<input type="text" inputmode="numeric" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo e($formValues['mobile']); ?>" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="whatsapp" class="col-3 control-label">WhatsApp Number</label>
								<div class="col-9">
									<input type="text" inputmode="numeric" class="form-control" id="whatsapp" name="whatsapp" pattern="[0-9]{10}" maxlength="10" value="<?php echo e($formValues['whatsapp']); ?>">
								</div>
							</div>
							<div class="mb-3 row">
								<label for="email" class="col-3 control-label">Email Address</label>
								<div class="col-9">
									<input type="email" class="form-control" id="email" name="email" pattern="[a-z0-9._%+\-]+@gmail.com$" value="<?php echo e($formValues['email']); ?>" required='required'>
									<p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="wingflat" class="col-3 control-label">Flat No/House No</label>
								<div class="col-9">
									<input type="text" class="form-control" id="wingflat" name="wingflat" value="<?php echo e($formValues['wingflat']); ?>" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="society" class="col-3 control-label">Society/House Name</label>
								<div class="col-9">
									<?php $society_result = db_query($link, "SELECT DISTINCT `society` FROM `thalilist` WHERE `society` IS NOT NULL AND `society` != '' ORDER BY `society` ASC"); ?>
									<select class="form-select" id="society" name="society" required='required'>
										<option value="">Select Society/House Name</option>
										<?php while ($society_row = mysqli_fetch_assoc($society_result)) { ?>
											<option value="<?php echo e($society_row['society']); ?>" <?php echo ($society_row['society'] === $formValues['society']) ? 'selected' : ''; ?>><?php echo e($society_row['society']); ?></option>
										<?php } ?>
										<option value="Other" <?php echo ($formValues['society'] === 'Other') ? 'selected' : ''; ?>>Other</option>
									</select>
									<p class="help-block mb-0 text-danger text-end"><small>(If your society/house name is not in the list then please select other)</small></p>
								</div>
							</div>
							<div id="society_name_wrapper" class="mb-3 row" style="display:none;">
								<label for="society_name" class="col-3 control-label">Other Society/House Name</label>
								<div class="col-9">
									<input type="text" class="form-control" name="society_name" id="society_name_input" value="<?php echo e((string) ($_POST['society_name'] ?? '')); ?>" />
								</div>
							</div>
							<div id="society_address_wrapper" class="mb-3 row" style="display:none;">
								<label for="society_address" class="col-3 control-label">Other Society/House Address</label>
								<div class="col-9">
									<textarea class="form-control" name="society_address" id="society_address_input"><?php echo e((string) ($_POST['society_address'] ?? '')); ?></textarea>
								</div>
							</div>
							<div class="mb-3 row">
								<div class="col-9 offset-3">
									<button type="submit" class="btn btn-light" name='submit'>Submit</button>
								</div>
							</div>
						</form>
					</div> <!-- /container -->
				</div>

				<?php include('../users/footer.php'); ?>
