<?php
include('connection.php');
include('_authCheck.php');

$query = "SELECT yearly_hub FROM thalilist where Email_id = '" . $_SESSION['email'] . "'";

$values = mysqli_fetch_assoc(mysqli_query($link, $query));

if (!empty($values['yearly_hub'])) {
  header("Location: index.php");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include('_head.php'); ?>
</head>

<body>

  <?php include('_nav.php'); ?>

  <div class="container">
    <div class="row">
      <h1 class="col-xs-12">Niyaaz takhmeen for full year from Shehrullah to Shabaan.</h1>
      <h4 class="col-xs-12">Please select the total niyaaz amount that you will contribute for the this year.</h4>
    </div>
    <div class="row"><a href='selectyearlyhub_action.php?option=1' class="col-xs-12 col-sm-10 col-md-6 col-lg-4 btn btn-primary" style='margin:5px'>24,000/-</a></div>
    <div class="row"><a href='selectyearlyhub_action.php?option=2' class="col-xs-12 col-sm-10 col-md-6 col-lg-4 btn btn-primary" style='margin:5px'>25,000/-</a></div>
    <div class="row"><a href='selectyearlyhub_action.php?option=3' class="col-xs-12 col-sm-10 col-md-6 col-lg-4 btn btn-primary" style='margin:5px'>26,000/-</a></div>
    <div class="row"><a href='selectyearlyhub_action.php?option=4' class="col-xs-12 col-sm-10 col-md-6 col-lg-4 btn btn-primary" style='margin:5px'>27,000/-</a></div>
    <div class="row"><a href='selectyearlyhub_action.php?option=5' class="col-xs-12 col-sm-10 col-md-6 col-lg-4 btn btn-primary" style='margin:5px'>53,000/-</a></div>

    <div class="row">
      <hr>
      <div class="form-group">
        <form method="post" action="selectyearlyhub_action.php">
          <label for="inputEmail" class="col-lg control-label">Other ( > 24,000/-)</label>
          <div class="col-lg">
            <input type="number" class="form-control" name="other_takhmeen" min="24000" />
          </div>
          <br>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
      </div>
    </div>


  </div>

  <?php include('_bottomJS.php'); ?>

  <div align="center">
    <a href="mailto:kalimimohallapoona@gmail.com">kalimimohallapoona@gmail.com</a><br><br>
  </div>
</body>

</html>