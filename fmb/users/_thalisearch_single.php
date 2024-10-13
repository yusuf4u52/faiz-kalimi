<?php
$values = mysqli_fetch_assoc($result);

$comments_query = "SELECT `comments`.*, `thalilist`.`NAME` FROM `comments` INNER JOIN `thalilist` on `comments`.`author_id` = `thalilist`.`id`
WHERE `comments`.`user_id` = '" . $values['id'] . "' ORDER BY `comments`.`created` DESC ";
$comments_result = mysqli_query($link, $comments_query);

$musaid_details = mysqli_fetch_assoc(mysqli_query($link, "SELECT NAME, CONTACT FROM thalilist where Email_id = '" . $values['musaid'] . "'"));

?>
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="false">
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingThaali">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" href="#collapseThaali" aria-expanded="true" aria-controls="collapseThaali">
          Thaali Details
        </a>
      </h4>
    </div>
    <div id="collapseThaali" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingThaali">
      <div class="panel-body">
        <ul class="list-group col">
          <li class="list-group-item">
            <a href="#" data-key="payhoob" data-thali="<?php echo $values['Thali']; ?>">Pay Hoob</a> |
            <?php
            if ($values['Active'] == '1') { ?>
              <a href="#" data-key="stopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="0">Stop Thaali</a> |
            <?php } else { ?>
              <a href="#" data-key="stopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="1">Start Thaali</a> |
            <?php }
            if ($values['Active'] != '2') { ?>
              <a href="#" data-key="stoppermanant" data-thali="<?php echo $values['Thali']; ?>">Stop Permanent</a> |
            <?php } ?>
            <a data-toggle="modal" href="#changeMusaid">Change Musaid</a> |
            <a data-toggle="modal" href="#changeThalisize">Change Thali Size</a>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Sabeel Number</h6>
            <p class="list-group-item-text"><strong><?php echo $values['Thali']; ?></strong></p>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Tiffin Number</h6>
            <p class="list-group-item-text"><strong><?php echo $values['tiffinno']; ?></strong></p>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Name</h6>
            <p class="list-group-item-text"><strong><?php echo $values['NAME']; ?></strong></p>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Mobile No</h6>
            <p class="list-group-item-text"><strong><?php echo $values['CONTACT']; ?></strong></p>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-heading text-muted">Thali Type</h6>
            <p class="list-group-item-text"><strong><?php echo $values['thalisize']; ?></strong></p>
          </li>
          <?php if ($musaid_details) { ?>
            <li class="list-group-item">
              <h6 class="list-group-item-head ing text-muted">Musaid</h6>
              <p class="list-group-item-text"><strong><?php echo $musaid_details['NAME']; ?> | <a href="tel:<?php echo $musaid_details['CONTACT']; ?>"><?php echo $musaid_details['CONTACT']; ?></a></strong></p>
            </li>
          <?php } ?>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Active</h6>
            <p class="list-group-item-text"><strong><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></strong></p>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Transporter</h6>
            <p class="list-group-item-text"><strong><?php echo $values['Transporter']; ?></strong></p>
          </li>
          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Address</h6>
            <p class="list-group-item-text"><strong><?php echo $values['Full_Address']; ?></strong></p>
          </li>

          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Start Date</h6>
            <p class="list-group-item-text"><strong class="hijridate"><?php echo $values['Thali_start_date']; ?></strong></p>
          </li>

          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Stop Date</h6>
            <p class="list-group-item-text"><strong class="hijridate"><?php echo $values['Thali_stop_date']; ?></strong></p>
          </li>

          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Hub Pending</h6>
            <p class="list-group-item-text"><strong><?php echo $values['Total_Pending'] + $values['Paid']; ?> - <?php echo $values['Paid']; ?> = <?php echo $values['Total_Pending']; ?></strong></p>
          </li>

          <li class="list-group-item">
            <h6 class="list-group-item-head ing text-muted">Thali Delivered</h6>
            <p class="list-group-item-text"><strong><?php echo round($values['thalicount'] * 100 / $max_days[0]); ?>% of days</strong></p>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingMenu">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" href="#collapseMenu" aria-expanded="true" aria-controls="collapseMenu">
          Menu Details
        </a>
      </h4>
    </div>
    <div id="collapseMenu" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingMenu">
      <div class="panel-body">
        <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
          <div class="alert alert-success" role="alert">Thali of <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is edited successfully for thali no <strong><?php echo $_GET['thalino']; ?></strong></div>
        <?php } ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'nochange') { ?>
          <div class="alert alert-warning" role="alert">No change found for Thali of <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> for thali no <strong><?php echo $_GET['thalino']; ?></strong>.</div>
        <?php } ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'rsvp') { ?>
          <div class="alert alert-danger" role="alert">You can't edit the thali now because RSVP time for editing Thali of
            <strong>
              <?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> for thali no <strong><?php echo $_GET['thalino']; ?></strong> is finished.
          </div>
        <?php } ?>
        <?php $menu_list = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` >= '".date('Y-m-d')."' AND `menu_type` = 'thaali' order by `menu_date` DESC") or die(mysqli_error($link)); ?>
        <table class="table table-striped table-hover">
          <tr>
            <th><b>Date</b></th>
            <th width="50%"><b>Menu Item</b></th>
            <th><b>Action</b></th>
          </tr>
          <?php while ($menu_values = mysqli_fetch_assoc($menu_list)) {
            $menu_id = $menu_values['id'];
            $menu_date = $menu_values['menu_date'];
            $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '".$menu_values['menu_date']."' AND `thali` = '".$values['Thali']."'") or die(mysqli_error($link));
            if($user_menu->num_rows > 0) {
              $row = $user_menu->fetch_assoc();
              $menu_item = unserialize($row['menu_item']);
              if( !empty($menu_item['roti']['qty'])) {
                $roti_qty = $menu_item['roti']['qty'];
              }
              $target = 'adminusermenu-'.$menu_id; 
             } else {
              $menu_item = unserialize($menu_values['menu_item']);
              if($values['thalisize'] == 'Mini') {
                $roti_qty = $menu_item['roti']['tqty'];
              } elseif($values['thalisize'] == 'Small') {
                $roti_qty = $menu_item['roti']['sqty'];
              } elseif($values['thalisize'] == 'Medium') {
                $roti_qty = $menu_item['roti']['mqty'];
              } elseif($values['thalisize'] == 'Large') {
                $roti_qty = $menu_item['roti']['lqty'];
              }
              $target = 'adminmenu-'.$menu_id;
            } ?>
            <tr>
              <td><?php echo date('d M Y', strtotime($menu_date)); ?></td>
              <td>
                <?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] . '  (' . $menu_item['sabji']['qty'] . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] . '  (' . $menu_item['tarkari']['qty'] . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] . '  (' . $menu_item['rice']['qty'] . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] . '  (' . $roti_qty . ')<br/>' : ''); ?>
              </td>
              <td><?php if (date('Y-m-d') < $menu_date) { ?><button type="button" class="btn btn-success" data-target="#<?php echo $target; ?>" data-toggle="modal"><i class="fas fa-edit"></i></button><?php } else { ?> <button type="button" class="btn btn-warning" disabled>RSVP Ended</button><?php } ?></td>
            </tr>
          <?php mysqli_free_result($user_menu); }
          mysqli_free_result($menu_list); ?>
        </table>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingReceipt">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" href="#collapseReceipt" aria-expanded="true" aria-controls="collapseReceipt">
          Receipt Details
        </a>
      </h4>
    </div>
    <div id="collapseReceipt" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingReceipt">
      <div class="panel-body">
        <table class="table table-striped table-hover">
          <tr>
            <th><b>Receipt No</b></th>
            <th><b>Amount</b></th>
            <th><b>Date</b></th>
          </tr>
          <?php
          $query = "SELECT r.* FROM $receipts_tablename r, thalilist t WHERE r.userid = t.id and t.id ='" . $values['id'] . "' ORDER BY Date ASC";
          $result = mysqli_query($link, $query);
          while ($row = mysqli_fetch_assoc($result)) {
            foreach ($row as $key => $value) {
              $row[$key] = stripslashes($value);
            }
            echo "<tr>";
            echo "<td>" . nl2br($row['Receipt_No']) . "</td>";
            echo "<td>" . nl2br($row['Amount']) . "</td>";
            echo "<td class=\"hijridate\">" . nl2br($row['Date']) . "</td>";
            echo "</tr>";
          }
          ?>
        </table>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingComment">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" href="#collapseComment" aria-expanded="true" aria-controls="collapseComment">
          Comments
        </a>
      </h4>
    </div>
    <div id="collapseComment" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingComment">
      <div class="panel-body">
        <form method="post">
          <textarea name="comment" class="form-control" placeholder="write a comment..." rows="3"></textarea>
          <input type="hidden" name="user_id" value="<?php echo $values['id']; ?>">
          <br>
          <button type="submit" class="btn btn-info pull-right">Post</button>
        </form>
        <div class="clearfix"></div>
        <hr>
        <ul class="media-list">
          <?php while ($comment = mysqli_fetch_assoc($comments_result)) { ?>
            <li class="media">
              <div class="media-body">
                <span class="text-muted pull-right">
                  <small class="text-muted"><?php echo $comment['created']; ?></small>
                </span>
                <strong class="text-success"><?php echo $comment['NAME']; ?></strong>
                <p><?php echo $comment['comment']; ?></p>
              </div>
            </li>
          <?php
          }
          ?>
        </ul>
      </div>
    </div>
  </div>
</div>