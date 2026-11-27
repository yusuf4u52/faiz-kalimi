<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');

// Any user in the users table may open this page; they see only the members assigned to their own user id.
$me = mysqli_fetch_assoc(db_query($link, "SELECT id FROM users WHERE email = ? LIMIT 1", "s", [$_SESSION['email'] ?? '']));
if (!$me) {
    echo '<div class="alert alert-danger">You do not have access to this page.</div>';
    include('../footer.php');
    exit;
}

// Members assigned to THIS musaid (thalilist.fmb_musaid = users.id) who still have a pending amount.
$list = db_query(
    $link,
    "SELECT t.Thali, t.tiffinno, t.CONTACT, t.WhatsApp, t.ITS_No, t.NAME, t.previous_hub, t.yearly_hub,
            (t.Previous_Due + t.yearly_hub - t.Paid) AS Total_Pending, t.Paid, t.`Paid %` AS paid_pct,
            t.wingflat, t.society, r.Amount, r.Date
     FROM thalilist AS t
     LEFT JOIN (
         SELECT userid, Amount, Date FROM (
             SELECT userid, Amount, Date, ROW_NUMBER() OVER (PARTITION BY userid ORDER BY Date DESC, id DESC) AS rn FROM receipts
         ) AS ranked WHERE rn = 1
     ) AS r ON t.id = r.userid
     WHERE t.fmb_musaid = ? AND t.hardstop != 1 AND (t.Previous_Due + t.yearly_hub - t.Paid) > 0
     ORDER BY Total_Pending DESC",
    "s",
    [(string) $me['id']]
);

// First column is the "#" counter that main.js fills in for #transporterlist.
$headings = ['#', 'Sabeel No', 'Thali No', 'Contact', 'Whatsapp', 'ITS No', 'Name', 'Previous Hub', 'Current Hub',
    'Pending', 'Paid', 'Last Payment', 'Payment Date', 'Paid %', 'Wing/Flat', 'Society'];
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">Musaid Follow Up</h2>
                <?php if ($list->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table id="transporterlist" class="table table-striped table-hover">
                            <thead>
                                <tr><?php foreach ($headings as $h) { echo '<th scope="col">' . e($h) . '</th>'; } ?></tr>
                            </thead>
                            <tbody>
                                <?php $n = 0; while ($v = mysqli_fetch_assoc($list)) { $n++; ?>
                                    <tr>
                                        <td><?php echo $n; ?></td>
                                        <td><?php echo e($v['Thali']); ?></td>
                                        <td><?php echo e($v['tiffinno']); ?></td>
                                        <td><a href="tel:<?php echo e($v['CONTACT']); ?>"><?php echo e($v['CONTACT']); ?></a></td>
                                        <td><a href="https://wa.me/91<?php echo e($v['WhatsApp']); ?>" target="_blank"><?php echo e($v['WhatsApp']); ?></a></td>
                                        <td><?php echo e($v['ITS_No']); ?></td>
                                        <td><?php echo e($v['NAME']); ?></td>
                                        <td><?php echo e((string) $v['previous_hub']); ?></td>
                                        <td><?php echo e((string) $v['yearly_hub']); ?></td>
                                        <td><strong><?php echo e((string) $v['Total_Pending']); ?></strong></td>
                                        <td><?php echo e((string) $v['Paid']); ?></td>
                                        <td><?php echo e((string) ($v['Amount'] ?? '')); ?></td>
                                        <td><?php echo e((string) ($v['Date'] ?? '')); ?></td>
                                        <td><?php echo e((string) ($v['paid_pct'] ?? '')); ?>%</td>
                                        <td><?php echo e($v['wingflat']); ?></td>
                                        <td><?php echo e($v['society']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr><?php foreach ($headings as $h) { echo '<th scope="col">' . e($h) . '</th>'; } ?></tr>
                            </tfoot>
                        </table>
                    </div>
                <?php } else { ?>
                    <h4 class="text-center mt-5">No pending follow-ups assigned to you.</h4>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>