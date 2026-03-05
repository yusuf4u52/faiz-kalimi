<style>
.vjb-forms-wrapper {
    font-size: 14px;
}
.vjb-form-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 12px auto;
    padding: 8mm;
    box-sizing: border-box;
    border: 1px solid #777;
    background: #fff;
    page-break-inside: avoid;
}
.vjb-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 14px;
}
.vjb-table td {
    border: 1px solid #777;
    padding: 3px 8px;
    vertical-align: middle;
}
.vjb-label {
    font-weight: 700;
    width: 13%;
    white-space: nowrap;
}
.vjb-value {
    font-weight: 600;
}
.vjb-logo-cell {
    width: 13%;
    text-align: center;
    vertical-align: top !important;
    padding-top: 8px !important;
}
.vjb-logo-cell img {
    width: 52px;
    height: 52px;
    object-fit: contain;
}
.vjb-head-main {
    text-align: center;
    font-weight: 700;
    font-size: 15px;
    line-height: 1.25;
    text-transform: uppercase;
}
.vjb-head-sub {
    margin-top: 18px;
    font-size: 15px;
}
.vjb-row-sm td {
    height: 42px;
}
.vjb-row-md td {
    height: 50px;
}
.vjb-row-gap td {
    height: 56px;
}
.vjb-band-blue {
    background: #5b8fe3;
    font-weight: 700;
    text-align: center;
    text-decoration: underline;
}
.vjb-band-cyan {
    background: #11ace3;
    font-weight: 700;
    text-align: center;
    text-decoration: underline;
}
.vjb-year {
    text-align: center;
    font-weight: 700;
    font-size: 15px;
}
.vjb-prev-value {
    margin-top: 2px;
    font-size: 13px;
    font-weight: 600;
}
.vjb-prev-row-value {
    text-align: center;
    font-size: 14px;
    font-weight: 600;
}
.vjb-sign-box {
    height: 128px;
    vertical-align: top !important;
    position: relative;
    font-size: 13px;
}
.vjb-sign-title {
    font-weight: 700;
    text-align: center;
    margin-top: 2px;
    letter-spacing: 0.02em;
}
.vjb-sign-label {
    position: absolute;
    bottom: 4px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 15px;
    font-weight: 700;
}
.vjb-sign-box .vjb-sign-top {
    font-size: 14px;
}
@page {
    size: A4;
    margin: 0;
}
@media print {
    body * {
        visibility: hidden !important;
    }
    .vjb-forms-wrapper,
    .vjb-forms-wrapper * {
        visibility: visible !important;
    }
    .vjb-forms-wrapper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
    }
    html,
    body {
        width: auto;
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .card,
    .card-body {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        position: static !important;
    }
    .container,
    .container-fluid,
    .content-wrapper,
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        position: static !important;
    }
    .d-print-none {
        display: none !important;
    }
    .vjb-form-page {
        width: 210mm;
        min-height: 0;
        height: 297mm;
        border: none;
        margin: 0;
        padding: 8mm;
        page-break-after: always;
        break-after: page;
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .vjb-form-page:last-of-type {
        page-break-after: auto;
        break-after: auto;
    }
}
</style>
<div class="d-flex justify-content-end mb-2 d-print-none">
    <button type="button" class="btn btn-light btn-sm" onclick="window.print()">Print</button>
