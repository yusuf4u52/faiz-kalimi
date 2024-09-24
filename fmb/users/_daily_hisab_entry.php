<?php
include('connection.php');
include('_authCheck.php');

$result=mysqli_query($link,"SELECT dh.*,SUM(dhi.amount) as total_amount FROM daily_hisab as dh INNER JOIN daily_hisab_items as dhi on dh.`date` = dhi.`date` group by `dhi`.`date` order by `dh`.`date` DESC") or die(mysqli_error($link));

$result2=mysqli_fetch_assoc(mysqli_query($link,"SELECT SUM(amount) as total FROM sf_hisab where type = 'Dr'"));
$result3=mysqli_fetch_assoc(mysqli_query($link,"SELECT SUM(Amount) as total FROM account where Type = 'Cash' AND Date >= '1438-02-01'"));
$spent = $result2['total'];
$cash = $result3['total'];

$cashinhand = $cash - $spent;

?>

<html>
<head>
<?php include('_head.php'); ?>
</head>
<body>
<?php include('_nav.php'); ?>
<div class="container">
<button type="button" class="btn btn-primary" data-target="#adddish" data-toggle="modal">Add Dish</button>
<button type="button" class="btn btn-primary" data-target="#additems" data-toggle="modal">Add Items</button>
<button type="button" class="btn btn-primary" data-target="#sfhisab" data-toggle="modal">SF Purchases</button><br><br>

  <table class="table table-striped table-hover" id="my-table">
  <thead>
    <tr>
      <th>Date</th>
      <th>Dish with Roti</th>
      <th>Dish with Rice</th>
      <th>Thali count</th>
      <th>Total amount</th>
      <th>Per thali cost</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $dates = array();
    while($values = mysqli_fetch_assoc($result))
                    {
    $dates[] = $values['date'];  
                      ?>
    <tr>
      <td><?php echo $values['date']; ?></td>
      <td><?php echo $values['dish_with_roti']; ?></td>
      <td><?php echo $values['dish_with_rice']; ?></td>
      <td><?php echo $values['thalicount']; ?></td>
      <td><?php echo $values['total_amount']; ?></td>
      <td><?php echo round((int)$values['total_amount'] / (int)$values['thalicount']); ?></td>
      <td><button type="button" class="btn btn-primary" data-target="#detailed-<?php echo $values['date']; ?>" data-toggle="modal">View Details</button></td>
    </tr>
    <?php } ?>
  </tbody>
</table>

</div>

