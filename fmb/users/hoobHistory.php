<?php
error_reporting(0);
include('header.php');
include('navbar.php');
?>

<div class="card">
  <div class="card-body">
	<table class="table table-striped display" width="100%">
	  <thead>
		<tr>
		  <th>Receipt No</th>
		  <th>Amount</th>
		  <th>Date</th>
		</tr>
	  </thead>
	  <tbody>
		<?php
		$query = "SELECT r.* FROM receipts r, thalilist t WHERE r.userid = t.id and t.Email_ID ='" . $_SESSION['email'] . "' ORDER BY Date ASC";
		$result = mysqli_query($link, $query);
		while ($row = mysqli_fetch_assoc($result)) {
		  foreach ($row as $key => $value) {
			$row[$key] = stripslashes($value);
		  }
		  echo "<tr>";
		  echo "<td>" . nl2br($row['Receipt_No']) . "</td>";
		  echo "<td>" . nl2br($row['Amount']) . "</td>";
		  echo "<td data-sort=" . strtotime($row['date']) . "> . date('d M Y', strtotime($row['date'])) . "</td>";
		  echo "</tr>";
		}
		?>
	  </tbody>
	</table>
  </div>
</div>

<?php include('footer.php'); ?>
