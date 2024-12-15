<?php
error_reporting(0);
include('_authCheck.php');
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
        <div class="col-lg-12">
          <table class="table table-striped table-hover">
          <tr>
          <td><b>Receipt No</b></td>
          <td><b>Amount</b></td>
          <td><b>Date</b></td>
          </tr>
          <?php
         
          $query = "SELECT r.* FROM receipts r, thalilist t WHERE r.userid = t.id and t.Email_ID ='".$_SESSION['email']."' ORDER BY Date ASC";
          $result = mysqli_query($link,$query);
          while($row = mysqli_fetch_assoc($result)){ 
          foreach($row AS $key => $value) { $row[$key] = stripslashes($value); } 
            echo "<tr>";  
            echo "<td>" . nl2br( $row['Receipt_No']) . "</td>";  
            echo "<td>" . nl2br( $row['Amount']) . "</td>";  
            echo "<td class=\"hijridate\">" . nl2br( $row['Date']) . "</td>";  
            echo "</tr>"; 
          } 
          ?>
          </table>
        </div>
      </div>
    </div>
    <?php include('_bottomJS.php'); ?>
  </body>
</html>

