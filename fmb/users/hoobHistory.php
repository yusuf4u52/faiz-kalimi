<?php
error_reporting(0);
include('header.php');
include('navbar.php');
include('getHijriDate.php');
?>

<div class="card">
  <div class="card-body">
	<div class="table-responsive">
		<table class="table table-striped display" width="100%">
			<thead>
				<tr>
					<th>Date</th>
					<th>Hijri</th>
					<th>Receipt No</th>
					<th>Name</th>
					<th>Amount</th>
					<th>Pay Mode</th>
					<th>Transaction Id</th>
					<th>Takhmeen Year</th>
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
				echo "<td data-sort=" . strtotime($row['Date']) . ">" . date('d M Y', strtotime($row['Date'])) . "</td>";
            	echo "<td>" . getHijriFullDate($row['Date']) . "</td>";
				echo "<td>" . nl2br($row['Receipt_No']) . "</td>";
				echo "<td>" . nl2br($row['name']) . "</td>";
				echo "<td>" . nl2br($row['Amount']) . "</td>";
				echo "<td>" . nl2br($row['payment_type']) . "</td>";
				echo "<td>" . nl2br($row['transaction_id']) . "</td>";
				echo "<td>" . nl2br($row['takmeem_year']) . "</td>";
				echo "</tr>";
				}
				?>
			</tbody>
		</table>
	</div>
  </div>
</div>

<?php include('footer.php'); ?>
