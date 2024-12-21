<div class="table-responsive">
  <table class="table table-striped display" width="100%">
    <thead>
      <tr>
        <th>Sabeel No</th>
        <th>Tiffin No</th>
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
        <td><?php echo $values['Thali']; ?></td>
        <td><?php echo $values['tiffinno']; ?></td>
        <td><?php echo $values['NAME']; ?></td>
        <td><a href="tel:<?php echo $values['CONTACT']; ?>"><?php echo $values['CONTACT']; ?></a></td>
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
</div>