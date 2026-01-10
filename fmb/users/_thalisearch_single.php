<?php $values = mysqli_fetch_assoc($result);
$comments_query = "SELECT `comments`.*, `thalilist`.`NAME` FROM `comments` INNER JOIN `thalilist` on `comments`.`author_id` = `thalilist`.`id`
WHERE `comments`.`user_id` = '" . $values['id'] . "' ORDER BY `comments`.`created` DESC ";
$comments_result = mysqli_query($link, $comments_query);
$musaid_details = mysqli_fetch_assoc(mysqli_query($link, "SELECT username, mobile FROM users where email = '" .
  $values['musaid'] . "'")); ?>
<ul class="nav nav-tabs mb-4" id="thaaliTab" role="thalilist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="thaali-tab" data-bs-toggle="tab" data-bs-target="#thaali" type="button"
      role="tab" aria-controls="thaali" aria-selected="true">Thaali Details</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="stop-tab" data-bs-toggle="tab" data-bs-target="#stop" type="button" role="tab"
      aria-controls="menu" aria-selected="false">Stop Thaali</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu" type="button" role="tab"
      aria-controls="menu" aria-selected="false">Menu Details</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab"
      aria-controls="receipt" aria-selected="false">Receipt</button>
  </li>
  <!--<li class="nav-item" role="presentation">
    <button class="nav-link" id="comment-tab" data-bs-toggle="tab" data-bs-target="#comment" type="button" role="tab"
      aria-controls="comment" aria-selected="false">Comments</button>
  </li>-->