<?php
    foreach($dates as $value)
                    {
                      ?>
    <div class="modal" id="detailed-<?php echo $value; ?>">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo $value; ?></h4>
      </div>
      <div class="modal-body">
      <!-- Detailed hisab -->
        <table class="table table-striped table-hover ">
        <thead>
          <tr>
            <th>Item</th>
            <th>Quantity</th>
            <th>Rate(Rs)<th>
            <th>Amount(Rs)</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result1=mysqli_query($link,"SELECT * FROM daily_hisab_items where `date`='".$value."'") or die(mysqli_error($link));
          while($values1 = mysqli_fetch_assoc($result1))
                          {
                            ?>
          <tr>
            <td><?php echo $values1['items']; ?></td>
            <td><?php 
                  if($values1['quantity']==""){echo "1 unit";}
                  echo $values1['quantity']; ?></td>
            <td><?php 
                    if(preg_match("[500\s*gm|1\/2\s*kg]", $values1['quantity'])){$test=0.5;}
                    else {$test=preg_replace("([A-Za-z|\s+A-Za-z]|A-Za-z\s)", "", $values1['quantity']);}
                    if($test==""){ $test="1"; }
                    echo  round($values1['amount']/$test,2) ;?><td>
            <td><?php echo $values1['amount']; ?></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      <!-- detailed hisab end -->
    </div>
  </div>
</div>
</div>
    <?php } ?>

<div class="modal" id="adddish">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Add Dish</h4>
      </div>
      <div class="modal-body">
  
  <form class="form-horizontal" method="post" action="savedish.php">
  <fieldset>
    <div class="form-group">
      <label for="inputEmail" class="col-lg-3 control-label">Date</label>
      <div class="col-lg-6">
        <input type="text" class="form-control gregdate" name="date1" value="<?php echo date("Y-m-d") ?>"/>
      </div>
    </div>
    <div class="form-group">
      <label for="thalicount" class="col-lg-3 control-label">Thali Count</label>
      <div class="col-lg-6">
        <input type="number" class="form-control" name="thalicount">
      </div>
    </div>
    <div class="form-group">
      <label for="dishroti" class="col-lg-3 control-label">Dish with Roti</label>
      <div class="col-lg-6">
        <input type="text" class="form-control" name="dishroti">
      </div>
    </div>
    <div class="form-group">
      <label for="dishrice" class="col-lg-3 control-label">Dish with Rice</label>
      <div class="col-lg-6">
        <input type="text" class="form-control" name="dishrice">
      </div>
    </div>
    </fieldset>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
      </form>
    </div>
  </div>
</div>
</div>

<div class="modal" id="additems">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Add Items</h4>
      </div>
      <div class="modal-body">

<form method="post" action="saveitems.php">
<fieldset>
<div class="form-group">
      <label for="inputEmail" class="col-lg-3 control-label">Date</label>
      <div class="col-lg-6">
        <input type="text" class="form-control col-lg-6 gregdate" name="date1" value="<?php echo date("Y-m-d") ?>">
      </div>
</div>
<br><br>
    <div class="form-group col-xs-4 col-md-4">
        <label for="item" class="control-label">Item</label>
        <input type="text" value='' class="form-control" id="item" name="row[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row1[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row2[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row3[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row4[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row5[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row6[]">
    </div>
    <div class="form-group col-xs-4 col-md-4">
        <label for="quantity" class="control-label">Quantity</label>
        <input type="text" value='' class="form-control" id="quantity" name="row[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row1[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row2[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row3[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row4[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row5[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row6[]">
    </div>
    <div class="form-group col-xs-4 col-md-4">
        <label for="amount" class="control-label">Amount</label>
        <input type="number" value='' class="form-control" id="amount" name="row[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row1[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row2[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row3[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row4[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row5[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row6[]">

    </div>
    </fieldset>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
      </form>
    </div>
  </div>
</div>
</div>



<div class="modal" id="sfhisab">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Add Items &nbsp (Cash In Hand | <?php echo $cashinhand; ?>)</h4>
      </div>
      <div class="modal-body">

<form method="post" action="sf_hisab.php">
<fieldset>
  <div class="form-group">
      <label for="inputEmail" class="col-lg-3 control-label">Date</label>
      <div class="col-lg-6">
        <input type="text" class="form-control gregdate" name="date1" value="<?php echo date("Y-m-d") ?>"/>
      </div>
    </div>
    <div class="form-group col-xs-4 col-md-4">
        <label for="item" class="control-label">Item</label>
        <input type="text" value='' class="form-control" id="item" name="row[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row1[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row2[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row3[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row4[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row5[]"><br>
        <input type="text" value='' class="form-control" id="item" name="row6[]">
    </div>
    <div class="form-group col-xs-4 col-md-4">
        <label for="quantity" class="control-label">Quantity</label>
        <input type="text" value='' class="form-control" id="quantity" name="row[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row1[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row2[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row3[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row4[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row5[]"><br>
        <input type="text" value='' class="form-control" id="quantity" name="row6[]">
    </div>
    <div class="form-group col-xs-4 col-md-4">
        <label for="amount" class="control-label">Amount</label>
        <input type="number" value='' class="form-control" id="amount" name="row[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row1[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row2[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row3[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row4[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row5[]"><br>
        <input type="number" value='' class="form-control" id="amount" name="row6[]">

    </div>
    </fieldset>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
      </form>
    </div>
  </div>
</div>
</div>
<?php include('_bottomJS.php'); ?>
<script type="text/javascript">
  $('#my-table').dynatable();
</script>

</body>
</html>


