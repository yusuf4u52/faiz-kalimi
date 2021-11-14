<?php
include('_authCheck.php');
?>
<!DOCTYPE html>
<html lang="en">

<head><?php include('_head.php'); ?></head>

<body>
  <?php include('_nav.php'); ?>
  <div class="container">
    <!-- Forms
      ================================================== -->
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 id="forms">Pay Hoob</h2>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12">
          <div class="well bs-component">
            <form class="form-horizontal" action="checkout.php" method="POST">
              <fieldset>
                <div class="form-group">
                  <label for="hoobAmount" class="col-lg-2 control-label">Amount</label>
                  <div class="col-lg-10">
                    <input type="number" class="form-control" id="hoobAmount" placeholder="Amount" name="amount">
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-lg-10 col-lg-offset-2">
                    <button type="submit" class="btn btn-primary">Pay Now</button>
                  </div>
                </div>
              </fieldset>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include('_bottomJS.php'); ?>
</body>

</html>