</ul>
<div class="tab-content" id="thaaliTabContent">
  <div class="tab-pane fade show active" id="thaali" role="tabpanel" aria-labelledby="thaali-tab">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <h4 class="mb-3">Thaali Details</h4>
      </li>
      <li class="list-group-item">
        <ul class="nav nav-underline">
          <!--<li class="nav-item"><a class="nav-link" href="#" data-key="payhoob" data-thali="<?php echo $values['Thali']; ?>">Pay Hoob</a></li>-->
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeMusaid">Change Musaid</a>
          </li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#extraRoti">Extra Roti</a>
          </li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeEmail">Change Email</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeThalisize">Change Thali
              Size</a></li>
        </ul>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Sabeel Number</div>
        <?php echo $values['Thali']; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Tiffin Number</div>
        <?php echo $values['tiffinno']; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Thali Size</div>
        <?php echo $values['thalisize']; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Extra Roti</div>
        <?php echo $values['extraRoti']; ?>
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
        <div class="fw-bold">Mobile No</div>
        <a href="tel:<?php echo $values['CONTACT']; ?>"><?php echo $values['CONTACT']; ?></a>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Email Address</div>
        <a href="mailto:<?php echo $values['Email_ID']; ?>"><?php echo $values['Email_ID']; ?></a> <?php if(!empty($values['SEmail_ID'])) : ?>| <a
          href="mailto:<?php echo $values['SEmail_ID']; ?>"><?php echo $values['SEmail_ID']; ?></a> <?php endif; ?>
      </li>
      <?php if ($musaid_details) { ?>
        <li class="list-group-item">
          <div class="fw-bold">Masool</div>
          <?php echo $musaid_details['username']; ?> | <a
            href="tel:<?php echo $musaid_details['mobile']; ?>"><?php echo $musaid_details['mobile']; ?></a></strong>
          </p>
        </li>
      <?php } ?>
      <li class="list-group-item">
        <div class="fw-bold">Active</div>
        <?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></strong>
        </p>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Transporter</div>
        <?php echo $values['Transporter']; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Full Address</div>
        <?php echo $values['wingflat']; ?>, <?php echo $values['society']; ?>, <?php echo $values['Full_Address']; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Start Date</div>
        <span class="hijridate"><?php echo $values['Thali_start_date']; ?></span>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Stop Date</div>
        <span class="hijridate"><?php echo $values['Thali_stop_date']; ?></span>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Current Year Hub</div>
        ₹<?php echo $values['yearly_hub']; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Hub Pending</div>
        ₹<?php echo $values['Total_Pending'] + $values['Paid']; ?> -
        ₹<?php echo $values['Paid']; ?> = ₹<?php echo $values['Total_Pending']; ?>
      </li>
      <?php if($values['Total_Pending'] > 0) { ?>
        <li class="list-group-item">
          <?php $msg = "Salaam " . $values['NAME'] . ", %0A%0AAapna ghare *Faiz ul Mawaid il Burhaniyah* ni barakat pohchi rahi che. Iltemas che k aapni pending hoob jald si jald ada kariye ane hamne FMB khidmat team ne yaari aapiye.
          %0A%0ASabil - " . $values['Thali'] . "
    			%0APending Hoob - " . $values['Total_Pending']
  				?>
  				<a target="_blank" href="https://wa.me/91<?php echo $values['WhatsApp']; ?>?text=<?php echo ($msg); ?>">WhatsApp</a>
        </li>
      <?php } ?>
      <li class="list-group-item">
        <div class="fw-bold">Thali Delivered</div>
        <?php echo round($values['thalicount'] * 100 / $max_days[0]); ?>%
        of days
      </li>
    </ul>
  </div>
  <div class="tab-pane fade" id="menu" role="tabpanel" aria-labelledby="menu-tab">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <h4 class="mb-3">Menu Details</h4>
      </li>
    </ul>
    <?php $menu_list = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` >= '" . date('Y-m-d') . "' AND `menu_type` = 'thaali' order by `menu_date` DESC") or die(mysqli_error($link)); ?>
    <div class="table-responsive">
      <table class="table table-striped display" width="100%">
        <thead>
          <tr>
            <th>Date</th>
            <th width="50%">Menu Item</th>
            <th>Thali Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($menu_values = mysqli_fetch_assoc($menu_list)) {
            $menu_id = $menu_values['id'];
            $menu_date = $menu_values['menu_date'];
            $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $menu_values['menu_date'] . "' AND `thali` = '" . $values['Thali'] . "'") or die(mysqli_error($link));
            if ($user_menu->num_rows > 0) {
              $row = $user_menu->fetch_assoc();
              $menu_item = unserialize($row['menu_item']);
              if (!empty($menu_item['roti']['qty'])) {
                $roti_qty = $menu_item['roti']['qty'];
              }
              $target = 'adminusermenu-' . $menu_id;
            } else {
              $menu_item = unserialize($menu_values['menu_item']);
              if ($values['thalisize'] == 'Mini' && !empty($menu_item['roti']['tqty'])) {
                $roti_qty = $menu_item['roti']['tqty'];
              } elseif ($values['thalisize'] == 'Small' && !empty($menu_item['roti']['sqty'])) {
                $roti_qty = $menu_item['roti']['sqty'];
              } elseif ($values['thalisize'] == 'Medium' && !empty($menu_item['roti']['mqty'])) {
                $roti_qty = $menu_item['roti']['mqty'];
              } elseif ($values['thalisize'] == 'Large' && !empty($menu_item['roti']['lqty'])) {
                $roti_qty = $menu_item['roti']['lqty'];
              }
              $target = 'adminmenu-' . $menu_id;
            }
            $stopthali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `stop_date` = '" . $menu_values['menu_date'] . "' AND `thali` = '" . $values['Thali'] . "'") or die(mysqli_error($link));
            if ($stopthali->num_rows > 0) {
              $status = '<span style="color:#dc3545;">Stop</span>';
            } else {
              $status = '<span style="color:#198754;">Start</span>';
            } ?>
            <tr>
              <td><?php echo date('d M Y', strtotime($menu_date)); ?></td>
              <td>
                <?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] . '  (' . $menu_item['sabji']['qty'] . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] . '  (' . $menu_item['tarkari']['qty'] . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] . '  (' . $menu_item['rice']['qty'] . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] . '  (' . $roti_qty . ')<br/>' : ''); ?>
              </td>
              <td><?php echo $status; ?></td>
              <td><?php if (date('Y-m-d') < $menu_date) { ?><button type="button" class="btn btn-light"
                    data-bs-target="#<?php echo $target; ?>" data-bs-toggle="modal"><i
                      class="bi bi-pencil-square"></i></button><?php } else { ?> <button type="button" class="btn btn-light"
                    disabled>RSVP Ended</button><?php } ?>
              </td>
            </tr>
            <?php mysqli_free_result($user_menu);
          }
          mysqli_free_result($menu_list); ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="tab-pane fade" id="stop" role="tabpanel" aria-labelledby="menu-tab">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <h4 class="mb-3">Stop Dates</h4>
      </li>
      <li class="list-group-item">
        <ul class="nav nav-underline">
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="modal" href="#stop_thali">Stop By Dates</a>
          </li>
          <li class="nav-item"><?php
            if ($values['Active'] == '1') { ?>
              <a class="nav-link" href="#" data-key="stopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="0">Stop Thaali</a>
            <?php } else { ?>
              <a class="nav-link" href="#" data-key="stopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="1">Start Thaali</a>
            <?php } ?>
          </li>
          <?php if ($values['Active'] != '2') { ?>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="modal" href="#stop_permanent">Stop Permanent</a>
            </li>
          <?php } ?>
        </ul>
      </li>
    </ul>
    <?php
    date_default_timezone_set('Asia/Kolkata');
    $stop_dates = mysqli_query($link, "WITH ranked_dates AS (
        SELECT `id`, `thali`, `stop_date`, ROW_NUMBER() OVER (PARTITION BY `thali` ORDER BY `stop_date`) AS row_num FROM `stop_thali` where `Thali` = '" . $values['Thali'] . "'
    ),
    grouped_dates AS (
        SELECT `id`, `thali`, `stop_date`, DATE_SUB(`stop_date`, INTERVAL row_num DAY) AS group_key FROM ranked_dates
    )
    SELECT `id`, `thali`, MIN(`stop_date`) AS start_date, MAX(`stop_date`) AS end_date FROM grouped_dates GROUP BY `thali`, group_key ORDER BY start_date DESC;") or die(mysqli_error($link));
    if (isset($stop_dates) && $stop_dates->num_rows > 0) { ?>
        <div class="table-responsive">
            <table class="table table-striped display" width="100%">
                <thead>
                    <tr>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($stop_values = mysqli_fetch_assoc($stop_dates)) {
                        $stop_date = new DateTime($stop_values['start_date'] . '00:00:00');
                        $stop_date = $stop_date->format('Y-m-d H:i:s'); ?>
                        <tr>
                            <td data-sort="<?php echo strtotime($stop_values['start_date']); ?>"><?php echo date('d M Y', strtotime($stop_values['start_date'])); ?></td>
                            <td data-sort="<?php echo strtotime($stop_values['end_date']); ?>"><?php echo date('d M Y', strtotime($stop_values['end_date'])); ?></td>
                            <td><?php if (date('Y-m-d H:i:s') < $stop_date) { ?><button type="button"
                                        class="btn btn-light"
                                        data-bs-target="#startthali-<?php echo $stop_values['id']; ?>"
                                        data-bs-toggle="modal" style="margin-bottom:5px">Delete</button><?php } else { ?> <button type="button"
                                        class="btn btn-light" disabled>RSVP Ended</button> <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else {
        echo '<h5 class="text-center mb-3">Currently you has no stop dates.</h5>';
    } mysqli_free_result($stop_dates); ?>
  </div>
  <div class="tab-pane fade" id="receipt" role="tabpanel" aria-labelledby="receipt-tab">
    <h4 class="mb-3">Receipt Details</h4>
    <div class="table-responsive">
      <table class="table table-striped display" width="100%">
        <thead>
          <tr>
            <th>Receipt No</th>
            <th>Amount</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
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
            echo "<td data-sort=" . strtotime($row['Date']) . ">" . date('d M Y', strtotime($row['Date'])) . "</td>";
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <!--<div class="tab-pane fade" id="comment" role="tabpanel" aria-labelledby="comment-tab">
    <h4 class="mb-3">Comments</h4>
    <form method="post" autocomplete="off">
      <textarea name="comment" class="form-control" placeholder="Write a comment..." rows="3"></textarea>
      <input type="hidden" name="user_id" value="<?php echo $values['id']; ?>">
      <br>
      <button type="submit" class="btn btn-light pull-right">Post</button>
    </form>
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
</div>-->
