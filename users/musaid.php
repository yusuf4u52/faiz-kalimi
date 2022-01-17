<?php
include('_authCheck.php');
if ($_POST) {
	if (!empty($_POST['date']) && !empty($_POST['rs']) && !empty($_POST['comment'])) {
		echo "1sst";
		mysqli_query($link, "INSERT INTO `hub_commitment` (`author_id`, `thali`, `comments`, `commit_date`, `rs`) VALUES ('" . $_SESSION['thaliid'] . "', '" . $_POST['Thali'] . "', '" . mysqli_real_escape_string($link, $_POST['comment']) . "', '" . $_POST['date'] . "', '" . $_POST['rs'] . "')") or die(mysqli_error($link));
	} else if (!empty($_POST['date']) && !empty($_POST['rs'])) {
		echo "2ndt";
		mysqli_query($link, "INSERT INTO `hub_commitment` (`author_id`,`thali`, `commit_date`, `rs`) VALUES ('" . $_SESSION['thaliid'] . "', '" . $_POST['Thali'] . "', '" . $_POST['date'] . "', '" . $_POST['rs'] . "')") or die(mysqli_error($link));
	} else if (!empty($_POST['comment'])) {
		echo "3rd";
		mysqli_query($link, "INSERT INTO `hub_commitment` (`author_id`,`thali`, `comments`) VALUES ('" . $_SESSION['thaliid'] . "', '" . $_POST['Thali'] . "', '" . mysqli_real_escape_string($link, $_POST['comment']) . "')") or die(mysqli_error($link));
	}
	header("Location: musaid.php");
	exit;
}

$current_year = mysqli_fetch_assoc(mysqli_query($link, "SELECT value FROM settings where `key`='current_year'"));
$previous_year = ((int) $current_year['value']) - 1;

$previous_thalilist = "thalilist_" . $previous_year;
$previous_receipts = "receipts_" . $previous_year;

$max_days = mysqli_fetch_row(mysqli_query($link, "SELECT MAX(thalicount) as max FROM `thalilist`"));
$val = mysqli_query($link, "SELECT MAX(thalicount) as max FROM `$previous_thalilist`");
if ($val !== FALSE) {
	$max_days_previous = mysqli_fetch_row($val);
} else {
	$max_days_previous = 1;
}


if (isset($_SESSION['role']) && ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin')) {
	$musaid_list = mysqli_fetch_all(mysqli_query($link, "SELECT `id`,`email`,`username` FROM `users` WHERE `role` in ('musaid','admin','superadmin')"), MYSQLI_ASSOC);
} else {
	$musaid_list = array(
		array(
			'id' => 0,
			'username' => $_SESSION['email'],
			'email' => $_SESSION['email']
		)
	);
}
?>
<!DOCTYPE html>
<html>

<head>
	<title>Musaid Home</title>
	<?php include('_head.php'); ?>
	<style type="text/css">
		.panel-body {
			overflow-x: scroll;
		}
	</style>
</head>

