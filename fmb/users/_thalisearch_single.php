<?php
// $values, $adminMenuEntries, and $stopDateRanges are all set by
// thalisearch.php before this file is included — see
// build_admin_menu_entries() / get_stop_date_ranges() there, which
// replaced this file's own (and thalisearch.php's separate) per-date
// menu_list/user_menu/stop_thali queries with a single shared batch fetch.

$musaid_details = null;
if (!empty($values['musaid'])) {
    $musaid_details = mysqli_fetch_assoc(
        db_query($link, "SELECT username, mobile FROM users WHERE email = ?", "s", [$values['musaid']])
    );
}
?>
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
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab"
      aria-controls="members" aria-selected="false">Members</button>
  </li>
</ul>
<div class="tab-content" id="thaaliTabContent">
  <div class="tab-pane fade show active" id="thaali" role="tabpanel" aria-labelledby="thaali-tab">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <h4 class="mb-3">Thaali Details</h4>
      </li>
      <li class="list-group-item">
        <ul class="nav nav-underline">
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeMusaid">Change Masool</a>
          </li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeTransporter">Change Transporter</a>
          </li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#extraRoti">Extra Roti</a>
          </li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#lessRice">Less Rice</a>
          </li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeEmail">Change Email</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="modal" href="#changeThalisize">Change Thali
              Size</a></li>
        </ul>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Sabeel Number</div>
        <?php echo e($values['Thali']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Sabeel Type</div>
        <?php echo e($values['sabeelType']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Tiffin Number</div>
        <?php echo e($values['tiffinno']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Thali Size</div>
        <?php echo e($values['thalisize']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Extra Roti</div>
        <?php echo e((string) $values['extraRoti']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Less Rice</div>
        <?php echo e((string) $values['lessRice']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">HOF ITS No</div>
        <?php echo e($values['ITS_No']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Name</div>
        <?php echo e($values['NAME']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Mobile No</div>
        <a href="tel:<?php echo e($values['CONTACT']); ?>"><?php echo e($values['CONTACT']); ?></a>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Email Address</div>
        <a href="mailto:<?php echo e($values['Email_ID']); ?>"><?php echo e($values['Email_ID']); ?></a> <?php if (!empty($values['SEmail_ID'])) : ?>| <a
          href="mailto:<?php echo e($values['SEmail_ID']); ?>"><?php echo e($values['SEmail_ID']); ?></a> <?php endif; ?>
      </li>
      <?php if ($musaid_details) { ?>
        <li class="list-group-item">
          <div class="fw-bold">Masool</div>
          <?php echo e($musaid_details['username']); ?> | <a
            href="tel:<?php echo e($musaid_details['mobile']); ?>"><?php echo e($musaid_details['mobile']); ?></a>
        </li>
      <?php } ?>
      <li class="list-group-item">
        <div class="fw-bold">Active</div>
        <?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Transporter</div>
        <?php echo e($values['Transporter']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Full Address</div>
        <?php echo e($values['wingflat']); ?>, <?php echo e($values['society']); ?>, <?php echo e($values['Full_Address']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Start Date</div>
        <span class="hijridate"><?php echo e($values['Thali_start_date']); ?></span>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Stop Date</div>
        <span class="hijridate"><?php echo e($values['Thali_stop_date']); ?></span>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Previous Year Hub</div>
        ₹<?php echo e((string) $values['previous_hub']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Current Year Hub</div>
        ₹<?php echo e((string) $values['yearly_hub']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Zabihat Niyat</div>
        <?php echo e($values['Zabihat']); ?>
      </li>
      <li class="list-group-item">
        <div class="fw-bold">Hub Pending</div>
        ₹<?php echo e((string) ($values['Total_Pending'] + $values['Paid'])); ?> -
        ₹<?php echo e((string) $values['Paid']); ?> = ₹<?php echo e((string) $values['Total_Pending']); ?>
      </li>
      <?php if ($values['Total_Pending'] > 0) { ?>
        <li class="list-group-item">
          <div class="fw-bold">Whatsapp Mumin for Hub</div>
          <?php
          // Build the message as plain text with real newlines, then let
          // rawurlencode() handle ALL the URL escaping — the original
          // manually spliced in literal "%0A" for newlines but left the
          // rest of the string (including the member's own name) totally
          // unencoded, so a name containing "&" or "#" could break the
          // wa.me URL's query string.
          $msg = "Salaam " . $values['NAME'] . ", \n\nAapna ghare *Faiz ul Mawaid il Burhaniyah* ni barakat pohchi rahi che. Iltemas che k aapni pending hoob jald si jald ada kariye ane hamne FMB khidmat team ne yaari aapiye.\n\nSabil - " . $values['Thali'] . "\nPending Hoob - " . $values['Total_Pending'];
          ?>
          <a target="_blank" href="https://wa.me/91<?php echo e($values['WhatsApp']); ?>?text=<?php echo rawurlencode($msg); ?>">WhatsApp</a>
        </li>
      <?php } ?>
      <li class="list-group-item">
        <div class="fw-bold">Thali Delivered</div>
        <?php
        echo ($max_days[0] > 0)
          ? round($values['thalicount'] * 100 / $max_days[0]) . '%'
          : '0%';
        ?> of days
      </li>
    </ul>
  </div>
  <div class="tab-pane fade" id="menu" role="tabpanel" aria-labelledby="menu-tab">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <h4 class="mb-3">Menu Details</h4>
      </li>
    </ul>
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
          <?php foreach ($adminMenuEntries as $entry) {
            $menu_item = $entry['menu_item']; ?>
            <tr>
              <td><?php echo e(date('d M Y', strtotime($entry['date']))); ?></td>
              <td>
                <?php echo (!empty($menu_item['sabji']['item']) ? e($menu_item['sabji']['item']) . '  (' . e((string) $menu_item['sabji']['qty']) . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['tarkari']['item']) ? e($menu_item['tarkari']['item']) . '  (' . e((string) $menu_item['tarkari']['qty']) . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['rice']['item']) ? e($menu_item['rice']['item']) . '  (' . e((string) $menu_item['rice']['qty']) . ')<br/>' : ''); ?>
                <?php echo (!empty($menu_item['roti']['item']) ? e($menu_item['roti']['item']) . '  (' . e((string) $entry['roti_qty']) . ')<br/>' : ''); ?>
              </td>
              <td><?php echo $entry['status'] === 'stop' ? '<span style="color:#dc3545;">Stop</span>' : '<span style="color:#198754;">Start</span>'; ?></td>
              <td><?php if (date('Y-m-d') < $entry['date']) { ?><button type="button" class="btn btn-light"
                    data-bs-target="#<?php echo e($entry['target']); ?>" data-bs-toggle="modal"><i
                      class="bi bi-pencil-square"></i></button><?php } else { ?> <button type="button" class="btn btn-light"
                    disabled>RSVP Ended</button><?php } ?>
              </td>
            </tr>
          <?php } ?>
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
          <li class="nav-item">
            <?php if ($values['Active'] != '1') { ?>
              <a class="nav-link" href="#" data-key="stopthaali" data-thali="<?php echo e($values['Thali']); ?>" data-active="1">Start Thaali</a>
            <?php } else { ?>
              <a class="nav-link" href="#" data-key="startthaali" data-thali="<?php echo e($values['Thali']); ?>" data-active="0">Stop Thaali</a>
            <?php } ?>
          </li>
          <?php if ($values['hardstop'] != '1') { ?>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="modal" href="#stop_permanent">Stop Permanent</a>
            </li>
          <?php } ?>
        </ul>
      </li>
    </ul>
    <?php if (!empty($stopDateRanges)) { ?>
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
            <?php foreach ($stopDateRanges as $stop_values) {
              $endDateTime = (new DateTime($stop_values['end_date'] . ' 00:00:00'))->format('Y-m-d H:i:s'); ?>
              <tr>
                <td data-sort="<?php echo strtotime($stop_values['start_date']); ?>"><?php echo e(date('d M Y', strtotime($stop_values['start_date']))); ?></td>
                <td data-sort="<?php echo strtotime($stop_values['end_date']); ?>"><?php echo e(date('d M Y', strtotime($stop_values['end_date']))); ?></td>
                <td><?php if (date('Y-m-d H:i:s') < $endDateTime) { ?><button type="button"
                      class="btn btn-light"
                      data-bs-target="#startthali-<?php echo (int) $stop_values['id']; ?>"
                      data-bs-toggle="modal" style="margin-bottom:5px">Delete</button><?php } else { ?> <button type="button"
                      class="btn btn-light" disabled>RSVP Ended</button> <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } else { ?>
      <h5 class="text-center mb-3">Currently you has no stop dates.</h5>
    <?php } ?>
  </div>
  <div class="tab-pane fade" id="receipt" role="tabpanel" aria-labelledby="receipt-tab">
    <h4 class="mb-3">Receipt Details</h4>
    <div id="printableReceipts" class="table-responsive">
      <table class="table table-striped" width="100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>Hijri</th>
            <th>Receipt No</th>
            <th>Name</th>
            <th>Amount</th>
            <th>Pay Mode</th>
            <th>Takhmeen Year</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Table name interpolation here is unavoidable (table names
          // can't be bound as query parameters); $receipts_tablename can
          // now only ever be 'receipts' or 'receipts_<validated 4-digit
          // year>' — see the validation in thalisearch.php.
          $stmt = mysqli_prepare($link, "SELECT r.* FROM `$receipts_tablename` r, thalilist t WHERE r.userid = t.id AND t.id = ? ORDER BY r.Date ASC");
          mysqli_stmt_bind_param($stmt, "s", $values['id']);
          mysqli_stmt_execute($stmt);
          $receiptResult = mysqli_stmt_get_result($stmt);
          while ($row = mysqli_fetch_assoc($receiptResult)) {
            // NOTE: the original code ran stripslashes() on every field
            // here — a leftover from PHP's "magic quotes" feature, removed
            // in PHP 5.4. With magic quotes gone, that call was silently
            // corrupting any receipt data that happened to contain a
            // genuine backslash.
            echo "<tr>";
            echo "<td data-sort=" . strtotime($row['Date']) . ">" . e(date('d M Y', strtotime($row['Date']))) . "</td>";
            echo "<td>" . e(getHijriFullDate($row['Date'])) . "</td>";
            echo "<td>" . nl2br(e($row['Receipt_No'])) . "</td>";
            echo "<td>" . nl2br(e($row['name'])) . "</td>";
            echo "<td>" . nl2br(e((string) $row['Amount'])) . "</td>";
            echo "<td>" . nl2br(e($row['payment_type'])) . "</td>";
            echo "<td>" . nl2br(e($row['takmeem_year'])) . "</td>";
            echo "</tr>";
          }
          mysqli_free_result($receiptResult);
          ?>
        </tbody>
      </table>
    </div>
    <div class='col-12'>
      <button class='btn btn-light' id='Print' onclick="printTabs()">Print</button>
    </div>
  </div>
  <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
    <h4 class="mb-3">Member Details</h4>
    <div id="printableMembers" class="table-responsive">
      <table class="table table-striped" width="100%">
        <thead>
          <tr>
            <th>ITS No</th>
            <th>Member Type</th>
            <th>Full Name</th>
            <th>Age</th>
            <th>Gender</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $res_members = db_query($link, "SELECT * FROM thalilist_members WHERE thalilist_id = ? ORDER BY age DESC", "s", [$values['id']]);
          while ($row_members = mysqli_fetch_assoc($res_members)) {
            echo "<tr>";
            echo "<td>" . nl2br(e($row_members['its_no'])) . "</td>";
            echo "<td>" . nl2br(e($row_members['member_type'])) . "</td>";
            echo "<td>" . nl2br(e($row_members['full_name'])) . "</td>";
            echo "<td>" . nl2br(e((string) $row_members['age'])) . "</td>";
            echo "<td>" . nl2br(e($row_members['gender'])) . "</td>";
            echo "</tr>";
          }
          mysqli_free_result($res_members);
          ?>
        </tbody>
      </table>
    </div>
    <div class='col-12'>
      <button class='btn btn-light' id='Print' onclick="printTabs()">Print</button>
    </div>
  </div>

  <script>
    function printTabs() {
      var originalContents = document.body.innerHTML; // Store original content
      var printReciepts = document.getElementById('printableReceipts').innerHTML;
      var printMembers = document.getElementById('printableMembers').innerHTML;
      var printContents = printReciepts + printMembers;

      document.body.innerHTML = printContents; // Replace body content with the div's content
      window.print(); // Print the current body content

      document.body.innerHTML = originalContents; // Restore the original page
    }
  </script>
