<table class="table table-striped table-hover">

  <thead>

    <tr>
      <th>Pay Hoob</th>
      <th>Stop Thaali</th>
      <th>Stop Permanant</th>
      <th>Thali No</th>
      <th>Name</th>
      <th>Mobile No</th>
      <th>Active</th>
      <th>Transporter</th>
      <th>Address</th>
      <th>Start Date</th>
      <th>Stop Date</th>
      <th>Thali Delivered</th>
      <th>Hub pending</th>
    </tr>
  </thead>

  <tbody>
    <?php
      while($values = mysqli_fetch_assoc($result))
      {
    ?>
    <tr>
      <td><a href="#" data-key="payhoob" data-thali="<?php echo $values['Thali']; ?>">Pay Hoob</a></td>
      <td><?php
        if($values['Active'] == '1') {?>
          <a href="#" data-key="stopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="0">Stop Thaali</a>
          <?php }else{ ?>
          <a href="#" data-key="stopthaali" data-thali="<?php echo $values['Thali']; ?>" data-active="1">Start Thaali</a>
          <?php } ?>
      </td>
      <td><?php
        if($values['Active'] != '2') {?>
          <a href="#" data-key="stoppermanant" data-thali="<?php echo $values['Thali']; ?>">Stop Permanant</a>
        <?php } ?>
      </td>
      <td><?php echo $values['Thali']; ?></td>
      <td><?php echo $values['NAME']; ?></td>
      <td><?php echo $values['CONTACT']; ?></td>
      <td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
      <td><?php echo $values['Transporter']; ?></td>
      <td><?php echo $values['Full_Address']; ?></td>
      <td class="hijridate"><?php echo $values['Thali_start_date']; ?></td>
      <td class="hijridate"><?php echo $values['Thali_stop_date']; ?></td>
      <td><?php echo round($values['thalicount'] * 100 / $max_days[0]); ?>% of days</td>
      <td><?php echo $values['Total_Pending']; ?></td>
    </tr>
    <?php } ?>
  </tbody>
</table>