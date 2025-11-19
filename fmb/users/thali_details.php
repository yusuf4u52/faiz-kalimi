<?php
include('header.php');
include('navbar.php');

$transporters = mysqli_query($link , "SELECT * FROM transporters WHERE `Name` = '" . $values['Transporter'] . "'") or die(mysqli_error($link));
$transvalues = $transporters->fetch_assoc();
?>

<div class="card">
  <div class="card-body">
	<h2 class="mb-3">Thali Details</h2>
	<ul class="list-group list-group-flush">
	  <li class="list-group-item">
		<div class="fw-bold">Sabeel Number</div>
		<?php echo $values['Thali']; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Tiffin Number</div>
		<?php echo $values['tiffinno']; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Thali Type</div>
		<?php echo $values['thalisize']; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">HOF ITS No</div>
		<?php echo $values['ITS_No']; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Name</div>
		<?php echo $values['NAME']; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Mobile Number</div>
		<?php echo $values['CONTACT']; ?>
	  </li>
	  <li class="list-group-item">
        <div class="fw-bold">Email Address</div>
        <a href="mailto:<?php echo $values['Email_ID']; ?>"><?php echo $values['Email_ID']; ?></a> <?php if(!empty($values['SEmail_ID'])) : ?>| <a
          href="mailto:<?php echo $values['SEmail_ID']; ?>"><?php echo $values['SEmail_ID']; ?></a> <?php endif; ?>
      </li>
	  <?php if ($musaid_details) { ?>
		<li class="list-group-item">
		  <div class="fw-bold">Musaid</div>
		  <?php echo $musaid_details['NAME']; ?> | <a 
				href="https://wa.me/+91<?php echo $musaid_details['CONTACT']; ?>"><?php echo $musaid_details['CONTACT']; ?></a>
		</li>
	  <?php } ?>
	  <li class="list-group-item">
		<div class="fw-bold">Previous Due</div>
		<?php echo '₹ '. $values['Previous_Due']; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Current Year Takhmeen</div>
		<?php echo '₹ '. $values['yearly_hub']; ?>
	  </li>
	  <li class="list-group-item">
        <div class="fw-bold">Hub Pending</div>
        ₹<?php echo $values['Total_Pending'] + $values['Paid']; ?> -
        ₹<?php echo $values['Paid']; ?> = ₹<?php echo $values['Total_Pending']; ?>
      </li>
	  <li class="list-group-item">
		<div class="fw-bold">Is Active?</div>
		<p class="list-group-item-text">
		  <?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Transporter</div>
		<p class="list-group-item-text">
		  <?php echo  $values['Transporter']; ?> | <a
				href="https://wa.me/+91<?php echo $transvalues['Mobile']; ?>"><?php echo $transvalues['Mobile']; ?></a>
		</p>
	  </li>
	  <li class="list-group-item">
		<div class="fw-bold">Address</div>
		<p class="list-group-item-text"><?php echo $values['Full_Address']; ?></p>
	  </li>

	  <?php
	  if ($values['Active'] == 1) {
		?>
		<li class="list-group-item">
		  <div class="fw-bold">Start Date</div>
		  <p class="list-group-item-text hijridate"><?php echo $values['Thali_Start_Date']; ?></p>
		</li>

		<?php
	  } else {
		?>
		<li class="list-group-item">
		  <div class="fw-bold">Stop Date</div>
		  <p class="list-group-item-text hijridate"><?php echo $values['Thali_Stop_Date']; ?></p>
		</li>
	  <?php } ?>
	</ul>
  </div>
</div>

<?php include('footer.php'); ?>