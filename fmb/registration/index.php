<?php
include('../users/connection.php');
require_once '../users/_sendMail.php';

if (isset($_POST['submit'])) {

	$its = mysqli_real_escape_string($link, $_POST['its']);
	$firstname = mysqli_real_escape_string($link, $_POST['firstname']);
	$fathername = mysqli_real_escape_string($link, $_POST['fathername']);
	$lastname = mysqli_real_escape_string($link, $_POST['lastname']);
	$mobile = mysqli_real_escape_string($link, $_POST['mobile']);
	$whatsapp = mysqli_real_escape_string($link, $_POST['whatsapp']);
	$email = mysqli_real_escape_string($link, $_POST['email']);
	$wingflat = mysqli_real_escape_string($link, $_POST['wingflat']);
	$society = mysqli_real_escape_string($link, $_POST['society']);

	$thalilist_query = "SELECT * FROM `thalilist` WHERE `ITS_No`='$its' OR `Email_ID` = '$email' OR `SEmail_ID` = '$email'";
	$thalilist_result = mysqli_query($link, $thalilist_query);
	if (mysqli_num_rows($thalilist_result) > 0) {
		$msg = "Your data already exist in the system and so you can login directly.";
	} else {
		$society = mysqli_real_escape_string($link, $_POST['society']);

		if ($society === 'Other') {
			$society_name = mysqli_real_escape_string($link, $_POST['society_name']);
			$society_address = mysqli_real_escape_string($link, $_POST['society_address']);

			$society = $society_name;
			$full_address = $society_address;

			$sector = '';
			$transporter = '';
			$musaid = '';
		} else {
			$thalidata = "Select Full_Address, musaid, sector, Transporter from `thalilist` where society='$society' limit 1";
			$thalidataresult = mysqli_query($link, $thalidata);
			if (mysqli_num_rows($thalidataresult) > 0) {
				$thalidatarow = mysqli_fetch_assoc($thalidataresult);
				$sector = $thalidatarow['sector'];
				$transporter = $thalidatarow['Transporter'];
				$musaid = $thalidatarow['musaid'];
				$full_address = $thalidatarow['Full_Address'];
			}
		}
		$name = $firstname . " " . $fathername . " " . $lastname;
		$sql = "INSERT INTO `thalilist` (`ITS_No`, `Name`, `CONTACT`, `WhatsApp`, `Email_ID`, `wingflat`, `society`, `sector`, `musaid`, `Transporter`, `Full_Address`) VALUES ('$its', '$name', '$mobile', '$whatsapp', '$email', '$wingflat', '$society', '$sector', '$musaid', '$transporter', '$full_address')";
		if (mysqli_query($link, $sql)) {
			$msg = "New record created successfully";
		} else {
			$msg = "Error: " . $sql . "<br>" . mysqli_error($link);
		}
		$msgvar = "Salaam " . $firstname . "bhai,<br><br>New Registration form for Faiz ul Mawaid il Burhaniyah thali has been successfully submitted.<br>
  		<b>Please contact Kalimi Mohalla Jamaat Office to start your thali.</b>";
		sendEmail([$email], 'New Registration Successful, Visit Faiz to start the thali', $msgvar, null, null, true);
		$msg = "Your registration has been successfully submitted. Please contact Kalimi Mohalla Jamaat Office to start your thali.";
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
						<?php if (isset($msg)) { ?>
							<div class="alert alert-info fade show" role="alert">
								<?php echo $msg; ?>
							</div>
						<?php } ?>
						<h2 class="mb-4 text-center">Thaali Registration</h2>
						<form class="form-horizontal" method="post" autocomplete="off">
							<div class="mb-3 row">
								<label for="its" class="col-3 control-label">HOF ITS No</label>
								<div class="col-9">
									<input type="number" class="form-control" id="its" name="its" pattern="[0-9]{8}" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="firstname" class="col-3 control-label">First Name</label>
								<div class="col-9">
									<input type="text" class="form-control" id="firstname" name="firstname" pattern="[A-Za-z ]+" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="fathername" class="col-3 control-label">Father's/Husband's Name</label>
								<div class="col-9">
									<input type="text" class="form-control" id="fathername" name="fathername" pattern="[A-Za-z ]+" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="lastname" class="col-3 control-label">Last Name</label>
								<div class="col-9">
									<input type="text" class="form-control" id="lastname" name="lastname" pattern="[A-Za-z ]+" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="mobile" class="col-3 control-label">Mobile Number</label>
								<div class="col-9">
									<input type="number" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="whatsapp" class="col-3 control-label">WhatsApp Number</label>
								<div class="col-9">
									<input type="number" class="form-control" id="whatsapp" name="whatsapp" pattern="[0-9]{10}">
								</div>
							</div>
							<div class="mb-3 row">
								<label for="email" class="col-3 control-label">Email Address</label>
								<div class="col-9">
									<input type="email" class="form-control" id="email" name="email" pattern="[a-z0-9._%+\-]+@gmail.com$" required='required'>
									<p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="wingflat" class="col-3 control-label">Flat No/House No</label>
								<div class="col-9">
									<input type="text" class="form-control" id="wingflat" name="wingflat" required='required'>
								</div>
							</div>
							<div class="mb-3 row">
								<label for="society" class="col-3 control-label">Society/House Name</label>
								<div class="col-9">
									<?php $society_query = "SELECT DISTINCT `Society` FROM `thalilist` WHERE `Society` IS NOT NULL AND `Society` != '' ORDER BY `Society` ASC";
									$society_result = mysqli_query($link, $society_query); ?>
									<select class="form-select" id="society" name="society" required='required'>
										<option value="">Select Society/House Name</option>
										<?php while ($society_row = mysqli_fetch_assoc($society_result)) { ?>
											<option value="<?php echo $society_row['Society']; ?>"><?php echo $society_row['Society']; ?></option>
										<?php } ?>
										<option value="Other">Other</option>
									</select>
									<p class="help-block mb-0 text-danger text-end"><small>(If your society/house name is not in the list then please select other)</small></p>
								</div>
							</div>
							<div id="society_name_wrapper" class="mb-3 row" style="display:none;">
								<label for="society_name" class="col-3 control-label">Other Society/House Name</label>
								<div class="col-9">
									<input type="text" class="form-control" name="society_name" id="society_name_input" />
								</div>
							</div>
							<div id="society_address_wrapper" class="mb-3 row" style="display:none;">
								<label for="society_address" class="col-3 control-label">Other Society/House Address</label>
								<div class="col-9">
									<textarea class="form-control" name="society_address" id="society_address_input"></textarea>
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