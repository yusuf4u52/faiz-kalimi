<?php
$values = mysqli_fetch_assoc($result);

$comments_query = "SELECT `comments`.*, `thalilist`.`NAME` FROM `comments` INNER JOIN `thalilist` on `comments`.`author_id` = `thalilist`.`id`
WHERE `comments`.`user_id` = '" . $values['id'] . "' ORDER BY `comments`.`created` DESC ";
$comments_result = mysqli_query($link, $comments_query);

$musaid_details = mysqli_fetch_assoc(mysqli_query($link, "SELECT NAME, CONTACT FROM thalilist where Email_id = '" . $values['musaid'] . "'"));

?>
<div class="panel panel-default">
  <div aria-labelledby="headingOne">
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
          <a data-toggle="modal" href="#changeMusaid">Change Musaid</a>
        </li>
        <li class="list-group-item">
          <h6 class="list-group-item-head ing text-muted">Thaali Number</h6>
          <p class="list-group-item-text"><strong><?php echo $values['Thali']; ?></strong></p>
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
        <li class="list-group-item">
          <table class="table table-striped table-hover">
            <tr>
              <td><b>Receipt No</b></td>
              <td><b>Amount</b></td>
              <td><b>Date</b></td>
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
        </li>
      </ul>

      <!-- Comment section starts-->
      <div class="row bootstrap snippets">
        <div class="col-md-12 col-sm-12">
          <div class="comment-wrapper">
            <div class="panel panel-info">
              <div class="panel-heading">
                Comments
              </div>
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

                  <?php
                  while ($comment = mysqli_fetch_assoc($comments_result)) {
                  ?>
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
          <!-- Comment section ends-->

        </div>
      </div>
    </div>