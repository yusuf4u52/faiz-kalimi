<?php
include('connection.php');
include('_authCheck.php');

$eventid=$_GET['eventid'];
$event = mysqli_fetch_assoc(mysqli_query($link,"SELECT * FROM events where id=$eventid"));
$total_registered_count = mysqli_num_rows(mysqli_query($link,"SELECT * FROM event_response where eventid=$eventid"));
$result=mysqli_query($link,"SELECT * FROM thalilist where id not in (select thaliid from event_response where eventid=$eventid) and Active in (0,1) and Thali is not null");
?>
<!DOCTYPE html>
<html>
<head>
	<title>Not Registered</title>
	<?php include('_head.php'); ?>
	<?php include('_bottomJS.php'); ?>
</head>
<body>
	<?php include('_nav.php'); ?>

	<!-- Modal Window to add friends -->

	<div class="container">
		<h3><?php echo $event['name'].' - '.$event['venue']; ?></h3>
		<h5><?php echo "Total registered count ".$total_registered_count; ?></h5>
		<h5><?php echo "Total not registered count " . mysqli_num_rows($result); ?></h5>
		<table class="table table-hover">
		  <thead>
		    <tr>
		      <th scope="col">Thali</th>
		      <th scope="col">ITS</th>
		      <th scope="col">Name</th>
		      <th scope="col">Mobile</th>
		      <th scope="col">Whatsapp</th>
		    </tr>
		  </thead>
		  <tbody>
			  <?php
		      while($values = mysqli_fetch_assoc($result))
		      {
	          ?>
		    <tr>
		      <td><?php echo $values['Thali']; ?></td>
		      <td><?php echo $values['ITS_No']; ?></td>
		      <td><?php echo $values['NAME']; ?></td>
		      <td><?php echo $values['CONTACT']; ?></td>
		      <td><a href="https://wa.me/91<?php echo $values['CONTACT']; ?>?text=Salaam%2C%0AYou%20have%20not%20yet%20registered%20for%20the%20OHBAT%20Majlis%20on%20Sunday%202nd%20Sep.%0APlease%20do%20it%20as%20soon%20as%20possible">WhatsApp</a></td>
		    </tr>
		    <?php } ?>
		  </tbody>
		</table>
	</div>
</body>
</html>