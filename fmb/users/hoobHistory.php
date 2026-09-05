<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
include('header.php');
include('navbar.php');
require_once('helpers.php');
include('getHijriDate.php');

/**
 * Render one receipts table for the given table name and email.
 * $table is never user input (always one of the two literals below), so
 * it's safe to interpolate directly — table/column names can't be bound
 * as query parameters in mysqli.
 */
function render_receipts_table(mysqli $link, string $table, string $email): void
{
    $query = "SELECT r.* FROM `$table` r, thalilist t WHERE r.userid = t.id AND t.Email_ID = ? ORDER BY r.Date ASC";
    $result = db_query($link, $query, "s", [$email]);

    while ($row = mysqli_fetch_assoc($result)) {
        // NOTE: the original code ran stripslashes() on every field here —
        // a leftover from PHP's "magic quotes" feature, which was removed
        // in PHP 5.4. With magic quotes gone, that stripslashes() call was
        // silently corrupting any receipt data that happened to contain a
        // genuine backslash (e.g. in a name or a Windows-style path).
        echo "<tr>";
        echo "<td data-sort=" . strtotime($row['Date']) . ">" . e(date('d M Y', strtotime($row['Date']))) . "</td>";
        echo "<td>" . e(getHijriFullDate($row['Date'])) . "</td>";
        echo "<td>" . nl2br(e($row['Receipt_No'])) . "</td>";
        echo "<td>" . nl2br(e($row['name'])) . "</td>";
        echo "<td>" . nl2br(e((string) $row['Amount'])) . "</td>";
        echo "<td>" . nl2br(e($row['payment_type'])) . "</td>";
        echo "<td>" . nl2br(e($row['transaction_id'])) . "</td>";
        echo "<td>" . nl2br(e($row['takmeem_year'])) . "</td>";
        echo "</tr>";
    }
}
?>

<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Current Year Reciepts</h2>
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
					<?php render_receipts_table($link, 'receipts', $_SESSION['email']); ?>
				</tbody>
			</table>
		</div>
		<hr>
		<h2 class="mb-3">Previous Year Reciepts</h2>
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
					<?php render_receipts_table($link, 'receipts_1447', $_SESSION['email']); ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php include('footer.php'); ?>
