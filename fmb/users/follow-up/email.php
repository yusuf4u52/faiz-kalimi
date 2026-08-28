<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../connection.php');
require_once('../helpers.php');
require_once('../_sendMail.php');

if (!user_email_in(FOLLOW_UP_ACCESS_EMAILS)) {
    http_response_code(403);
    exit('Forbidden.');
}

$report = (string) ($_POST['report'] ?? '');
$reports = [
    'local' => [
        'title' => 'Local Current Year Pending Hoob',
        'where' => '(Previous_Due + yearly_hub - Paid) = yearly_hub AND thalisize IS NOT NULL AND hardstop != 1 AND sabeelType = \'Kalimi ITS\'',
    ],
    'non-local' => [
        'title' => 'Non Local Current Year Pending Hoob',
        'where' => '(Previous_Due + yearly_hub - Paid) = yearly_hub AND thalisize IS NOT NULL AND hardstop != 1 AND yearly_hub > 0 AND sabeelType != \'Kalimi ITS\'',
    ],
    'previous' => [
        'title' => 'Previous Year Pending Hoob',
        'where' => 'Previous_Due > 4 AND thalisize IS NOT NULL AND hardstop != 1',
    ],
];

if (!isset($reports[$report])) {
    header('Location: local.php?status=' . urlencode('Invalid email report.'));
    exit;
}

$query = "SELECT NAME, ITS_No, Thali, Email_ID, SEmail_ID, Previous_Due,
                 (Previous_Due + yearly_hub - Paid) AS Total_Pending
          FROM thalilist
          WHERE {$reports[$report]['where']}
          ORDER BY Total_Pending DESC";
$members = db_query($link, $query);
$sent = 0;
$failed = 0;

while ($member = mysqli_fetch_assoc($members)) {
    $recipients = [];
    foreach ([$member['Email_ID'], $member['SEmail_ID']] as $email) {
        $email = trim((string) $email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients, true)) {
            $recipients[] = $email;
        }
    }

    if ($recipients === []) {
        $failed++;
        continue;
    }

    $name = e((string) $member['NAME']);
    $itsNumber = e((string) $member['ITS_No']);
    $sabeelNumber = e((string) $member['Thali']);
    $pendingAmount = number_format((float) ($member['Total_Pending'] ?? 0));
    $previousDue = (float) ($member['Previous_Due'] ?? 0);
    $previousDueMessage = '';
    if ($previousDue > 0) {
        $previousDueMessage = '<p>Aapni pichla Waras ni due <strong>₹' . number_format($previousDue) . '</strong> haji pan pending che. Aap si adab sathe iltemaas che ane ummeed ke Aqa Maula T.U.S na Milad Mubarak na pehla aa due ada kari ne clear kari aapso.</p>';
    }
    $emailBody = "
        <div style=\"font-family:Arial,sans-serif;color:#333;max-width:640px;margin:auto\">
            <img src=\"https://kalimijamaatpoona.org/fmb/assets/img/logo.avif\" alt=\"FMB Kalimi Mohalla\" width=\"90\" height=\"90\" style=\"display:block;margin:0 auto 20px\">
            <p>Salaam <strong>{$name}</strong>,</p>
            <p><strong>Reminder - FMB Hoob Pending</strong></p>
            <p>Aapna ghare <strong>Faiz ul Mawaid il Burhaniyah</strong> ni barakat pohchi rahi che. Aapni FMB ni hoob baki che, je ni tafseel niche aapi che.</p>
            <table cellpadding=\"8\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%;border:1px solid #ddd\">
                <tr><td style=\"border:1px solid #ddd\"><strong>Name</strong></td><td style=\"border:1px solid #ddd\">{$name}</td></tr>
                <tr><td style=\"border:1px solid #ddd\"><strong>ITS Number</strong></td><td style=\"border:1px solid #ddd\">{$itsNumber}</td></tr>
                <tr><td style=\"border:1px solid #ddd\"><strong>Sabeel Number</strong></td><td style=\"border:1px solid #ddd\">{$sabeelNumber}</td></tr>
                <tr><td style=\"border:1px solid #ddd\"><strong>Pending Amount</strong></td><td style=\"border:1px solid #ddd\">₹ {$pendingAmount}</td></tr>
            </table>
            {$previousDueMessage}
            <p>Aaje <strong>Al-Hayyul Muqaddas Syedna Mohammed Burhanuddin (RA)</strong> na <strong>Urs Mubarak</strong> no din che. Aa Maula ni nazarat ane barakat si <strong>Faiz ul Mawaid il Burhaniyah</strong> no aa amal-e-jariyah saariyan che ane ghana gharo sudhi Faiz ni barkat pohchi rahi che.</p>
            <p>Aap si adab sathe iltemaas che ke aa mubarak din ma aap aapni pending FMB ni hoob ada kari ne aa khidmat ma shamil thaiye ane Maula ni barkat haasil kariye. Aapni timely hoob si <strong>Faiz</strong> ane aa khidmat nu nizam barabar chaltu rahe che.</p>
            <p>FMB ni hoob ada kerva waste <strong>7th Miqaat - Milad-un-Nabi (S.A)</strong> gujri chuko che. Aapne iltemaas che ke aap pending hoob jald si jald ada kari aapsho.</p>
            <p>Agar aap aa hoob pehla thi ada kari chuka ho, to payment ni receipt ya transfer nu screenshot hamne mokli aapsho, taake ame aapna records update kari shakay.</p>
            <p>Was Salaam,<br><strong>FMB Khidmat Team</strong></p>
        </div>";

    if (sendEmail($recipients, 'Reminder - FMB Hoob Pending', $emailBody)) {
        $sent++;
    } else {
        $failed++;
    }
}

$status = sprintf('%d email(s) sent, %d failed.', $sent, $failed);
header('Location: ' . $report . '.php?status=' . urlencode($status));
exit;