</div>
<div class="vjb-forms-wrapper">
    <?php foreach ($records as $row): ?>
        <?php
        $vajebaatPrev = trim((string)($row->vajebaat_prev ?? ''));
        $annualNiyazPrev = trim((string)($row->annual_niyaz_prev ?? ''));
        $ikramPrev = trim((string)($row->ikram_prev ?? ''));
        $husainiStatus = trim((string)($row->husaini_scheme_status_prev ?? ''));
        $sector = trim((string)($row->sector ?? ''));
        $subSector = trim((string)($row->sub_sector ?? ''));
        $slotDate = '';
        if (!empty($row->slot_date)) {
            $slotTimestamp = strtotime((string)$row->slot_date);
            $slotDate = $slotTimestamp ? date('d-m-Y', $slotTimestamp) : (string)$row->slot_date;
        }
        ?>
        <div class="vjb-form-page">
            <table class="vjb-table">
                <colgroup>
                    <col style="width:13%">
                    <col style="width:16%">
                    <col style="width:14%">
                    <col style="width:16%">
                    <col style="width:13%">
                    <col style="width:28%">
                </colgroup>
                <tr>
                    <td class="vjb-logo-cell">
                        <picture>
                            <source srcset="/shehrullah/assets/img/logo.avif" type="image/avif">
                            <img src="/shehrullah/assets/img/logo.png" alt="Kalimi Mohalla Logo" loading="lazy" decoding="async" fetchpriority="low" width="52" height="52">
                        </picture>
                    </td>
                    <td colspan="5" class="vjb-head-main">
                        DAWOODI BOHRA JAMAAT TRUST, KALIMI MOHALLA, POONA JAMAAT
                        <div class="vjb-head-sub">VAJEBAAT TAKHMEEN FORM <?= h($current_year) ?>H</div>
                    </td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">Sabil #</td>
                    <td class="vjb-value"><?= h($row->sabeel ?? '') ?></td>
                    <td class="vjb-label">Name</td>
                    <td colspan="3" class="vjb-value"><?= h($row->Full_Name ?? '') ?></td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">ITS #</td>
                    <td class="vjb-value"><?= h($row->ITS_ID ?? '') ?></td>
                    <td class="vjb-label">Mobile #</td>
                    <td class="vjb-value"><?= h($row->Mobile ?? '') ?></td>
                    <td class="vjb-label">HOF/FM #</td>
                    <td class="vjb-value"></td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">Address #</td>
                    <td colspan="3" class="vjb-value"><?= h($row->Address ?? '') ?></td>
                    <td class="vjb-label">Date</td>
                    <td class="vjb-value"><?= h($slotDate) ?></td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">Sector</td>
                    <td class="vjb-value"><?= h($sector) ?></td>
                    <td class="vjb-label">Sub Sector</td>
                    <td class="vjb-value"><?= h($subSector) ?></td>
                    <td class="vjb-label">ITS Data Verification Status</td>
                    <td class="vjb-value"></td>
                </tr>

                <tr><td colspan="6" class="vjb-band-blue">Vajebaat</td></tr>
                <tr>
                    <td colspan="3" class="vjb-band-blue vjb-year">
                        <?= h($vajebaat_prev_label) ?>
                    </td>
                    <td colspan="3" class="vjb-band-blue vjb-year"><?= h($vajebaat_curr_label) ?></td>
                </tr>
                <tr class="vjb-row-gap">
                    <td colspan="3" class="vjb-prev-row-value"><?php if ($vajebaatPrev !== ''): ?><div class="vjb-prev-value"><?= h($vajebaatPrev) ?></div><?php endif; ?></td>
                    <td colspan="3"></td>
                </tr>

                <tr><td colspan="6" class="vjb-band-cyan">Annual Niyaz</td></tr>
                <tr>
                    <td colspan="3" class="vjb-band-cyan vjb-year">
                        <?= h($niyaz_prev_label) ?>
                    </td>
                    <td colspan="3" class="vjb-band-cyan vjb-year"><?= h($niyaz_curr_label) ?></td>
                </tr>
                <tr class="vjb-row-gap">
                    <td colspan="3" class="vjb-prev-row-value"><?php if ($annualNiyazPrev !== ''): ?><div class="vjb-prev-value"><?= h($annualNiyazPrev) ?></div><?php endif; ?></td>
                    <td colspan="3"></td>
                </tr>

                <tr><td colspan="6" class="vjb-band-blue">Ikram</td></tr>
                <tr>
                    <td colspan="3" class="vjb-band-blue vjb-year">
                        <?= h($ikram_prev_label) ?>
                    </td>
                    <td colspan="3" class="vjb-band-blue vjb-year"><?= h($ikram_curr_label) ?></td>
                </tr>
                <tr class="vjb-row-gap">
                    <td colspan="3" class="vjb-prev-row-value"><?php if ($ikramPrev !== ''): ?><div class="vjb-prev-value"><?= h($ikramPrev) ?></div><?php endif; ?></td>
                    <td colspan="3"></td>
                </tr>

                <tr>
                    <td colspan="3" class="vjb-sign-box">
                        <div class="vjb-sign-top">Sign:</div>
                        <div class="vjb-sign-label">Abde Syedna</div>
                    </td>
                    <td colspan="3" class="vjb-sign-box" style="padding: 0;">
                        <div class="vjb-band-cyan vjb-sign-title" style="padding: 10px 8px; text-decoration: none;">HUSAINI SCHEME ACCOUNT STATUS</div>
                        <div style="padding: 10px 8px; font-weight: 700; font-size: 14px; text-align: center;"><?= h($husainiStatus) ?></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="vjb-sign-box">
                        <div class="vjb-sign-top">Sign:</div>
                        <div class="vjb-sign-label">Secretary / Treasurer</div>
                    </td>
                    <td colspan="3" class="vjb-sign-box">
                        <div class="vjb-sign-top">Sign:</div>
                        <div class="vjb-sign-label">Aamil Saheb</div>
                    </td>
                </tr>
            </table>
        </div>
    <?php endforeach; ?>
</div>
