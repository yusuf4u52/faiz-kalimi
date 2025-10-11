<?php 
include('../header.php');
include('../navbar.php');

$query = "SELECT id, Thali, tiffinno, NAME, CONTACT, Active, Transporter, thalisize, extraRoti, yearly_hub, ITS_No, Email_ID, SEmail_ID, thalicount, Thali_start_date, Thali_stop_date, Full_Address, musaid, Paid, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist WHERE thalisize LIKE 'Friday' ORDER BY tiffinno ASC";
$result = mysqli_query($link, $query);
$max_days = mysqli_fetch_row(mysqli_query($link, "SELECT MAX(thalicount) as max FROM thalilist"));
if (mysqli_num_rows($result) > 1): ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-3">Friday Thalis</h2>
            <div class="table-responsive">
                <table class="table table-striped display" width="100%">
                <thead>
                    <tr>
                        <th>Thali Status</th>
                        <th>Tiffin No</th>
                        <th>Sabeel No</th>
                        <th>Name</th>
                        <th>Mobile No</th>
                        <th>Active</th>
                        <th>Transporter</th>
                        <th>Address</th>
                        <th>Thali Delivered</th>
                        <th>Current Hub</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($values = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <?php if ($values['Active'] == '1') { ?>
                                <td><a href="#" onclick="stopThali_admin('<?php echo $values['Thali']; ?>', '0')">Stop Thaali</a></td>
                            <?php } else { ?>
                                <td><a href="#" onclick="stopThali_admin('<?php echo $values['Thali']; ?>', '1')">Start Thaali</a></td>
                            <?php } ?>
                            <td><?php echo $values['tiffinno']; ?></td>
                            <td><?php echo $values['Thali']; ?></td>
                            <td><?php echo $values['NAME']; ?></td>
                            <td><a href="tel:<?php echo $values['CONTACT']; ?>"><?php echo $values['CONTACT']; ?></a></td>
                            <td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $values['Transporter']; ?></td>
                            <td><?php echo $values['Full_Address']; ?></td>
                            <td><?php echo round($values['thalicount'] * 100 / $max_days[0]); ?>% of days</td>
                            <td><?php echo $values['yearly_hub']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include('../footer.php'); ?>