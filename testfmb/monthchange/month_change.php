<?php
include('../users/header.php');
include('../users/navbar.php');
if ($_POST) {
	$query = file_get_contents("month_change.sql");
	$query = str_replace('%month%', $_POST['year'], $query);
	mysqli_multi_query($link, $query) or die(mysqli_error($link));
	$msg = "Success";
}
?>
<div class="card">
	<div class="card-body">
		<?php if (isset($msg)) { ?>
			<div class="alert alert-success" role="alert">
				<?php echo $msg; ?>
			</div>
		<?php } ?>
		<h2 class="mb-3">Select previous year to be appended to sheet</h2>
		<form method="POST">
			<div class="mb-3">
				<select name="year" class="form-select" required>
					<?php
					for ($i = 1438; $i <= 1450; $i++) { ?>
						<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="mb-3">
				<input class="btn btn-light" type="submit" value="Submit">
			</div>
		</form>
	</div>
</div>