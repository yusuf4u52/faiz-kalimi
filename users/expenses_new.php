<?php
include('connection.php');
include('_authCheck.php');
error_reporting(0);

$months = array(
  '09' => 'Ramazan',
  '10' => 'Shawwal',
  '11' => 'Zilqad',
  '12' => 'Zilhaj',
  '01' => 'Moharram',
  '02' => 'Safar',
  '03' => 'RabiulAwwal',
  '04' => 'RabiulAkhar',
  '05' => 'JamadalAwwal',
  '06' => 'JamadalAkhar',
  '07' => 'Rajab',
  '08' => 'Shaban'
);

$fmt = numfmt_create('en_IN', NumberFormatter::CURRENCY);
$fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

?>

<html>

<head>
  <?php include('_head.php'); ?>
</head>

<body>
  <?php include('_nav.php'); ?>
  <form align="center" method="POST">
    <select name="year">
      <?php
      for ($i = 1438; $i <= 1450; $i++) { ?>
        <option value="<?php echo $i; ?>" <?php if ($_POST['year'] == $i) echo "selected"; ?>><?php echo $i - 1 . ' - ' . $i; ?></option>
      <?php } ?>
    </select>
    <input type="submit" value="Submit">
  </form>
  <?php
  $previous_year = $_POST['year'] - 1;
  if (!empty($_POST['year'])) {
    $result = mysqli_query($link, "SELECT value FROM settings where `key`='current_year'");
    $current_year = mysqli_fetch_assoc($result);

    $result = mysqli_query($link, "SELECT value FROM settings where `key`='cash_in_hand_" . $previous_year . "'");
    $previous_balance = mysqli_fetch_assoc($result);

    if ($current_year['value'] == $_POST['year']) {
      $thalilist_tablename = "thalilist";
      $account_tablename = "account";
      $receipts_tablename = "receipts";
      $niyaz_tablename = "niyaz";
      $zabihat_tablename = "zabihat";
      $ashara_tablename = "ashara";
      $sherullah_tablename = "sherullah";
      $voluntary_tablename = "voluntary";
    } else {
      $thalilist_tablename = "thalilist_" . $_POST['year'];
      $account_tablename = "account_" . $_POST['year'];
      $receipts_tablename = "receipts_" . $_POST['year'];
      $niyaz_tablename = "niyaz_" . $_POST['year'];
      $zabihat_tablename = "zabihat_" . $_POST['year'];
      $ashara_tablename = "ashara_" . $_POST['year'];
      $sherullah_tablename = "sherullah_" . $_POST['year'];
      $voluntary_tablename = "voluntary_" . $_POST['year'];
    }

    foreach ($months as $key => $month) {
      $sf_breakup = mysqli_query($link, "SELECT * FROM $account_tablename where Month = '" . $month . "' and Date > '2021-04-11'") or die(mysqli_error($link));
  ?>
      <div class="modal" id="sfbreakup-<?php echo $month; ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title">Expense Breakdown</h4>
            </div>
            <div class="modal-body">
              <table class="table table-striped table-hover table-responsive">

                <thead>

                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                    <th>Month</th>
                  </tr>
                </thead>

                <tbody>

                  <?php
                  while ($valuesnew = mysqli_fetch_assoc($sf_breakup)) {
                  ?>
                    <tr>
                      <td><?php echo $valuesnew['Date']; ?></td>
                      <td><?php echo $valuesnew['Type']; ?></td>
                      <td><?php echo $valuesnew['Amount']; ?></td>
                      <td><?php echo $valuesnew['Remarks']; ?></td>
                      <td><?php echo $valuesnew['Month']; ?></td>
                    </tr>
                  <?php } ?>

                </tbody>
              </table>


            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>





    <div class="modal" id="myModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Enter the amount</h4>
          </div>
          <div class="modal-body">
            <div id="hisabform">
              <input class="form-control gregdate" type="text" name="sf_amount_date" value="<?php echo date("Y-m-d") ?>" /><br>
              <input class="form-control" type="number" name="Amount" placeholder="Amount" /><br>
              <select class="form-control" name="salary">
                <option value='Cash'>Cash</option>
                <option value='Zabihat'>Zabihat</option>
                <option value='Manager Salary'>Manager Salary</option>
                <option value='Cook Salary'>Cook Salary</option>
                <option value='Light Bill'>Light Bill</option>
                <option value='Rent'>Rent</option>
                <option value='Aapa'>Aapa</option>
                <?php
                $result1 = mysqli_query($link, "SELECT Name FROM transporters where Name!='Pick Up'");
                while ($values1 = mysqli_fetch_assoc($result1)) {
                ?>
                  <option value='<?php echo $values1['Name']; ?>'><?php echo $values1['Name']; ?></option>
                <?php
                }
                ?>
                <option value='Others'>Others</option>
              </select><br>
              <input type="hidden" name="Month" />
              <input type="hidden" name="tablename" value="<?php echo $account_tablename; ?>" />
              <input class="form-control" type="text" placeholder="Remarks" name="desc" /><br>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal" name="cancel">Close</button>
            <button type="button" class="btn btn-primary" name="save">Save changes</button>
          </div>
        </div>
      </div>
    </div>




    <div class="container">
      <table class="table table-striped table-hover table-responsive table-bordered">
        <thead>
          <tr>
            <td colspan='8'></td>
            <td><strong>Previous Year Cash</strong></td>
            <td><strong><?php echo numfmt_format_currency($fmt, $previous_balance['value'], "INR"); ?></strong></td>
            <td colspan='1'></td>
          </tr>
          <tr>
            <th>Months</th>
            <th>FMB Hoob</th>
            <th>Niyaz</th>
            <th>Zabihat</th>
            <th>Ashara</th>
            <th>Sherullah</th>
            <th>Voluntary</th>
            <th>Total Income</th>
            <th>Total Expense</th>
            <th>Total Savings</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>

          <?php

          $result3 = mysqli_query($link, "SELECT value FROM settings where `key`='zabihat_" . $_POST['year'] . "'");
          $zab_maula = mysqli_fetch_assoc($result3);

          $result4 = mysqli_query($link, "SELECT SUM(Zabihat) as Amount FROM $thalilist_tablename");
          $zab_students = mysqli_fetch_assoc($result4);

          $result5 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $account_tablename where Type = 'Zabihat'");
          $zab_used = mysqli_fetch_assoc($result5);

          $yearly_total_savings = $zab_maula['value'] + $previous_balance['value'];

          foreach ($months as $key => $value) {
            $key == $key + 1;
            $result = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $receipts_tablename where Date like '%-$key-%' and Date > '1442-08-29'");
            $hub_received = mysqli_fetch_assoc($result);

            $result6 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $niyaz_tablename where Date like '%-$key-%' and Date > '1442-08-29'");
            $niyaz_received = mysqli_fetch_assoc($result6);

            $result7 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $zabihat_tablename where Date like '%-$key-%' and Date > '1442-08-29'");
            $zabihat_received = mysqli_fetch_assoc($result7);

            $result8 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $ashara_tablename where Date like '%-$key-%' and Date > '1442-08-29'");
            $ashara_received = mysqli_fetch_assoc($result8);

            $result9 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $sherullah_tablename where Date like '%-$key-%' and Date > '1442-08-29'");
            $sherullah_received = mysqli_fetch_assoc($result9);

            $result10 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $voluntary_tablename where Date like '%-$key-%' and Date > '1442-08-29'");
            $voluntary_received = mysqli_fetch_assoc($result10);

            $result1 = mysqli_query($link, "SELECT SUM(Amount) as Amount FROM $account_tablename where Month = '$value' and Date > '2021-04-11'");
            $cash_paid = mysqli_fetch_assoc($result1);

            $yearly_total_savings += $hub_received['Amount'] + $niyaz_received['Amount'] + $zabihat_received['Amount'] + $ashara_received['Amount'] + $sherullah_received['Amount'] + $voluntary_received['Amount'] - $cash_paid['Amount'];

          ?>

            <tr>
              <td><?php echo $value; ?></td>
              <td><?php echo numfmt_format_currency($fmt, $hub_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $niyaz_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $zabihat_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $ashara_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $sherullah_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $voluntary_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $hub_received['Amount'] + $niyaz_received['Amount'] + $zabihat_received['Amount'] + $ashara_received['Amount'] + $sherullah_received['Amount'] + $voluntary_received['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $cash_paid['Amount'], "INR"); ?></td>
              <td><?php echo numfmt_format_currency($fmt, $yearly_total_savings, "INR"); ?></td>
              <td><a href="#" data-key="payhisab" data-month="<?php echo $value; ?>"><img src="images/add.png" style="width:20px;height:20px;"></a>&nbsp;
                <a data-key="Monthview" data-month="<?php echo $value; ?>" data-toggle="modal" href="#sfbreakup-<?php echo $value; ?>"><img src="images/view.png" style="width:20px;height:20px;"></a>
              </td>
            </tr>
          <?php }
          mysqli_query($link, "UPDATE settings set value ='" . $yearly_total_savings . "' where `key`= 'cash_in_hand_" . $_POST['year'] . "'") or die(mysqli_error($link));
          ?>
          <tr>
            <td colspan='8'></td>
            <td><strong>Cash In Hand</strong></td>
            <td><strong><?php echo numfmt_format_currency($fmt, $yearly_total_savings, "INR"); ?></strong></td>
            <td colspan='1'></td>
          </tr>
        </tbody>
      </table>

      <table class="table table-striped table-hover table-responsive table-bordered">
        <thead>
          <tr>
            <th>Zabihat Maula(TUS)</th>
            <th>Zabihat Students</th>
            <th>Used</th>
            <th>Remaining</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $zab_maula['value']; ?></td>
            <td><?php echo $zab_students['Amount']; ?></td>
            <td><?php echo $zab_used['Amount']; ?></td>
            <td><?php echo $zab_maula['value'] + $zab_students['Amount'] - $zab_used['Amount']; ?></td>
          </tr>
        </tbody>
      </table>
    <?php } ?>

    </div>
    <?php include('_bottomJS.php'); ?>
    <script>
      $(function() {
        $(function() {
          var hisabform = $('#myModal');
          hisabform.hide();
          $('[data-key="payhisab"]').click(function() {
            $('[name="Month"]', hisabform).val($(this).attr('data-month'));
            hisabform.show();
          });
          $('[name="save"]').click(function() {
            var data = '';
            $('input[type!="button"],select', hisabform).each(function() {
              data = data + $(this).attr('name') + '=' + $(this).val() + '&';
            });
            $.ajax({
              method: 'post',
              url: '_payhisab_new.php',
              async: 'false',
              data: data,
              success: function(data) {
                if (data == 'success') {
                  hisabform.hide();
                  location.reload();
                  // } else if(data == 'DuplicateReceiptNo') {
                  //   alert('Receipt number already exists in database');
                } else {
                  alert('Update failed. Please do not add receipt again unless you check system values properly');
                }
              },
              error: function() {
                alert('Try again');
              }
            });
          });

          $('[name="cancel"]').click(function() {
            hisabform.hide();
          });



        });
      });
    </script>
</body>

</html>