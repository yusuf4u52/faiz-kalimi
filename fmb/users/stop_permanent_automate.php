<?php
include('connection.php');

$query = mysqli_query($link,"SELECT c.Thali, t.id, MAX(c.datetime) as last_activity from change_table as c inner join thalilist as t on c.Thali = t.Thali WHERE t.Active=0 GROUP BY c.Thali HAVING last_activity < DATE_SUB(now(), INTERVAL 6 MONTH)") or die(mysqli_error($link));


while($row = mysqli_fetch_assoc($query))
{
	mysqli_query($link,"INSERT INTO change_table (`Thali`,`userid`, `Operation`) VALUES ('".$row['Thali']."','".$row['id']."','Stop Permanent')") or die(mysqli_error($link));
 	mysqli_query($link,"UPDATE thalilist set Active='2', `old_thali` = `Thali`, `Thali` = NULL WHERE Thali = '".$row['Thali']."'") or die(mysqli_error($link));
}
echo "Stop permenant script succesfuly completed"
?> 