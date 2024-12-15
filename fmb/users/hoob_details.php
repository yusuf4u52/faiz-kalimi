<?php
include('header.php');
include('navbar.php');

if (!empty($values['yearly_hub'])) {
  $sql = mysqli_query($link, "select miqat_date,miqat_description from sms_date");
  $miqaatslist = mysqli_fetch_all($sql);
}
?>
<div class="content mt-5">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <h2 class="mb-3">Hoob Details</h2>
            <div class="Payment-details text-center mb-3">
              <img class="img-fluid mx-auto d-block mb-3" src="assets/img/fmb-account.png" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla) Acoount" width="900" height="548" />
              <h5 class="mb-3 text-center">Your total takhmeen for this year</h5>
              <h1 class="mb-3 text-center" style="color:#3C5A05;"><i class="bi bi-currency-rupee"></i><?php echo $values['yearly_hub']; ?></h1>
              <a class="btn btn-light btn-lg mb-3" href="upi://pay?pa=dbjt-fmb-kalimi@ybl&pn=D B J T TRUST K M POONA - FMB&cu=INR" id="__UPI_BUTTON__">Pay</a>
            </div>
            <h6 class="mb-3">The niyaaz amount will be payable throughout the year on the following miqaats. If possible
              do contribute the whole amount in Lailatul Qadr</h6>
            <ol>
              <?php foreach ($miqaatslist as $miqaat) { ?>
                <li><?php echo $miqaat['1']; ?></li>
              <?php } ?>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>