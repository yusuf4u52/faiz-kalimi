<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');

if (!user_email_in(EVENT_NOT_REGISTERED_VIEWER_EMAILS)) {
    header("Location: /fmb/users/events.php");
    exit;
}

$eventId = $_GET['eventid'] ?? null;
if ($eventId === null || !ctype_digit((string) $eventId)) {
    header("Location: /fmb/users/events.php");
    exit;
}

$eventResult = db_query($link, "SELECT `name`, `venue` FROM events WHERE id = ?", "i", [(int) $eventId]);
$event = mysqli_fetch_assoc($eventResult);
if (!$event) {
    header("Location: /fmb/users/events.php");
    exit;
}

$registeredCountResult = db_query($link, "SELECT COUNT(*) AS cnt FROM event_response WHERE eventid = ?", "i", [(int) $eventId]);
$total_registered_count = (int) mysqli_fetch_assoc($registeredCountResult)['cnt'];

$result = db_query(
    $link,
    "SELECT t.Thali, t.ITS_No, t.NAME, t.CONTACT
     FROM thalilist t
     WHERE t.id NOT IN (SELECT thaliid FROM event_response WHERE eventid = ?)
       AND t.Active IN (0, 1) AND t.Thali IS NOT NULL",
    "i",
    [(int) $eventId]
);
$notRegistered = mysqli_fetch_all($result, MYSQLI_ASSOC);

include('header.php');
include('navbar.php');
?>

<div class="card">
    <div class="card-body">
        <h3><?php echo e($event['name']) . ' - ' . e($event['venue']); ?></h3>
        <h5>Total registered count <?php echo $total_registered_count; ?></h5>
        <h5>Total not registered count <?php echo count($notRegistered); ?></h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Thali</th>
                        <th scope="col">ITS</th>
                        <th scope="col">Name</th>
                        <th scope="col">Mobile</th>
                        <th scope="col">Whatsapp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notRegistered as $values) { ?>
                        <tr>
                            <td><?php echo e($values['Thali']); ?></td>
                            <td><?php echo e($values['ITS_No']); ?></td>
                            <td><?php echo e($values['NAME']); ?></td>
                            <td><?php echo e($values['CONTACT']); ?></td>
                            <td><a href="https://wa.me/91<?php echo e($values['CONTACT']); ?>?text=<?php echo rawurlencode("Salaam,\nYou have not yet registered for the event.\nPlease do it as soon as possible"); ?>">WhatsApp</a></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
