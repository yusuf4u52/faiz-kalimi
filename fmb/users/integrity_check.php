<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hub sanity test</title>
</head>
<body>

  <?php
  $sql = db_query($link, "SELECT SUM(`Amount`) FROM receipts");
  $row = mysqli_fetch_row($sql);
  $amount = $row[0];
  echo "Amount in Receipts " . e((string) $amount) . "";
  ?>
  <br>
  <?php
  $sql = db_query($link, "SELECT SUM(`Paid`) FROM thalilist");
  $row = mysqli_fetch_row($sql);
  $paid = $row[0];
  echo "Amount in Thalilist " . e((string) $paid) . "\n";
  ?>
  <br>
  <?php
  if ($amount == $paid) {
      echo "Both matches so we are good";
  } else {
      echo "Something is wrong as numbers above dont match up";
  }
  ?>
  <br>
  <?php
  db_query($link, "SET SQL_BIG_SELECTS=1");

  $sql = db_query(
      $link,
      "SELECT l.Receipt_No + 1 AS start, MIN(fr.Receipt_No) - 1 AS stop
       FROM receipts AS l
       LEFT OUTER JOIN receipts AS r ON l.Receipt_No = r.Receipt_No - 1
       LEFT OUTER JOIN receipts AS fr ON l.Receipt_No < fr.Receipt_No
       WHERE r.Receipt_No IS NULL AND fr.Receipt_No IS NOT NULL
       GROUP BY l.Receipt_No, r.Receipt_No"
  );
  echo "<br>Missing Receipts are -<br>";

  while ($row = mysqli_fetch_assoc($sql)) {
      echo "From " . e((string) $row["start"]) . " - To: " . e((string) $row["stop"]) . "<br>";
  }
  ?>
</body>
</html>
