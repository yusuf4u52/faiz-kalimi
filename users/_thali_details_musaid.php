<?php
$previous_values = mysqli_fetch_assoc(
    mysqli_query(
        $link,
        "SELECT id, Thali, Name, contact, yearly_hub, total_pending, Previous_Due, Paid, thalicount, WhatsApp FROM $previous_thalilist where Thali='" . $values['Thali'] . "'"
    )
);
?>

<div class="modal" id="details-<?php echo $values['Thali']; ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Details - Thali# <?php echo $values['Thali']; ?> <?php echo $values['Name']; ?></h4>
            </div>
            <div class="modal-body">
                <h2>Thali Details</h2>
                <ul class="list-group col">
                    <li class="list-group-item">
                        <h6 class="list-group-item-head ing text-muted">ITS No</h6>
                        <p class="list-group-item-text"><strong><?php echo $values['ITS_No']; ?></strong></p>
                    </li>
                    <li class="list-group-item">
                        <h6 class="list-group-item-head ing text-muted">Contact</h6>
                        <p class="list-group-item-text"><strong><?php echo $values['contact']; ?></strong></p>
                    </li>
                </ul>

                <h2>Year <?php echo $previous_year; ?></h2>
                <ul class="list-group col">
                    <li class="list-group-item">
                        <h6 class="list-group-item-head ing text-muted">Hub Pending <strong>(Yearly Takhmeen + Previous Due - Paid = Total Pending)</strong></h6>
                        <p class="list-group-item-text"><strong><?php echo $previous_values['yearly_hub']; ?> + <?php echo $previous_values['Previous_Due']; ?> - <?php echo $previous_values['Paid']; ?> = <?php echo $previous_values['total_pending']; ?></strong></p>
                    </li>
                    <li class="list-group-item">
                        <h6 class="list-group-item-head ing text-muted">Thali Delivered</h6>
                        <p class="list-group-item-text"><strong><?php echo round($previous_values['thalicount'] * 100 / $max_days_previous[0]); ?>% of days</strong></p>
                    </li>
                    <li class="list-group-item">
                        <table class="table table-striped table-hover">
                            <tr>
                                <td><b>Receipt No</b></td>
                                <td><b>Amount</b></td>
                                <td><b>Date</b></td>
                            </tr>
                            <?php
                            $query = "SELECT r.* FROM $previous_receipts r, $previous_thalilist t WHERE r.userid = t.id and t.id ='" . $previous_values['id'] . "' ORDER BY Date ASC";
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

                <h2>Year <?php echo $current_year['value']; ?></h2>
                <ul class="list-group col">
                    <li class="list-group-item">
                        <h6 class="list-group-item-head ing text-muted">Hub Pending <strong>(Yearly Takhmeen + Previous Due - Paid = Total Pending)</strong></h6>
                        <p class="list-group-item-text"><strong><?php echo $values['yearly_hub']; ?> + <?php echo $values['Previous_Due']; ?> - <?php echo $values['Paid']; ?> = <?php echo $values['total_pending']; ?></strong></p>
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
                            $query = "SELECT r.* FROM receipts r, thalilist t WHERE r.userid = t.id and t.id ='" . $values['id'] . "' ORDER BY Date ASC";
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



            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>