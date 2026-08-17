<table class="table table-striped display" width="100%">
  <thead>
    <tr>
      <th>Sabeel No</th>
      <th>Tiffin No</th>
      <th>Thali Size</th>
      <th>Name</th>
      <th>Mobile No</th>
      <th>Active</th>
      <th>Transporter</th>
      <th>Thali Delivered</th>
      <th>Current Hub</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($values = mysqli_fetch_assoc($result)) { ?>
      <tr>
        <td><a href="thalisearch.php?thalino=<?php echo urlencode($values['Thali']); ?>&year=<?php echo urlencode($_GET['year'] ?? ''); ?>"><?php echo e($values['Thali']); ?></a>
        </td>
        <td><?php echo e($values['tiffinno']); ?></td>
        <td><?php echo e($values['thalisize']); ?></td>
        <td><?php echo e($values['NAME']); ?></td>
        <td><a href="tel:<?php echo e($values['CONTACT']); ?>"><?php echo e($values['CONTACT']); ?></a></td>
        <td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
        <td><?php echo e($values['Transporter']); ?></td>
        <td><?php echo (($max_days[0] ?? 0) > 0) ? round($values['thalicount'] * 100 / $max_days[0]) . '%' : '0%'; ?> of days</td>
        <td><?php echo e((string) $values['yearly_hub']); ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>