<body>
	<?php include('_nav.php'); ?>
	<div class="container">
		<div class="row">
			<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
				<?php
				foreach ($musaid_list as $musaid) {
					$result = mysqli_query($link, "SELECT * FROM thalilist where `Paid %` < 70 and Active in (0,1) and musaid='" . $musaid['email'] . "' order by `Paid %`");
					$thali_details = mysqli_fetch_all($result, MYSQLI_ASSOC);
					$musaid_thali_count = count($thali_details);
					if ($musaid_thali_count > 0) {
				?>
						<div class="panel panel-default">
							<div class="panel-heading" role="tab" id="heading<?php echo $musaid['id']; ?>">
								<h4 class="panel-title">
									<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $musaid['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $musaid['id']; ?>">
										<?php echo $musaid['username']; ?> - (<?php echo $musaid_thali_count; ?>)
									</a>
								</h4>
							</div>
							<div id="collapse<?php echo $musaid['id']; ?>" class="panel-collapse collapse <?php if (count($musaid_list) == 1) echo "in"; ?>" role="tabpanel" aria-labelledby="heading<?php echo $musaid['id']; ?>">
								<div class="panel-body">
									<table class="table table-hover">
										<thead>
											<tr>
												<th scope="col">Thali#</th>
												<th scope="col">Tiffin#</th>
												<th scope="col">Action</th>
												<th scope="col">Active</th>
												<th scope="col">Name</th>
												<th scope="col">Total Hub</th>
												<th scope="col">Pending</th>
												<th scope="col">Paid %</th>
												<th scope="col">Commited Date/RS</th>
												<th scope="col">Comments</th>
												<th scope="col">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($thali_details as $values) {
												$commit = mysqli_query($link, "SELECT concat(commit_date, ' / ', rs) FROM hub_commitment where rs !=0 and thali='" . $values['Thali'] . "'");
												$all_data = mysqli_fetch_all($commit);
												$all_dates = array_column($all_data, 0);
												$comments = mysqli_fetch_all(mysqli_query($link, "SELECT `hub_commitment`.`comments`, `hub_commitment`.`timestamp`, `thalilist`.`Email_ID` FROM hub_commitment INNER JOIN `thalilist` on `hub_commitment`.`author_id` = `thalilist`.`id` where comments is not null and `hub_commitment`.`thali`='" . $values['Thali'] . "' ORDER BY `timestamp` DESC"), MYSQLI_ASSOC);
											?>
												<form method="post">
													<tr>
														<input type='hidden' value='<?php echo $values['Thali']; ?>' name='Thali'>
														<td>
															<?php echo $values['Thali']; ?>
															&nbsp;
															<a data-toggle="modal" href="#details-<?php echo $values['Thali']; ?>">
																<img src="images/view.png" style="width:20px;height:20px;">
															</a>
														</td>
														<td><?php echo $values['tiffinno']; ?></td>
														<td>
															<a target="_blank" href="https://wa.me/91<?php echo $values['WhatsApp']; ?>?text=Salaam Bhai">WhatsApp</a>
															<!-- <?php if ($values['Active'] == '1') { ?>
																<a href="#" data-key="startstopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="0">Stop Thaali</a>
															<?php } else { ?>
																<a href="#" data-key="startstopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="1">Start Thaali</a>
															<?php } ?> -->
														</td>
														<td><?php echo $values['Active'] ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>'; ?></td>
														<td><?php echo $values['NAME']; ?></td>
														<td><?php echo $values['yearly_hub']; ?></td>
														<td><?php echo $values['Total_Pending']; ?></td>
														<td><?php echo $values['Paid %']; ?></td>
														<td><?php echo "<pre>" . implode(",\n", $all_dates) . "</pre>"; ?><input type="text" name="date" class="datepicker" autocomplete="off"><input type="number" name="rs"></td>
														<td>
															<?php
															foreach ($comments as $comment) {
															?>
																<?php echo  $comment['comments']; ?><br>
																<span style="color: grey">- <?php echo explode('@', $comment['Email_ID'])[0]; ?> <?php echo date('d/m/Y', strtotime($comment['timestamp'])); ?></span>
																<br></br>
															<?php
															}
															?>

															<textarea name="comment" class="form-control" rows="3"></textarea>
														</td>
														<td><input type='submit' value="Save"></td>
													</tr>
												</form>
											<?php } ?>
										</tbody>
									</table>
									<?php
									foreach ($thali_details as $values) {
										include('_thali_details_musaid.php');
									}
									?>
								</div>
							</div>
						</div>
				<?php
					}
				}
				?>
			</div>
		</div>
	</div>
	<?php include('_bottomJS.php'); ?>
	<script>
		$(function() {
			$(".datepicker").datepicker({
				dateFormat: 'yy-mm-dd'
			});

			$('[data-key="startstopthaali"]').click(function() {
				if (confirm("Are you sure?")) {
					stopThali_admin($(this).attr('data-thali'), $(this).attr('data-active'), false, false, function(data) {
						if (data === 'success') {
							location.reload();
						}
					});
				}
			});
		});
	</script>
</body>

</html>